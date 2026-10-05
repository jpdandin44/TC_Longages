"""Backup integrity boundaries on fictitious files; no host or SQL credentials."""
import hashlib
from pathlib import Path
import sys
import tempfile
import unittest
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parent.parent / 'scripts'))
from backup_site import archive_sources, qualify, regular_tree


class BackupBoundaries(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.source = self.root / 'site'
        self.source.mkdir()
        (self.source / 'file').write_bytes(b'fictitious source')
        self.output = self.root / 'backup.tar.gz'

    def test_archive_rereads_every_byte_and_preserves_originals(self):
        inventory = archive_sources(self.output, {'drupal': self.source})
        self.assertEqual(inventory['drupal/file']['sha256'], hashlib.sha256(b'fictitious source').hexdigest())
        self.assertEqual((self.source / 'file').read_bytes(), b'fictitious source')
        previous = self.output.read_bytes()
        with self.assertRaisesRegex(ValueError, 'already exists'):
            archive_sources(self.output, {'drupal': self.source})
        self.assertEqual(self.output.read_bytes(), previous)

    def test_wrong_account_roots_and_runtime_rejected_before_dump(self):
        for account in ('root', 'avereo', 'daje1234;id'):
            with self.subTest(account=account), self.assertRaises(ValueError):
                qualify(account, self.source, self.root, self.root, self.source)
        with self.assertRaisesRegex(ValueError, 'Wrong backup target'):
            qualify('daje1234', self.source, self.root, self.root, self.source)

    def test_invalid_prefix_and_changing_source_rejected(self):
        with self.assertRaisesRegex(ValueError, 'Invalid backup prefix'):
            archive_sources(self.output, {'../private': self.source})
        with patch('backup_site.sha256', side_effect=['a'*64, 'b'*64]):
            with self.assertRaisesRegex(ValueError, 'changed during snapshot'):
                archive_sources(self.root / 'changed.tar.gz', {'drupal': self.source})

    def test_links_cannot_capture_outside_files(self):
        link = self.source / 'linked'
        try:
            link.symlink_to(self.root / 'outside')
        except OSError:
            self.skipTest('Host does not permit test symlink creation.')
        with self.assertRaisesRegex(ValueError, 'Link or special'):
            regular_tree(self.source)


if __name__ == '__main__':
    unittest.main()
