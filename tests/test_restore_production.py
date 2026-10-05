"""SQL import boundaries with synthetic data only, never a live database."""
import gzip
import hashlib
from pathlib import Path
import sys
import tempfile
import unittest
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parent.parent / 'scripts'))
from restore_production import option_value, sql_payload


class ProductionSqlBoundaries(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.path = Path(self.temporary.name) / 'database.sql.gz'

    def sql(self, content):
        with gzip.open(str(self.path), 'wb') as stream:
            stream.write(content)
        digest = hashlib.sha256(self.path.read_bytes()).hexdigest()
        with patch('restore_production.SQL_SHA', digest):
            return sql_payload(self.path)

    def test_named_backup_inventory_and_bytes_are_bound(self):
        content = b'CREATE TABLE `fiction` (`id` int);\nINSERT INTO `fiction` VALUES (1);\n'
        self.assertEqual(self.sql(content), (content, 1))
        with self.assertRaisesRegex(ValueError, 'Reviewed SQL'):
            sql_payload(self.path)

    def test_foreign_database_global_rights_and_client_sources_rejected(self):
        for command in (b'USE other;', b'CREATE DATABASE other;', b'DROP DATABASE other;',
                        b'GRANT ALL ON *.*;', b'REVOKE SELECT;', b'CREATE USER other;',
                        b'SET GLOBAL x=1;', b'SOURCE /private;', b'LOAD DATA LOCAL INFILE x;'):
            with self.subTest(command=command), self.assertRaisesRegex(ValueError, 'database scope'):
                self.sql(b'CREATE TABLE `fiction` (`id` int);\n' + command + b'\n')

    def test_duplicate_or_missing_table_inventory_rejected(self):
        for content in (b'SELECT 1;', b'CREATE TABLE `fiction` (`id` int);\nCREATE TABLE `fiction` (`id` int);'):
            with self.subTest(content=content), self.assertRaisesRegex(ValueError, 'table inventory'):
                self.sql(content)

    def test_private_password_keeps_special_characters_without_option_injection(self):
        self.assertEqual(option_value('fiction"\nuser=root\t\\#'), '"fiction\\"\\nuser=root\\t\\\\#"')
        for value in ('', None, 'fiction\0'):
            with self.subTest(value=value), self.assertRaises(ValueError):
                option_value(value)


if __name__ == '__main__':
    unittest.main()
