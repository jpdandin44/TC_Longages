"""Regression for first password entry, limited to a new fictitious SQLite user."""
import datetime
import json
from pathlib import Path
import secrets
import subprocess
import urllib.parse
from html.parser import HTMLParser

from importlib.util import module_from_spec, spec_from_file_location

ROOT = Path(__file__).resolve().parents[1]
spec = spec_from_file_location('tc_recipe', ROOT / 'tests/drupal-tc-identity-http.py')
base = module_from_spec(spec)
spec.loader.exec_module(base)
CHECKS = []


class ResetForm(HTMLParser):
    def __init__(self, body):
        super().__init__()
        self.action = None
        self.feed(body)

    def handle_starttag(self, tag, attributes):
        attributes = dict(attributes)
        if tag == 'form' and attributes.get('id') == 'user-pass-reset':
            self.action = attributes.get('action')


def check(label, condition):
    if not condition:
        raise AssertionError(label)
    CHECKS.append({'label': label, 'passed': True})


def fixture(operation):
    result = subprocess.run(['php', '-c', str(ROOT / '.local/drupal-tools/php.ini'),
                             str(ROOT / 'tests/drupal-password-setup.php'), operation],
                            capture_output=True, timeout=30)
    if result.returncode:
        raise RuntimeError('Local fixture ' + operation + ' failed')


def run():
    fixture('prepare')
    try:
        account = json.loads((ROOT / '.local/password-setup-fixture.json').read_text())
        other = base.Session()
        credentials = json.loads((ROOT / '.local/drupal-admin.json').read_text())
        accepted = other.form('/user/login', {'name': credentials['username'], 'pass': credentials['password']})
        del credentials
        check('Other fictitious Drupal account is signed in', accepted[0] in (302, 303))
        other_profile = other.get('/user')[2].get('Location')
        check('Direct login shortcut reproduces the authenticated 403', other.get(account['url'] + '/login')[0] == 403)
        guarded = other.get(account['url'])
        check('Native entry handles another session without switching accounts', guarded[0] == 302
              and urllib.parse.urlsplit(guarded[2].get('Location', '')).path in ('/', '/fr')
              and other.get('/user')[2].get('Location') == other_profile)

        session = base.Session()
        response = session.get(account['url'])
        form_path = '/user/reset/' + str(account['uid'])
        check('Native entry redirects without leaking the reset secret', response[0] == 302
              and urllib.parse.urlsplit(response[2].get('Location', '')).path.removeprefix('/fr') == form_path)
        reset = session.get(response[2]['Location'])
        action = ResetForm(reset[1]).action
        check('Confirmation uses club account presentation and explicit password action', 'tcl-account-page' in reset[1] and 'Choisir mon mot de passe' in reset[1])
        check('Native confirmation form is accessible anonymously', reset[0] == 200
              and action is not None and action.endswith('/login'))
        response = session.form(action, {}, reset[1])
        location = response[2].get('Location', '')
        check('Native reset preserves the form token', response[0] == 302
              and urllib.parse.urlsplit(location).path.removeprefix('/fr') == '/user/' + str(account['uid']) + '/edit'
              and 'pass-reset-token' in urllib.parse.parse_qs(urllib.parse.urlsplit(location).query))
        check('Shortcut also refuses the newly authenticated target session', session.get(account['url'] + '/login')[0] == 403)
        fixture('refresh')
        account = json.loads((ROOT / '.local/password-setup-fixture.json').read_text())
        restarted = session.get(account['url'])
        check('Native entry logs out the matching session before restarting', restarted[0] == 302
              and restarted[2].get('Location') == account['url'])
        response = session.get(restarted[2]['Location'])
        check('Matching session reaches the native confirmation route', response[0] == 302
              and urllib.parse.urlsplit(response[2].get('Location', '')).path.removeprefix('/fr') == form_path)
        reset = session.get(response[2]['Location'])
        action = ResetForm(reset[1]).action
        check('Matching session can reopen the confirmation form', reset[0] == 200
              and action is not None and action.endswith('/login'))
        response = session.form(action, {}, reset[1])
        location = response[2].get('Location', '')
        check('Restarted native flow restores a valid edit token', response[0] == 302
              and urllib.parse.urlsplit(location).path.removeprefix('/fr') == '/user/' + str(account['uid']) + '/edit'
              and 'pass-reset-token' in urllib.parse.parse_qs(urllib.parse.urlsplit(location).query))
        body = session.get(location)[1]
        check('Password edit uses club account presentation', 'tcl-account-page' in body)
        check('First setup does not request the current password', 'name="current_pass"' not in body)
        password = secrets.token_hex(24)
        values = {'mail': 'admin-tc-local@example.invalid', 'pass[pass1]': password, 'pass[pass2]': password, 'op': 'Enregistrer'}
        duplicate = session.form(location, values, body)
        check('Existing club-style email remains unavailable', duplicate[0] == 200
              and 'name="mail"' in duplicate[1] and ('already taken' in duplicate[1] or 'd\u00e9j\u00e0 utilis' in duplicate[1]))
        values.update({'mail': 'password-setup@example.invalid', 'pass[pass1]': password, 'pass[pass2]': password})
        saved = session.form(location, values, duplicate[1])
        check('First password and distinct email save without current password', saved[0] in (302, 303))
        fixture('verify')
        check('Stored password and email verified', True)
        replay = base.Session().get(account['url'])
        check('Used reset link redirects to the new request form', replay[0] == 302
              and urllib.parse.urlsplit(replay[2].get('Location', '')).path.removeprefix('/fr') == '/user/password')
        login = base.Session()
        accepted = login.form('/user/login', {'name': 'tcl-password-setup-fixture', 'pass': password})
        check('New fictitious password can sign in', accepted[0] in (302, 303))
        check('Native sign-in still cannot bypass Bureau OAuth', login.get('/bureau')[2].get('Location') == '/bureau/connexion')
    finally:
        fixture('cleanup')


if __name__ == '__main__':
    report = {'observedAt': datetime.datetime.now(datetime.timezone.utc).isoformat(),
              'scope': 'loopback SQLite, new fictitious user only', 'hostedVerified': False, 'checks': CHECKS}
    try:
        run()
        report['passed'] = True
    except Exception as error:
        report['passed'] = False
        report['error'] = str(error) if isinstance(error, AssertionError) else type(error).__name__
    (ROOT / '.local/password-setup-results.json').write_text(json.dumps(report, indent=2) + '\n', encoding='utf-8')
    print(json.dumps({'passed': report['passed'], 'checks': len(CHECKS), 'error': report.get('error')}))
    raise SystemExit(0 if report['passed'] else 1)
