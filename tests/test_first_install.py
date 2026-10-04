"""First-install boundaries, extracted bytes and interrupted attempts; no host."""
import ast
from datetime import datetime, timezone, timedelta
import hashlib
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch
import zipfile

sys.path.insert(0, str(Path(__file__).resolve().parent.parent / 'scripts'))
import first_install as install
from prepare_delivery import PAGES


class FirstInstallBoundaries(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self.tmp.cleanup)
        if os.name == 'nt':
            # Windows stat cannot attest POSIX modes. Exercise the algorithm
            # here, with Linux permission/UID checks explicitly outside proof.
            mode_fixture = patch.object(install.stat, 'S_IMODE', return_value=0o700)
            mode_fixture.start()
            self.addCleanup(mode_fixture.stop)
        self.home = Path(self.tmp.name)
        self.root = self.home / 'tcl-preproduction' / 'drupal'
        (self.root / 'web/cgi-bin').mkdir(parents=True)
        (self.root / 'web/.htaccess').write_bytes(install.DENY)
        self.archive = self.home / 'candidate.zip'
        paths = ['drupal/composer.lock', 'drupal/composer.json', 'drupal/vendor/autoload.php',
                 'drupal/web/core/lib/Drupal.php', 'drupal/web/.htaccess',
                 'drupal/web/modules/custom/tcl_site/tcl_site.info.yml',
                 'drupal/config/settings.hosting.example.php',
                 *('drupal/site-pages/' + page + '.html' for page in PAGES)]
        files = {name: (b'candidate-content-' + name.encode()) for name in paths}
        self.manifest = {'format': 'tcl-drupal-delivery-v1', 'sourceCommit': 'a' * 40,
                         'sourceClean': True, 'configured': False, 'publicOpeningAuthorized': False,
                         'files': {name: {'bytes': len(data), 'sha256': hashlib.sha256(data).hexdigest()}
                                   for name, data in files.items()}}
        manifest_data = json.dumps(self.manifest).encode()
        with zipfile.ZipFile(self.archive, 'w') as bundle:
            for name, data in files.items():
                bundle.writestr(name, data)
            bundle.writestr('manifest.json', manifest_data)
        self.profile = {'project': 'tclongages', 'environment': 'preproduction', 'account': 'sc1fixture',
                        'targetHost': 'preprod.sc1fixture.universe.wf', 'home': '/home2/sc1fixture',
                        'composerRoot': '/home2/sc1fixture/tcl-preproduction/drupal',
                        'documentRoot': '/home2/sc1fixture/tcl-preproduction/drupal/web',
                        'database': 'sc1fixture_tclpreprod', 'databaseUser': 'sc1fixture_tcl',
                        'sourceCommit': 'a' * 40, 'archiveSha256': install.digest(self.archive),
                        'manifestSha256': hashlib.sha256(manifest_data).hexdigest(),
                        'phpBinary': '/usr/local/bin/php', 'installationAuthorizationRef': 'fixture-only'}
        self.qualification = {'targetHost': self.profile['targetHost'], 'environment': 'preproduction',
                              'dnsVerified': True, 'recognizedHttpsVerified': True, 'httpPhpVerified': True,
                              'webRootProtectionVerified': True, 'phpVersion': '8.3.33',
                              'evidenceRef': 'fixture-only',
                              'checkedAt': datetime.now(timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ')}

    def staged(self):
        with patch.object(install, 'validate_profile', return_value=(self.home, self.root)), \
             patch.object(install, 'runtime_gate'):
            return install.stage(self.archive, self.profile, self.qualification)

    def test_target_profile_rejects_other_projects_accounts_and_roots(self):
        install.validate_profile(self.profile)
        changes = [{'project': 'avereo'}, {'environment': 'production'}, {'account': 'principal'},
                   {'targetHost': 'avereo.fr'}, {'home': '/tmp/sc1fixture'},
                   {'composerRoot': '/home2/sc1fixture/public_html'}, {'documentRoot': '/home2/sc1fixture'},
                   {'database': 'sc1fixture_other'}, {'databaseUser': 'root'}, {'sourceCommit': 'bad'},
                   {'archiveSha256': ''}, {'phpBinary': '/bin/sh'}]
        for change in changes:
            with self.subTest(change=change), self.assertRaises(ValueError):
                install.validate_profile({**self.profile, **change})

    @unittest.skipUnless(os.name == 'posix', 'UID gate is Linux-specific')
    def test_runtime_requires_fresh_proofs_and_agreement(self):
        import pwd
        identity = type('Identity', (), {'pw_name': self.profile['account']})()
        with patch.object(pwd, 'getpwuid', return_value=identity):
            install.runtime_gate(self.profile, self.qualification)
            changes = [{'recognizedHttpsVerified': False}, {'targetHost': 'other.example.org'},
                       {'httpPhpVerified': False}, {'webRootProtectionVerified': False},
                       {'phpVersion': '8.1.0'}, {'evidenceRef': ''},
                       {'checkedAt': (datetime.now(timezone.utc) - timedelta(hours=2)).strftime('%Y-%m-%dT%H:%M:%SZ')}]
            for change in changes:
                with self.subTest(change=change), self.assertRaises(ValueError):
                    install.runtime_gate(self.profile, {**self.qualification, **change})
            with self.assertRaises(ValueError):
                install.runtime_gate({**self.profile, 'installationAuthorizationRef': ''}, self.qualification)

    def test_existing_user_files_or_changed_closure_are_preserved(self):
        for extra in (self.root / 'new-file', self.root / 'web/new-file', self.root / 'web/cgi-bin/new-file'):
            extra.write_text('human-content')
            with self.assertRaises(ValueError):
                self.staged()
            self.assertEqual(extra.read_text(), 'human-content')
            extra.unlink()
        (self.root / 'web/.htaccess').write_bytes(b'custom-human-rule')
        with self.assertRaises(ValueError):
            self.staged()
        self.assertEqual((self.root / 'web/.htaccess').read_bytes(), b'custom-human-rule')

    def test_archive_digest_failure_never_changes_target(self):
        self.archive.write_bytes(self.archive.read_bytes() + b'changed')
        with self.assertRaises(ValueError):
            self.staged()
        self.assertEqual((self.root / 'web/.htaccess').read_bytes(), install.DENY)
        self.assertFalse((self.home / 'tcl-preproduction/private').exists())

    def test_staging_preserves_initial_root_and_exact_candidate_files(self):
        result = self.staged()
        self.assertTrue(result['remoteTransferExecuted'])
        self.assertFalse(result['installationExecuted'])
        self.assertFalse(result['publicOpeningExecuted'])
        state = install.read_json(self.home / 'tcl-preproduction/private/first-install-state.json')
        run = Path(state['runDirectory'])
        self.assertEqual(state['status'], 'staged_closed')
        self.assertEqual((run / 'initial-root/web/.htaccess').read_bytes(), install.DENY)
        self.assertEqual((self.root / 'web/.htaccess').read_bytes(), install.DENY)
        for name, entry in self.manifest['files'].items():
            if name.endswith('web/.htaccess'):
                self.assertEqual(install.digest(run / 'candidate.htaccess'), entry['sha256'])
            else:
                self.assertEqual(install.digest(self.root / Path(name).relative_to('drupal')), entry['sha256'])
        with self.assertRaises(ValueError):
            self.staged()

    def test_interrupted_promotion_restores_only_original_empty_root(self):
        actual = Path.rename
        def rename(path, target):
            if path.name == 'drupal' and path.parent.name.startswith('first-install-'):
                raise OSError('fixture-interruption')
            return actual(path, target)
        with patch.object(Path, 'rename', rename), self.assertRaises(OSError):
            self.staged()
        self.assertEqual((self.root / 'web/.htaccess').read_bytes(), install.DENY)
        state = install.read_json(self.home / 'tcl-preproduction/private/first-install-state.json')
        self.assertEqual(state['status'], 'prepared_private')
        with self.assertRaises(ValueError):
            self.staged()

    def test_parent_symlink_is_refused(self):
        if os.name != 'posix':
            self.skipTest('POSIX link fixture')
        link = self.home / 'alias'
        link.symlink_to(self.root, target_is_directory=True)
        with self.assertRaises(ValueError):
            install.no_links(link / 'web/.htaccess')

    def test_no_secret_from_php_failure_is_disclosed_or_opens_apache(self):
        self.staged()
        secret = 'fixture-secret-not-to-print'
        failed = subprocess.CompletedProcess([], 1, ('TCL_RESULT:{"secret":"' + secret + '"}').encode(), secret.encode())
        with patch.object(install, 'validate_profile', return_value=(self.home, self.root)), \
             patch.object(install, 'runtime_gate'), patch.object(install.subprocess, 'run', return_value=failed):
            with self.assertRaises(ValueError) as error:
                install.php_operation('install', self.profile, self.qualification)
        self.assertNotIn(secret, str(error.exception))
        self.assertEqual((self.root / 'web/.htaccess').read_bytes(), install.DENY)

    def test_failed_fresh_check_keeps_apache_denied(self):
        self.staged()
        succeeded = subprocess.CompletedProcess([], 0, b'TCL_RESULT:{"installationExecuted":true}\n', b'')
        failed = subprocess.CompletedProcess([], 1, b'fixture-secret', b'fixture-secret')
        with patch.object(install, 'validate_profile', return_value=(self.home, self.root)), \
             patch.object(install, 'runtime_gate'), patch.object(install.subprocess, 'run', side_effect=[succeeded, failed]):
            with self.assertRaises(ValueError):
                install.php_operation('install', self.profile, self.qualification)
        self.assertEqual((self.root / 'web/.htaccess').read_bytes(), install.DENY)

    def php_receipt(self):
        result = {'checkedAt': '2026-10-04T00:00:00Z', 'drupalVersion': '11.4.8', 'phpVersion': '8.3.33',
                  'environment': 'preproduction', 'maintenanceEnabled': True, 'tclSiteEnabled': True,
                  'mailNeutralized': True, 'registration': 'admin_only', 'cronAutorunDisabled': True,
                  'databaseConnectionVerified': True, 'sqlVersion': '11.4.13-MariaDB',
                  'installationExecuted': True, 'publicOpeningExecuted': False}
        return result

    def test_unexpected_sensitive_field_in_success_receipt_keeps_apache_denied(self):
        self.staged()
        installed = subprocess.CompletedProcess([], 0, b'TCL_RESULT:{"installationExecuted":true}\n', b'')
        record = {**self.php_receipt(), 'password': 'fixture-secret'}
        checked = subprocess.CompletedProcess([], 0, ('TCL_RESULT:' + json.dumps(record)).encode(), b'')
        with patch.object(install, 'validate_profile', return_value=(self.home, self.root)), \
             patch.object(install, 'runtime_gate'), patch.object(install.subprocess, 'run', side_effect=[installed, checked]):
            with self.assertRaises(ValueError) as error:
                install.php_operation('install', self.profile, self.qualification)
        self.assertNotIn('fixture-secret', str(error.exception))
        self.assertEqual((self.root / 'web/.htaccess').read_bytes(), install.DENY)

    def test_verified_maintenance_preserves_core_rules_and_allows_repeated_checks(self):
        self.staged()
        record = self.php_receipt()
        checked = subprocess.CompletedProcess([], 0, ('TCL_RESULT:' + json.dumps(record)).encode(), b'')
        with patch.object(install, 'validate_profile', return_value=(self.home, self.root)), \
             patch.object(install, 'runtime_gate'), patch.object(install.subprocess, 'run', return_value=checked):
            result = install.php_operation('install', self.profile, self.qualification)
            self.assertFalse(result['publicOpeningExecuted'])
            install.php_operation('check', self.profile, self.qualification)
            install.php_operation('check', self.profile, self.qualification)
        state = install.read_json(self.home / 'tcl-preproduction/private/first-install-state.json')
        run = Path(state['runDirectory'])
        active_rules = (self.root / 'web/.htaccess').read_bytes()
        self.assertTrue(active_rules.startswith((run / 'candidate.htaccess').read_bytes()))
        self.assertIn(b'install|update|settings', active_rules)
        self.assertEqual(state['status'], 'installed_maintenance')
        self.assertFalse(state['publicOpeningExecuted'])
        self.assertEqual(len(list(run.glob('check-receipt-*.json'))), 2)

    def test_python_sources_parse_for_qualified_36_runtime(self):
        for name in ('first_install.py', 'delivery_shared.py', 'prepare_delivery.py'):
            source = (install.HERE / name).read_text(encoding='utf-8')
            # Annotations future is unsupported before 3.7, even with ast feature_version.
            self.assertNotIn('from __future__ import annotations', source)
            ast.parse(source, filename=name, feature_version=(3, 6))


if __name__ == '__main__':
    unittest.main()
