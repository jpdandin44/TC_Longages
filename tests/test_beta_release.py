import json
from pathlib import Path
import shutil
import sys
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / 'scripts'))
from verify_beta_release import ARTIFACT, SNAPSHOT, freeze, source_name, verify_sources


class BetaReleaseTests(unittest.TestCase):
    def test_safe_source_and_generated_dependency(self):
        self.assertTrue(source_name('drupal/web/modules/custom/tcl_site/tcl_site.module'))
        self.assertFalse(source_name('drupal/web/core/lib/Drupal.php'))
        self.assertFalse(source_name('drupal/vendor/autoload.php'))

    def test_paths_and_runtime_are_rejected(self):
        for name in ('../settings.php', '/drupal/composer.lock', 'drupal/../secret',
                     'drupal\\composer.lock', 'drupal/web/sites/default/settings.php',
                     'drupal/web/sites/default/services.yml',
                     'drupal/web/sites/default/files/report.txt',
                     'drupal/web/modules/custom/tcl_bureau/tcl_bureau.module'):
            with self.subTest(name=name), self.assertRaises(ValueError):
                source_name(name)

    def test_freeze_cannot_overwrite(self):
        with tempfile.TemporaryDirectory() as tmp:
            with self.assertRaises(ValueError):
                freeze(Path('missing.zip'), Path(tmp))

    def test_frozen_source_matches_beta(self):
        result = verify_sources()
        self.assertEqual(result['frozenSourceFiles'], 65)
        self.assertEqual(result['historicalModuleFilesMatched'], 33)
        self.assertFalse(result['humanAcceptanceInferred'])

    def test_manifest_does_not_authorize_deployment(self):
        value = json.loads((SNAPSHOT / 'source-binding.json').read_text())
        self.assertEqual(value['artifactSha256'], ARTIFACT)
        self.assertFalse(value['deploymentAllowed'])
        self.assertFalse(value['publicOpeningAuthorized'])

    def test_wrong_binding_refused(self):
        real_loads = json.loads
        def changed(raw, *args, **kwargs):
            value = real_loads(raw, *args, **kwargs)
            value['artifactSha256'] = '0' * 64
            return value
        with patch('verify_beta_release.json.loads', side_effect=changed):
            with self.assertRaises(ValueError):
                verify_sources()

    def test_changed_source_and_matching_inventory_still_refused(self):
        import hashlib
        with tempfile.TemporaryDirectory() as tmp:
            destination = Path(tmp) / 'snapshot'
            shutil.copytree(SNAPSHOT, destination)
            name = 'drupal/web/index.php'
            source = destination / 'source' / name
            source.write_bytes(b'<?php echo "altered";')
            contract_path = destination / 'source-binding.json'
            value = json.loads(contract_path.read_text())
            value['files'][name] = {'bytes': source.stat().st_size,
                                    'sha256': hashlib.sha256(source.read_bytes()).hexdigest()}
            contract_path.write_text(json.dumps(value))
            with self.assertRaisesRegex(ValueError, 'contract differs'):
                verify_sources(destination)


if __name__ == '__main__':
    unittest.main()
