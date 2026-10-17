"""Offline orchestration/fixture tests, never a hosted Drupal qualification."""
import copy
from contextlib import closing
import hashlib
import json
import os
from pathlib import Path
import shutil
import sqlite3
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parent.parent / 'scripts'))
import production_action as action
import production_operator as operator


class FixtureBackend:
    """Real disposable files and SQLite; deliberately not the hosting adapter."""
    def __init__(self, root, maintenance=False, failure=None):
        self.root, self.maintenance, self.failure = root, maintenance, failure
        self.calls = []
        self.active = 'b' * 64
        (root / 'code').write_text('previous', encoding='ascii')
        with closing(sqlite3.connect(root / 'live.sqlite')) as db, db:
            db.execute('CREATE TABLE requests (message TEXT)')
            db.execute("INSERT INTO requests VALUES ('received after launch')")

    def visit(self, name):
        self.calls.append(name)
        if self.failure == name:
            raise RuntimeError('fixture private error')

    def preflight(self, request):
        self.visit('preflight')
        return dict(activeArchiveSha256=self.active, maintenanceActive=self.maintenance,
                    **{k: True for k in ('candidateVerified', 'schemaCompatible', 'rollbackSafe',
                                         'diskSpaceChecked', 'privateBackupLocationChecked',
                                         'isolatedRestoreTargetChecked', 'writersQuiesceSupported')})

    def enter_maintenance(self):
        self.visit('enter_maintenance')
        self.maintenance = True

    def backup(self, run_id):
        self.visit('backup')
        shutil.copyfile(self.root / 'code', self.root / 'saved-code')
        with closing(sqlite3.connect(self.root / 'live.sqlite')) as live, closing(sqlite3.connect(self.root / 'saved.sqlite')) as saved:
            live.backup(saved)
        return {'id': 'backup-' + run_id, 'createdAt': 100,
                'filesSha256': hashlib.sha256((self.root / 'saved-code').read_bytes()).hexdigest(),
                'sqlSha256': hashlib.sha256((self.root / 'saved.sqlite').read_bytes()).hexdigest(),
                'integrityVerified': True, 'complete': True}

    def restore_verify(self, backup):
        self.visit('restore_verify')
        shutil.copyfile(self.root / 'saved-code', self.root / 'restored-code')
        shutil.copyfile(self.root / 'saved.sqlite', self.root / 'isolated.sqlite')
        with closing(sqlite3.connect(self.root / 'isolated.sqlite')) as db:
            assert db.execute('SELECT message FROM requests').fetchone()[0] == 'received after launch'
        return dict(backup, filesRestored=True, sqlRestoredInIsolation=True,
                    drupalBooted=True, activeSitesUntouched=True, outboundMessagesDisabled=True)

    def switch_code(self, request, backup):
        (self.root / 'code').write_text('candidate', encoding='ascii')
        self.active = request['release']['archiveSha256']
        self.visit('switch_code')

    def health(self, archive, maintenance):
        self.visit('health')
        return dict(archiveSha256=self.active, maintenanceActive=self.maintenance,
                    httpChecked=True, drupalChecked=True, businessDataPreserved=True,
                    settingsPreserved=True, preproductionPreserved=True)

    def finish_maintenance(self, previous):
        self.visit('finish_maintenance')
        self.maintenance = previous

    def restore_code(self, backup):
        self.visit('restore_code')
        shutil.copyfile(self.root / 'saved-code', self.root / 'code')
        self.active = 'b' * 64


class ProductionActionTests(unittest.TestCase):
    def setUp(self):
        temporary = tempfile.TemporaryDirectory()
        self.addCleanup(temporary.cleanup)
        self.root = Path(temporary.name)
        (self.root / 'docs/notes-de-version').mkdir(parents=True)
        (self.root / 'docs/notes-de-version/fixture.md').write_text('Fixture only', encoding='ascii')
        self.env = dict(GITHUB_REF='refs/heads/main', GITHUB_SHA='a' * 40, APPROVED_SHA='a' * 40,
                        GITHUB_EVENT_NAME='workflow_dispatch', GITHUB_REPOSITORY=action.REPOSITORY,
                        OPERATION='deploy', RELEASE_ID='fixture', BACKUP_ID='',
                        GITHUB_RUN_ID='1234', GITHUB_RUN_ATTEMPT='1', CONFIRMATION='PRODUCTION TC Longages')
        self.config = dict(format='tcl-production-action-v1', repository=action.REPOSITORY,
                           target=action.TARGET, enabled=True, operatorSha256='d' * 64,
                           adapterSha256='e' * 64, qualificationRef='https://github.com/' + action.REPOSITORY + '/issues/1',
                           environmentReview={'adminBypassDisabled': True, 'evidenceRef': 'https://github.com/' + action.REPOSITORY + '/issues/1'},
                           releases={'fixture': dict(qualified=True, schemaChanges=False, codeRollbackCompatible=True,
                                                     sourceCommit='c' * 40, archiveSha256='a' * 64,
                                                     expectedActiveSha256='b' * 64, qualificationSha256='f' * 64,
                                                     releaseNote='docs/notes-de-version/fixture.md')})
        self.plan = action.validate(self.env, self.config, self.root)
        self.journals = self.root / 'journals'
        self.journals.mkdir()
        self.journal = self.journals / '1234.json'

    def run_transaction(self, backend):
        return operator.transaction(self.plan, backend, self.journal, now=lambda: 100)

    def test_plan_remains_offline_and_repository_is_disabled(self):
        config = json.loads((action.ROOT / 'config/production-action.json').read_text())
        self.assertIs(config['enabled'], False)
        self.assertEqual(config['releases'], {})
        self.assertFalse(action.validate(dict(self.env, OPERATION='plan'), config)['ready'])
        with self.assertRaises(ValueError):
            action.validate(self.env, config)

    def test_dispatch_identity_and_confirmation(self):
        for key, value in [('GITHUB_REF', 'refs/heads/evil'), ('GITHUB_SHA', 'b' * 40),
                           ('GITHUB_EVENT_NAME', 'push'), ('GITHUB_REPOSITORY', 'other/repo'),
                           ('CONFIRMATION', ''), ('OPERATION', 'sql-restore'), ('GITHUB_RUN_ATTEMPT', '2'),
                           ('GITHUB_RUN_ID', '1;id'), ('RELEASE_ID', '../fixture'), ('BACKUP_ID', 'backup-1')]:
            with self.subTest(key=key), self.assertRaises(ValueError):
                action.validate(dict(self.env, **{key: value}), self.config, self.root)

    def test_release_qualification_note_and_schema(self):
        for key, value in [('qualified', False), ('schemaChanges', True), ('codeRollbackCompatible', False),
                           ('sourceCommit', 'main'), ('archiveSha256', 'bad'), ('releaseNote', '../secret')]:
            config = copy.deepcopy(self.config)
            config['releases']['fixture'][key] = value
            with self.subTest(key=key), self.assertRaises(ValueError):
                action.validate(self.env, config, self.root)
        (self.root / 'docs/notes-de-version/fixture.md').unlink()
        with self.assertRaises(ValueError):
            action.validate(self.env, self.config, self.root)

    def test_rollback_requires_an_identifier_not_a_path(self):
        for bad in ('', '../backup', '/home/backup', 'backup;id'):
            with self.subTest(bad=bad), self.assertRaises(ValueError):
                action.validate(dict(self.env, OPERATION='rollback', BACKUP_ID=bad), self.config, self.root)
        plan = action.validate(dict(self.env, OPERATION='rollback', BACKUP_ID='backup-12'), self.config, self.root)
        self.assertTrue(plan['ready'])

    def environment(self):
        return {'name': 'tcl-production', 'can_admins_bypass': False,
                'protection_rules': [{'type': 'required_reviewers', 'reviewers': [{'id': 123}]}],
                'deployment_branch_policy': {'custom_branch_policies': True}}

    def test_live_environment_must_be_protected_and_main_only(self):
        environment = self.environment()
        branches = dict(total_count=1, branch_policies=[{'name': 'main', 'type': 'branch'}])
        def fetch(path, env):
            return branches if 'deployment-branch-policies' in path else environment
        action.verify_environment({}, fetch)
        for change in ({'protection_rules': []}, {'deployment_branch_policy': None}):
            with self.subTest(change=change), self.assertRaises(ValueError):
                action.verify_environment({}, lambda p, e: branches if '?' in p else dict(environment, **change))
        for name, kind in [('*', 'branch'), ('main', 'tag')]:
            branches['branch_policies'][0] = {'name': name, 'type': kind}
            with self.assertRaises(ValueError):
                action.verify_environment({}, fetch)

    def test_production_run_requires_a_recorded_approval(self):
        review = {'state': 'approved', 'user': {'login': 'reviewer'},
                  'environments': [{'name': 'tcl-production'}]}
        action.verify_run_approval(self.plan, {}, lambda p, e: [review])
        for reviews in ([], [dict(review, state='rejected')], [dict(review, environments=[{'name': 'other'}])]):
            with self.subTest(reviews=reviews), self.assertRaises(ValueError):
                action.verify_run_approval(self.plan, {}, lambda p, e: reviews)

    def test_bypass_review_is_explicit_not_an_invented_api_field(self):
        config = copy.deepcopy(self.config)
        config['environmentReview'] = None
        with self.assertRaises(ValueError):
            action.validate(self.env, config, self.root)

    def test_private_request_binds_workflow_catalog_and_adapter(self):
        profile = dict(enabled=True, approvedWorkflowSha='a' * 40)
        operator.validate_request(self.plan, profile, self.config)
        for key, value in [('workflowSha', 'b' * 40), ('catalogSha256', 'b' * 64),
                           ('adapterSha256', 'a' * 64), ('backupId', '../bad'), ('target', 'https://preprod.tclongages.fr')]:
            with self.subTest(key=key), self.assertRaises(ValueError):
                operator.validate_request(dict(self.plan, **{key: value}), profile, self.config)

    def test_success_backups_restore_then_switches_and_preserves_live_data(self):
        backend = FixtureBackend(self.root)
        result = self.run_transaction(backend)
        self.assertEqual(result['status'], 'completed')
        self.assertFalse(backend.maintenance)
        self.assertEqual(backend.calls, ['preflight', 'enter_maintenance', 'backup', 'restore_verify',
                                        'switch_code', 'health', 'finish_maintenance', 'health'])
        with closing(sqlite3.connect(self.root / 'live.sqlite')) as db:
            self.assertEqual(db.execute('SELECT count(*) FROM requests').fetchone()[0], 1)
        self.assertFalse(result['databaseRestored'])
        self.assertFalse(result['preproductionChanged'])

    def test_initial_maintenance_is_preserved(self):
        backend = FixtureBackend(self.root, maintenance=True)
        self.run_transaction(backend)
        self.assertTrue(backend.maintenance)

    def test_failure_before_mutation_does_not_close_the_site(self):
        backend = FixtureBackend(self.root, failure='preflight')
        with self.assertRaises(RuntimeError):
            self.run_transaction(backend)
        self.assertEqual(backend.calls, ['preflight'])
        self.assertEqual(json.loads(self.journal.read_text())['status'], 'refused_before_mutation')

    def test_bad_restore_receipt_never_switches_code(self):
        backend = FixtureBackend(self.root)
        with patch.object(backend, 'restore_verify', return_value={'sqlRestoredInIsolation': False}):
            with self.assertRaises(RuntimeError):
                self.run_transaction(backend)
        self.assertNotIn('switch_code', backend.calls)
        self.assertNotIn('restore_code', backend.calls)
        self.assertTrue(backend.maintenance)

    def test_stale_or_mismatched_backup_refused(self):
        backend = FixtureBackend(self.root)
        with patch.object(backend, 'backup', return_value=dict(id='backup-1', createdAt=99,
                          sqlSha256='f' * 64, filesSha256='e' * 64, integrityVerified=True, complete=True)):
            with self.assertRaises(RuntimeError):
                self.run_transaction(backend)
        self.assertNotIn('switch_code', backend.calls)

    def test_restoration_of_another_backup_is_not_accepted(self):
        backend = FixtureBackend(self.root)
        original = backend.restore_verify
        def wrong(backup):
            return dict(original(backup), sqlSha256='0' * 64)
        with patch.object(backend, 'restore_verify', side_effect=wrong):
            with self.assertRaises(RuntimeError):
                self.run_transaction(backend)
        self.assertNotIn('switch_code', backend.calls)

    def test_live_drift_prevents_any_mutation(self):
        backend = FixtureBackend(self.root)
        backend.active = '0' * 64
        with self.assertRaises(RuntimeError):
            self.run_transaction(backend)
        self.assertEqual(backend.calls, ['preflight'])

    def test_failed_post_open_check_closes_and_recovers(self):
        backend = FixtureBackend(self.root)
        original = backend.health
        count = 0
        def health(archive, maintenance):
            nonlocal count
            count += 1
            result = original(archive, maintenance)
            return dict(result, httpChecked=False) if count == 2 else result
        with patch.object(backend, 'health', side_effect=health):
            with self.assertRaises(RuntimeError):
                self.run_transaction(backend)
        self.assertTrue(backend.maintenance)
        self.assertEqual((self.root / 'code').read_text(), 'previous')

    def test_manual_rollback_also_creates_a_fresh_backup(self):
        self.plan = action.validate(dict(self.env, OPERATION='rollback', BACKUP_ID='backup-old'), self.config, self.root)
        backend = FixtureBackend(self.root)
        result = self.run_transaction(backend)
        self.assertEqual(result['operation'], 'rollback')
        self.assertLess(backend.calls.index('restore_verify'), backend.calls.index('switch_code'))
        self.assertFalse(result['databaseRestored'])

    @unittest.skipUnless(os.name == 'posix', 'Hosted file permissions require POSIX')
    def test_private_file_refuses_symlinks_and_public_permissions(self):
        target = self.root / 'private.json'
        target.write_text('{}')
        target.chmod(0o644)
        with self.assertRaises(ValueError):
            operator.private_file(target)
        target.chmod(0o600)
        self.assertEqual(operator.private_file(target), b'{}')
        link = self.root / 'link.json'
        link.symlink_to(target)
        with self.assertRaises(ValueError):
            operator.private_file(link)

    def test_partial_switch_recovers_code_without_reopening_or_restoring_sql(self):
        backend = FixtureBackend(self.root, failure='switch_code')
        with self.assertRaises(RuntimeError):
            self.run_transaction(backend)
        self.assertEqual((self.root / 'code').read_text(), 'previous')
        self.assertTrue(backend.maintenance)
        result = json.loads(self.journal.read_text())
        self.assertEqual(result['status'], 'code_recovered_maintenance_retained')
        self.assertFalse(result['databaseRestored'])

    def test_failed_recovery_never_claims_success(self):
        backend = FixtureBackend(self.root, failure='switch_code')
        with patch.object(backend, 'restore_code', side_effect=OSError('private error')):
            with self.assertRaises(RuntimeError):
                self.run_transaction(backend)
        self.assertEqual(json.loads(self.journal.read_text())['status'], 'recovery_unconfirmed')

    def test_replay_and_unresolved_journal_block_new_mutations(self):
        backend = FixtureBackend(self.root)
        self.run_transaction(backend)
        with self.assertRaises(ValueError):
            self.run_transaction(backend)
        self.journal.unlink()
        (self.journals / 'older.json').write_text('{"status":"started"}')
        before = list(backend.calls)
        with self.assertRaises(ValueError):
            self.run_transaction(backend)
        self.assertEqual(before, backend.calls)

    def test_receipt_is_bound_and_sanitized(self):
        backend = FixtureBackend(self.root)
        result = self.run_transaction(backend)
        result['secret'] = 'never output'
        self.assertNotIn('secret', action.verify_result(result, self.plan))
        for key, value in [('runId', 'other'), ('status', 'code_recovered_maintenance_retained'),
                           ('databaseRestored', True), ('preproductionChanged', True), ('restoreTested', False)]:
            with self.subTest(key=key), self.assertRaises(ValueError):
                action.verify_result(dict(result, **{key: value}), self.plan)

    def test_ssh_failure_cleans_key_and_never_echoes_remote_output(self):
        env = dict(SSH_USER='daje5127', SSH_PORT='22')
        with patch.object(action, 'setup_ssh', return_value=(self.root, 'fixture@example.invalid')), \
             patch.object(action, 'cleanup_ssh') as cleanup, \
             patch.object(action.subprocess, 'run', return_value=subprocess.CompletedProcess([], 1, b'private', b'secret')):
            with self.assertRaises(ValueError) as error:
                action.execute(self.plan, env)
            self.assertNotIn('secret', str(error.exception))
            cleanup.assert_called_once_with(env)

    def test_ssh_timeout_is_not_retried(self):
        env = dict(SSH_USER='daje5127', SSH_PORT='22')
        with patch.object(action, 'setup_ssh', return_value=(self.root, 'fixture@example.invalid')), \
             patch.object(action, 'cleanup_ssh') as cleanup, \
             patch.object(action.subprocess, 'run', side_effect=subprocess.TimeoutExpired('ssh', 1500)) as run:
            with self.assertRaises(subprocess.TimeoutExpired):
                action.execute(self.plan, env)
            self.assertEqual(run.call_count, 1)
            cleanup.assert_called_once()


if __name__ == '__main__':
    unittest.main()
