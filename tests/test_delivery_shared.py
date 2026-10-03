"""Hostile archives and provenance/SSH gates; no live credentials or host."""
import gzip
import hashlib
import io
import json
import os
from pathlib import Path
import stat
import subprocess
import sys
import tarfile
import tempfile
import unittest
import warnings
from unittest.mock import patch
import zipfile

sys.path.insert(0, str(Path(__file__).resolve().parent.parent/'scripts'))
import delivery_shared as shared
from prepare_delivery import validate_inputs, verify_candidate, build_candidate, PAGES


class DeliveryGates(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self.tmp.cleanup)
        self.root = Path(self.tmp.name)
        self.env = {'GITHUB_REF': 'refs/heads/main', 'GITHUB_SHA': 'a'*40,
                    'APPROVED_SHA': 'a'*40, 'OPERATION': 'build'}

    def zip(self, entries):
        path = self.root/'candidate.zip'
        with zipfile.ZipFile(path, 'w') as archive:
            for name, data in entries:
                if isinstance(name, str):
                    info = zipfile.ZipInfo('placeholder')
                    info.filename = name
                    name = info
                with warnings.catch_warnings():
                    warnings.simplefilter('ignore', UserWarning)
                    archive.writestr(name, data)
        return path

    def test_revision_and_operation(self):
        self.assertEqual(validate_inputs(self.env), 'a'*40)
        for key, value in [('APPROVED_SHA', 'b'*40), ('APPROVED_SHA', 'a'*39),
                           ('APPROVED_SHA', 'a'*40+';id'), ('GITHUB_REF', 'refs/heads/other'),
                           ('OPERATION', 'deploy')]:
            with self.subTest(key=key, value=value), self.assertRaises(ValueError):
                validate_inputs({**self.env, key: value})

    def test_tcl_qualification_profile_must_match(self):
        profile = {'project': 'tclongages', 'environment': 'preproduction',
                   'targetHost': 'preprod.tclongages.fr', 'authorizationRef': 'fixture-approval',
                   'sshHost': 'server.example.org', 'sshUser': 'fixture', 'sshPort': 22}
        env = {**self.env, 'OPERATION': 'qualify-ssh', 'CONFIRMATION': 'QUALIFY SSH TC Longages',
               'SSH_PROFILE': json.dumps(profile), 'SSH_HOST': 'server.example.org',
               'SSH_USER': 'fixture', 'SSH_PORT': '22'}
        validate_inputs(env)
        for change in ({'project': 'avereo'}, {'targetHost': 'avereo.fr'},
                       {'sshHost': 'different.example.org'}, {'authorizationRef': ''}):
            with self.subTest(change=change), self.assertRaises(ValueError):
                validate_inputs({**env, 'SSH_PROFILE': json.dumps({**profile, **change})})
        with self.assertRaises(ValueError):
            validate_inputs({**env, 'CONFIRMATION': ''})

    def test_endpoint_rejects_shell_inputs(self):
        for field in ('host', 'user', 'port'):
            for hostile in ('-option', 'ok;id', '$(id)', 'ok\nid', 'ok path', '../path', ''):
                args = dict(host='server.example.org', user='fixture', port='22')
                args[field] = hostile
                with self.subTest(field=field, hostile=hostile), self.assertRaises(ValueError):
                    shared.ssh_endpoint(**args)

    def test_zip_crc_and_inventory(self):
        path = self.zip([('drupal/composer.lock', b'locked')])
        result = shared.verify_zip(path, {'drupal'})
        self.assertEqual(result['drupal/composer.lock']['sha256'], hashlib.sha256(b'locked').hexdigest())
        data = bytearray(path.read_bytes()); position = data.index(b'locked'); data[position] ^= 1
        path.write_bytes(data)
        with self.assertRaises(zipfile.BadZipFile):
            shared.verify_zip(path, {'drupal'})

    def test_hostile_zip_paths(self):
        for name in ('/drupal/x', 'drupal/../secret', 'drupal\\x', 'drupal//x',
                     'drupal/./x', 'C:/drupal/x', '.local/secret', 'other/x'):
            with self.subTest(name=name), self.assertRaises(ValueError):
                shared.verify_zip(self.zip([(name, b'x')]), {'drupal'})

    def test_duplicate_empty_and_link_zip(self):
        with self.assertRaises(ValueError):
            shared.verify_zip(self.zip([('drupal/x', b'a'), ('drupal/x', b'b')]), {'drupal'})
        with self.assertRaises(ValueError):
            shared.verify_zip(self.zip([]), {'drupal'})
        info = zipfile.ZipInfo('drupal/link'); info.create_system = 3
        info.external_attr = (stat.S_IFLNK | 0o777) << 16
        with self.assertRaises(ValueError):
            shared.verify_zip(self.zip([(info, b'../../secret')]), {'drupal'})

    def test_zip_limits(self):
        path = self.zip([('drupal/x', b'bytes')])
        for limits in ({'max_entries': 0}, {'max_bytes': 4}, {'max_ratio': 0}):
            with self.subTest(limits=limits), self.assertRaises(ValueError):
                shared.verify_zip(path, {'drupal'}, **limits)

    def backup(self, webroot):
        sql, archive = self.root/'sql.gz', self.root/'files.tar.gz'
        sql.write_bytes(gzip.compress(b'CREATE TABLE `fixture` (id int);\n'))
        name = webroot+'/settings.php'; data = b'private fixture'
        with tarfile.open(archive, 'w:gz') as output:
            info = tarfile.TarInfo(name); info.size = len(data)
            output.addfile(info, io.BytesIO(data))
        return sql, archive, {name: hashlib.sha256(data).hexdigest()}

    def test_backup_algorithm_for_both_layouts_does_not_claim_restore(self):
        for webroot in ('sites/default', 'web/sites/default'):
            with self.subTest(webroot=webroot):
                sql, tar, files = self.backup(webroot)
                result = shared.verify_backup_archives(sql, tar, ['fixture'], files, {'sites', 'web'})
                self.assertEqual(result, {'integrityVerified': True, 'restorationTested': False})
                with self.assertRaises(ValueError):
                    shared.verify_backup_archives(sql, tar, ['different'], files, {'sites', 'web'})
                tar.write_bytes(tar.read_bytes()[:-4])
                with self.assertRaises((EOFError, gzip.BadGzipFile)):
                    shared.verify_backup_archives(sql, tar, ['fixture'], files, {'sites', 'web'})

    def test_candidate_provenance_and_active_settings(self):
        path = self.zip([('manifest.json', json.dumps({'sourceCommit': 'b'*40}))])
        with self.assertRaises(ValueError):
            verify_candidate(path, 'a'*40)

    def candidate(self, extra=None, changed_manifest=False):
        names = ['drupal/composer.lock', 'drupal/composer.json', 'drupal/vendor/autoload.php',
                 'drupal/web/core/lib/Drupal.php', 'drupal/web/.htaccess',
                 'drupal/web/modules/custom/tcl_site/tcl_site.info.yml',
                 'drupal/config/settings.hosting.example.php',
                 *(f'drupal/site-pages/{page}.html' for page in PAGES)]
        data = {name: b'fixture' for name in names}; data.update(extra or {})
        files = {name: {'bytes': len(value), 'sha256': hashlib.sha256(value).hexdigest()}
                 for name, value in data.items()}
        if changed_manifest:
            files[names[0]]['sha256'] = 'f'*64
        manifest = {'format': 'tcl-drupal-delivery-v1', 'sourceCommit': 'a'*40,
                    'sourceClean': True, 'configured': False,
                    'publicOpeningAuthorized': False, 'files': files}
        return self.zip([*data.items(), ('manifest.json', json.dumps(manifest))])

    def test_candidate_full_inventory_and_forbidden_settings(self):
        result = verify_candidate(self.candidate(), 'a'*40)
        self.assertFalse(result['deploymentExecuted'])
        with self.assertRaisesRegex(ValueError, 'differ from manifest'):
            verify_candidate(self.candidate(changed_manifest=True), 'a'*40)
        for name in ('drupal/web/sites/default/settings.php', 'drupal/.env.example',
                     'drupal/web/sites/default/files/user-upload.txt'):
            with self.subTest(name=name), self.assertRaisesRegex(ValueError, 'forbidden|Private file'):
                verify_candidate(self.candidate({name: b'fixture'}), 'a'*40)

    def test_build_from_clean_checkout_uses_generated_public_pages(self):
        sources = ('drupal/composer.json', 'drupal/composer.lock', 'drupal/web/.htaccess',
                   'drupal/config/settings.hosting.example.php',
                   'drupal/web/modules/custom/tcl_site/tcl_site.info.yml')
        for name in sources:
            path = self.root/name; path.parent.mkdir(parents=True, exist_ok=True)
            path.write_bytes(b'fixture source')
        (self.root/'.gitignore').write_text('/.local/\n/drupal/vendor/\n/drupal/web/core/\n')
        def git(*args):
            return subprocess.check_output(['git', *args], cwd=self.root, stderr=subprocess.DEVNULL)
        git('init', '--quiet'); git('add', '.')
        git('-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.invalid',
            '-c', 'commit.gpgsign=false', 'commit', '--quiet', '-m', 'fixture source')
        sha = git('rev-parse', 'HEAD').decode().strip()
        for name in ('drupal/vendor/autoload.php', 'drupal/web/core/lib/Drupal.php',
                     *(f'.local/drupal-public-candidate/site-pages/{page}.html' for page in PAGES)):
            path = self.root/name; path.parent.mkdir(parents=True, exist_ok=True)
            path.write_bytes(b'generated fixture')
        self.assertFalse((self.root/'drupal/site-pages').exists())
        with patch.dict(os.environ, {'GITHUB_ACTIONS': 'true', 'APPROVED_SHA': sha}):
            result = build_candidate(self.root/'.local/delivery/candidate.zip', root=self.root)
        self.assertTrue(result['sourceClean'])
        self.assertEqual(result['sourceCommit'], sha)
        self.assertFalse(result['deploymentExecuted'])

    def ssh_env(self):
        return {'RUNNER_TEMP': str(self.root), 'SSH_HOST': 'server.example.org',
                'SSH_USER': 'fixture', 'SSH_PORT': '22', 'SSH_KEY': 'fixture-private-key',
                'KNOWN_HOSTS': 'fixture-known-host', 'SSH_WAIT_SECONDS': '0'}

    def test_ssh_strict_and_cleanup_on_connection_failure(self):
        env = self.ssh_env()
        def run(args, **kwargs):
            if args[0] == 'ssh-keygen':
                return subprocess.CompletedProcess(args, 0, b'', b'')
            self.assertIn('StrictHostKeyChecking=yes', args)
            self.assertIn('IdentitiesOnly=yes', args)
            self.assertEqual(args[-1], 'id -un')
            return subprocess.CompletedProcess(args, 1, b'', b'private error')
        with patch.object(shared.subprocess, 'run', side_effect=run), self.assertRaises(ValueError):
            shared.qualify_ssh(env)
        self.assertFalse(shared.private_ssh_directory(env).exists())

    def test_key_failure_and_wrong_account_never_leave_keys(self):
        env = self.ssh_env()
        for key_fail in (True, False):
            def run(args, **kwargs):
                return subprocess.CompletedProcess(args, 1 if key_fail else 0,
                                                    b'wrong-user\n', b'')
            with self.subTest(key_fail=key_fail), patch.object(shared.subprocess, 'run', side_effect=run), self.assertRaises(ValueError):
                shared.qualify_ssh(env)
            self.assertFalse(shared.private_ssh_directory(env).exists())

    def test_ssh_success_only_attests_to_transport(self):
        with patch.object(shared.subprocess, 'run', return_value=subprocess.CompletedProcess([], 0, b'fixture\n', b'')):
            result = shared.qualify_ssh(self.ssh_env())
        self.assertTrue(result['sshHostVerified'])
        self.assertFalse(result['deploymentExecuted'])
        self.assertFalse(result['remoteTransferExecuted'])

    def test_preexisting_ssh_directory_is_preserved(self):
        env = self.ssh_env()
        directory = shared.private_ssh_directory(env); directory.mkdir()
        existing = directory/'key'; existing.write_bytes(b'preexisting fixture')
        with self.assertRaises(ValueError):
            shared.qualify_ssh(env)
        shared.cleanup_ssh(env)
        self.assertEqual(existing.read_bytes(), b'preexisting fixture')

    def test_backup_wrong_bytes_and_links_are_rejected(self):
        sql, archive, files = self.backup('web/sites/default')
        with self.assertRaises(ValueError):
            shared.verify_backup_archives(sql, archive, ['fixture'], {}, {'web'})
        with tarfile.open(archive, 'w:gz') as output:
            info = tarfile.TarInfo('web/link'); info.type = tarfile.SYMTYPE
            info.linkname = '../../secret'; output.addfile(info)
        with self.assertRaises(ValueError):
            shared.verify_backup_archives(sql, archive, ['fixture'], files, {'web'})


if __name__ == '__main__':
    unittest.main()
