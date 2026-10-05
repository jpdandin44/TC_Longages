"""Private restore checks on fictitious backups, without credentials or SQL writes."""
import hashlib
import json
from pathlib import Path
import sys
import tarfile
import tempfile
import unittest

sys.path.insert(0, str(Path(__file__).resolve().parent.parent / 'scripts'))
from backup_site import archive_sources, sha256
from restore_backup_files import entry_path, restore_files


class RestoreFilesBoundaries(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.backup = self.root / 'backup'
        self.backup.mkdir()
        self.source = self.root / 'original'
        self.source.mkdir()
        (self.source / 'file.php').write_bytes(b'fictitious Drupal')
        (self.backup / 'database.sql.gz').write_bytes(b'fictitious private SQL')
        inventory = archive_sources(self.backup / 'files.tar.gz', {'drupal': self.source})
        self.sql_sha = sha256(self.backup / 'database.sql.gz')
        self.files_sha = sha256(self.backup / 'files.tar.gz')
        self.receipt = {'format': 'tcl-private-backup-v1', 'integrityVerified': True,
            'sqlSha256': self.sql_sha, 'filesSha256': self.files_sha, 'inventory': inventory}
        self.write_receipt()

    def write_receipt(self):
        (self.backup / 'receipt.json').write_text(json.dumps(self.receipt))

    def restore(self):
        return restore_files(self.backup, self.root, self.sql_sha, self.files_sha)

    def test_each_byte_restored_privately_without_sql_claim_or_original_change(self):
        result = self.restore()
        restored = Path(result['directory']) / 'drupal/file.php'
        self.assertEqual(restored.read_bytes(), (self.source / 'file.php').read_bytes())
        self.assertTrue(result['filesRestoredAndVerified'])
        self.assertFalse(result['databaseRestored'])
        self.assertFalse(result['restorationTested'])
        self.assertFalse(result['deploymentExecuted'])
        self.assertNotEqual(self.restore()['directory'], result['directory'])

    def test_substitution_rejected_before_creating_copy(self):
        (self.backup / 'database.sql.gz').write_bytes(b'different SQL')
        with self.assertRaisesRegex(ValueError, 'bytes differ'):
            self.restore()
        self.assertEqual(list(self.root.glob('restore-*')), [])

    def test_absolute_traversal_unknown_prefix_and_normalization_rejected(self):
        for name in ('/drupal/file', 'drupal/../file', 'drupal//file', 'drupal/./file',
                     'other/file', 'drupal\\file', 'drupal'):
            with self.subTest(name=name), self.assertRaises(ValueError):
                entry_path(name)

    def test_inventory_mismatch_and_links_rejected(self):
        self.receipt['inventory']['drupal/missing'] = {'bytes': 0, 'sha256': hashlib.sha256(b'').hexdigest()}
        self.write_receipt()
        with self.assertRaisesRegex(ValueError, 'inventory mismatch'):
            self.restore()
        archive = self.backup / 'files.tar.gz'
        with tarfile.open(str(archive), 'w:gz') as stream:
            entry = tarfile.TarInfo('drupal/file.php')
            entry.type = tarfile.SYMTYPE
            entry.linkname = '/outside'
            stream.addfile(entry)
        self.files_sha = sha256(archive)
        self.receipt['filesSha256'] = self.files_sha
        self.receipt['inventory'] = {'drupal/file.php': {'bytes': 0, 'sha256': hashlib.sha256(b'').hexdigest()}}
        self.write_receipt()
        with self.assertRaisesRegex(ValueError, 'Non-regular'):
            self.restore()


if __name__ == '__main__':
    unittest.main()
