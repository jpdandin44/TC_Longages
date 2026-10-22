"""Local Drupal + fictitious signed OAuth/PKCE provider; never a hosted OAuth proof."""
import datetime
import http.cookiejar
import html
import json
import pathlib
import re
import sqlite3
import subprocess
import urllib.error
import urllib.parse
import urllib.request
from html.parser import HTMLParser

ROOT = pathlib.Path(__file__).resolve().parents[1]
ORIGIN = 'http://127.0.0.1:4186'
CHECKS = []

class Inputs(HTMLParser):
    def __init__(self, body):
        super().__init__(); self.hidden = {}; self.feed(body)
    def handle_starttag(self, tag, attributes):
        values = dict(attributes)
        if tag == 'input' and values.get('type') == 'hidden' and values.get('name'):
            self.hidden[values['name']] = values.get('value', '')

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args): return None

class Session:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar), NoRedirect())
    def get(self, path, values=None):
        url = path if path.startswith('http://127.0.0.1:4190/') else ORIGIN + path
        assert url.startswith((ORIGIN + '/', 'http://127.0.0.1:4190/'))
        data = urllib.parse.urlencode(values).encode() if values is not None else None
        try: response = self.client.open(urllib.request.Request(url, data=data), timeout=20)
        except urllib.error.HTTPError as error: response = error
        return response.status, response.read().decode(), dict(response.headers)
    def form(self, path, values, body=None):
        body = body if body is not None else self.get(path)[1]
        return self.get(path, dict(Inputs(body).hidden, **values))
    def oauth(self, subject='bureau-fixture', mode='valid'):
        (ROOT / '.local/oauth-fixture-mode.json').write_text(json.dumps({'subject': subject, 'mode': mode}))
        start = self.get('/bureau/connexion')
        if start[0] != 303: return start
        authorize = self.get(start[2]['Location'])
        back = urllib.parse.urlsplit(authorize[2]['Location'])
        return self.get(back.path + '?' + back.query)

def check(label, condition):
    CHECKS.append({'label': label, 'passed': bool(condition)})
    assert condition, label

def fixture(action):
    subprocess.run(['php', '-c', str(ROOT / '.local/drupal-tools/php.ini'), str(ROOT / 'tests/drupal-oauth-fixture-update.php'), action], check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

def run():
    anonymous = Session()
    for page in ['index', 'competitions', 'calendrier', 'disponibilites', 'equipes', 'espace', 'contact']:
        result = anonymous.get('/' + page + '.html')
        check('Bureau menu on ' + page, result[0] == 200 and 'href="/bureau/connexion">Bureau</a>' in result[1])
    check('V1 espace information page preserved', anonymous.get('/espace.html')[0] == 200)
    for path in ['/bureau', '/bureau/adherents', '/bureau/adherents/ajouter', '/bureau/comptes', '/bureau/communication']:
        check('direct URL requires OAuth ' + path, anonymous.get(path)[2].get('Location') == '/bureau/connexion')
    check('unauthenticated write rejected', anonymous.get('/bureau/adherents/ajouter', {'first_name': 'Forbidden'})[0] == 403)
    credentials = json.loads((ROOT / '.local/bureau-oauth-fixtures.json').read_text())
    native = Session(); native.form('/user/login', {'name': credentials['bureau']['username'], 'pass': credentials['bureau']['password']})
    check('native Drupal password cannot bypass OAuth', native.get('/bureau')[2].get('Location') == '/bureau/connexion')
    check('callback without transaction denied', Session().get('/bureau/oauth/retour?state=forged&code=forged')[0] == 403)
    other = Session(); start = other.get('/bureau/connexion'); authorize = other.get(start[2]['Location']); back = urllib.parse.urlsplit(authorize[2]['Location']); callback = back.path + '?' + back.query
    check('transaction bound to initiating browser', Session().get(callback)[0] == 403)
    check('valid initiating browser can finish OAuth', other.get(callback)[0] == 303)
    check('callback cannot be replayed', other.get(callback)[0] == 403)
    for mode in ['denied', 'wrong_issuer', 'wrong_audience', 'wrong_nonce', 'expired', 'missing_nonce', 'wrong_algorithm', 'wrong_signature', 'userinfo_mismatch', 'unavailable']:
        bad = Session()
        check('OAuth rejects ' + mode, bad.oauth(mode=mode)[0] == 403)
        check('failed OAuth leaves Bureau closed ' + mode, bad.get('/bureau')[2].get('Location') == '/bureau/connexion')
    check('unknown identity is not provisioned', Session().oauth('unknown-fixture')[0] == 403)
    bureau = Session(); check('approved Bureau OAuth succeeds', bureau.oauth()[0] == 303)
    result = bureau.get('/bureau'); check('Bureau home usable', result[0] == 200 and 'Enregistrer un nouvel adhérent' in result[1])
    check('no-store noindex on private page', 'no-store' in result[2].get('Cache-Control', '') and 'noindex' in result[2].get('X-Robots-Tag', ''))
    path = '/bureau/adherents/ajouter'
    form = bureau.get(path)[1]
    values = {'first_name': 'RecetteOAuth', 'last_name': 'Fictif' + str(datetime.datetime.now().microsecond), 'season': '2026-2027', 'profile': 'adult', 'birth_date': '1990-01-01', 'email': 'fixture@example.invalid', 'phone': '0600000000', 'city': 'Exemple', 'formula': 'Fiche reçue', 'training': 'unknown', 'previous_licence': 'unknown', 'status': 'received', 'notes': 'Données fictives uniquement.'}
    result = bureau.form(path, values, form)
    check('OAuth Bureau saves membership dossier', result[0] == 303 and '/bureau/adherents' in result[2].get('Location', ''))
    overview = bureau.get('/bureau/adherents?recherche=RecetteOAuth')[1]
    match = re.search(r'href="((?:/fr)?/bureau/adherents/\d+/modifier)"', overview)
    check('saved member is searchable', match is not None and values['last_name'] in overview)
    edit = match.group(1); old = bureau.get(edit)[1]
    check('OAuth Bureau edits dossier', bureau.form(edit, dict(values, status='checked', notes='Vérifié en recette.'), old)[0] == 303)
    check('stale revision cannot overwrite dossier', 'a changé depuis' in bureau.form(edit, dict(values, notes='Old overwrite'), old)[1])
    invalid = bureau.form(edit, dict(values, form_token='invalid', notes='No CSRF write'))
    check('CSRF protection retained', invalid[0] in (200, 400, 403) and 'No CSRF write' not in bureau.get(edit)[1])
    captain = Session(); check('approved captain authenticates', captain.oauth('captain-fixture')[0] == 303)
    check('captain cannot read membership dossiers', captain.get('/bureau/adherents')[0] == 403)
    check('captain cannot modify membership dossiers', captain.get(edit, values)[0] == 403)
    fixture('block')
    try: check('blocking account revokes current OAuth access', bureau.get('/bureau')[2].get('Location') == '/bureau/connexion')
    finally: fixture('unblock')
    fixture('remove-role')
    try: check('removing role revokes current OAuth access', bureau.get('/bureau')[2].get('Location') == '/bureau/connexion')
    finally: fixture('restore-role')
    db = sqlite3.connect(ROOT / '.local/drupal-runtime/site.sqlite')
    check('dossier persisted in private SQLite', db.execute('SELECT status FROM tcl_bureau_registration WHERE first_name=? AND last_name=?', (values['first_name'], values['last_name'])).fetchone()[0] == 'checked')
    db.close()
    fixture('revoke-identity')
    try: check('removing explicit association revokes current access', bureau.get('/bureau')[2].get('Location') == '/bureau/connexion')
    finally: fixture('restore-settings')
    fixture('disable')
    try:
        closed = Session().get('/bureau/connexion')
        check('missing configuration fails closed with clear response', closed[0] == 503 and 'préparation' in closed[1] and 'client_secret' not in closed[1])
        check('disabled configuration denies existing session', bureau.get('/bureau')[2].get('Location') == '/bureau/connexion')
    finally: fixture('restore-settings')
    for action in ['expire-idle', 'expire-absolute']:
        expired = Session(); check('prepare ' + action, expired.oauth()[0] == 303)
        fixture(action)
        check('session deadline enforced ' + action, expired.get('/bureau')[2].get('Location') == '/bureau/connexion')
        check('expired session cannot write ' + action, expired.get(edit, values)[0] == 403)
    logout = Session(); check('prepare logout', logout.oauth()[0] == 303)
    page = logout.get('/bureau')[1]
    link = re.search(r'href="([^"]*/user/logout[^\"]*)"', page)
    check('native logout link available', link is not None)
    logout.get(html.unescape(link.group(1)))
    check('logout removes Bureau access', logout.get('/bureau')[2].get('Location') == '/bureau/connexion')
    return bureau

if __name__ == '__main__':
    report = {'checkedAt': datetime.datetime.now(datetime.timezone.utc).isoformat(), 'scope': 'local Drupal + fictitious OAuth provider only', 'hostedOAuthVerified': False, 'fictitiousDataOnly': True, 'checks': CHECKS}
    try: run(); report['passed'] = True
    except Exception as error:
        report['passed'] = False; report['error'] = str(error) if isinstance(error, AssertionError) else type(error).__name__
    (ROOT / '.local/bureau-oauth-http-results.json').write_text(json.dumps(report, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(json.dumps({'passed': report['passed'], 'checks': len(CHECKS), 'error': report.get('error')}))
    raise SystemExit(0 if report['passed'] else 1)
