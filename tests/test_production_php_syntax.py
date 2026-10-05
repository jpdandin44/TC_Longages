"""Compile private PHP fragments before any production import can use them."""
import ast
import os
from pathlib import Path
import shutil
import subprocess
import unittest


class ProductionPhpSyntax(unittest.TestCase):
    def test_private_runtime_preflight_and_drupal_check_compile(self):
        php = os.environ.get('TCL_PHP_BINARY') or shutil.which('php')
        if not php:
            self.skipTest('PHP CLI unavailable; this check runs on the Linux delivery-safety job.')
        module = ast.parse((Path(__file__).resolve().parent.parent / 'scripts/restore_production.py').read_text(encoding='utf-8'))
        fragments = []
        for node in ast.walk(module):
            if isinstance(node, ast.Assign) and isinstance(node.value, ast.Constant) and isinstance(node.value.value, str):
                names = [item.id for item in node.targets if isinstance(item, ast.Name)]
                if names in [['RUNTIME'], ['code'], ['BOOTSTRAP_CHECK']]:
                    code = node.value.value
                    fragments.append((names[0], code if code.startswith('<?php') else '<?php\n' + code))
        self.assertEqual(len(fragments), 3)
        for name, code in fragments:
            with self.subTest(fragment=name):
                result = subprocess.run([php, '-l'], input=code.encode('utf-8'),
                                        stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=10)
                self.assertEqual(result.returncode, 0, result.stdout.decode('utf-8', errors='replace'))


if __name__ == '__main__':
    unittest.main()
