"""Recovery must rename table positions without changing private row values."""
import importlib.util
from pathlib import Path
import unittest

spec = importlib.util.spec_from_file_location('tcl_support_update', Path(__file__).resolve().parents[1] / 'scripts/support-update-hosting.py')
update = importlib.util.module_from_spec(spec)
spec.loader.exec_module(update)


class RecoverySqlTest(unittest.TestCase):
    def test_tables_and_executable_comments_renamed_without_column_or_value_change(self):
        sql = "DROP TABLE IF EXISTS `users`;\nCREATE TABLE `users` (`users` text);\n/*!40000 ALTER TABLE `users` DISABLE KEYS */;\nINSERT INTO `users` VALUES ('CREATE TABLE `users`', 'INSERT INTO `users`', 'quote\\\' `users`');"
        result = update.rewrite_sql(sql, {'users'}, 'sr_123456789abc_')
        self.assertIn('CREATE TABLE `sr_123456789abc_users` (`users` text)', result)
        self.assertIn('ALTER TABLE `sr_123456789abc_users` DISABLE KEYS', result)
        self.assertIn("VALUES ('CREATE TABLE `users`', 'INSERT INTO `users`', 'quote\\\' `users`')", result)
    def test_unknown_table_refused(self):
        with self.assertRaises(ValueError): update.rewrite_sql('INSERT INTO `other` VALUES (1);', {'users'}, 'sr_123456789abc_')
    def test_dump_locks_use_only_recovery_tables(self):
        sql="LOCK TABLES `users` WRITE, `sessions` WRITE;\nINSERT INTO `users` VALUES ('LOCK TABLES `users` WRITE;');\nUNLOCK TABLES;"
        result=update.rewrite_sql(sql, {'users','sessions'}, 'sr_123456789abc_')
        self.assertIn('LOCK TABLES `sr_123456789abc_users` WRITE, `sr_123456789abc_sessions` WRITE;',result)
        self.assertIn("VALUES ('LOCK TABLES `users` WRITE;')",result)
        self.assertIn('UNLOCK TABLES;',result)
        with self.assertRaises(ValueError): update.rewrite_sql('LOCK TABLES `other` WRITE;', {'users'}, 'sr_123456789abc_')
    def test_database_wide_objects_refused(self):
        for sql in ['CREATE DATABASE `other`;', 'USE `other`;', 'CREATE VIEW `v` AS SELECT 1;', 'DROP DATABASE `other`;']:
            with self.subTest(sql=sql), self.assertRaises(ValueError): update.rewrite_sql(sql, {'users'}, 'sr_123456789abc_')
    def test_database_words_in_private_text_are_preserved(self):
        sql = "INSERT INTO `users` VALUES ('USE `other`;', 'CREATE DATABASE `other`;');"
        self.assertIn("VALUES ('USE `other`;', 'CREATE DATABASE `other`;')", update.rewrite_sql(sql, {'users'}, 'sr_123456789abc_'))


if __name__ == '__main__': unittest.main()
