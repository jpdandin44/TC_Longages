"""Production preparation refuses wrong accounts, paths, SQL and opening scope."""
import copy
from pathlib import Path
import sys
import unittest

sys.path.insert(0, str(Path(__file__).resolve().parent.parent / 'scripts'))
from stage_production import qualify_profile


class ProductionScope(unittest.TestCase):
    def setUp(self):
        self.profile = {'project': 'tclongages', 'environment': 'production', 'account': 'daje5127',
            'sourceCommit': 'a'*40, 'archiveSha256': 'b'*64, 'manifestSha256': 'c'*64,
            'composerRoot': '/home2/daje5127/tcl-production/releases/'+'b'*16+'/drupal',
            'documentRoot': '/home2/daje5127/tcl-production/releases/'+'b'*16+'/drupal/web',
            'privateRoot': '/home2/daje5127/tcl-production/private', 'database': 'daje5127_tclprod',
            'databaseUser': 'daje5127_tclprod', 'targetHost': 'tclongages.fr',
            'authorizationRef': 'fictitious-test-only', 'officialRootChangeAllowed': False,
            'publicOpeningAllowed': False}

    def test_reviewed_scope_accepts_only_separate_unserved_root(self):
        home, release = qualify_profile(self.profile)
        self.assertEqual(home.as_posix(), '/home2/daje5127')
        self.assertTrue(release.as_posix().endswith('/'+'b'*16))

    def test_existing_preproduction_other_account_and_public_root_refused(self):
        for key, value in [('composerRoot', '/home2/daje5127/tcl-preproduction/drupal'),
                           ('documentRoot', '/home2/daje5127/public_html'),
                           ('privateRoot', '/home2/avereo/private'), ('account', 'root'),
                           ('database', 'daje5127_tclpreprod'), ('databaseUser', 'daje5127_tcl')]:
            profile = copy.deepcopy(self.profile)
            profile[key] = value
            with self.subTest(key=key), self.assertRaises(ValueError):
                qualify_profile(profile)

    def test_preparation_cannot_grant_bascule_or_public_opening(self):
        for key, value in [('officialRootChangeAllowed', True), ('publicOpeningAllowed', True),
                           ('authorizationRef', ''), ('environment', 'preproduction'),
                           ('targetHost', 'preprod.tclongages.fr'), ('sourceCommit', 'a'*7)]:
            profile = copy.deepcopy(self.profile)
            profile[key] = value
            with self.subTest(key=key), self.assertRaises(ValueError):
                qualify_profile(profile)


if __name__ == '__main__':
    unittest.main()
