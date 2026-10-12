"""Real TC Simple OAuth on loopback, with a fictitious local Admin only."""
import datetime
import http.cookiejar
import importlib.util
import json
from pathlib import Path
import re
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
CONSUMER = 'http://127.0.0.1:4186'
PROVIDER = 'http://127.0.0.1:4191'
spec = importlib.util.spec_from_file_location('local_oauth_recipe', ROOT / 'tests/drupal-bureau-oauth-http.py')
base = importlib.util.module_from_spec(spec)
spec.loader.exec_module(base)
CHECKS = []

def check(label, condition):
    if not condition:
        raise AssertionError(label)
    CHECKS.append({'label': label, 'passed': True})

class Session:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar), base.NoRedirect())

    def get(self, path, values=None):
        url = path if path.startswith('http:') else CONSUMER + path
        if not url.startswith((CONSUMER + '/', PROVIDER + '/')):
            raise ValueError('Only the two loopback recipe servers are allowed.')
        data = None if values is None else urllib.parse.urlencode(values).encode()
        try:
            response = self.client.open(urllib.request.Request(url, data=data), timeout=25)
        except urllib.error.HTTPError as error:
            response = error
        return response.status, response.read().decode(), dict(response.headers)

    def form(self, path, values, body=None):
        body = self.get(path)[1] if body is None else body
        return self.get(path, dict(base.Inputs(body).hidden, **values))

    def code(self, account):
        start = self.get('/bureau/connexion')
        check('Menu Bureau selects the TC provider', start[0] == 303 and start[2]['Location'].startswith(PROVIDER + '/oauth/authorize?'))
        authorize = start[2]['Location']
        response = self.get(authorize)
        if response[0] == 302 and '/connexion-tc?' in response[2].get('Location', ''):
            login = response[2]['Location']
            login = PROVIDER + login if login.startswith('/') else login
            body = self.get(login)[1]
            check('Dedicated TC login screen', 'Connexion TC Longages' in body and 'Identifiant TC Longages' in body)
            response = self.form(login, {'name': account['username'], 'pass': account['password'], 'op': 'Se connecter'}, body)
            check('TC local credentials accepted', response[0] in (302, 303))
            # A native session alone must still not grant access to the Bureau.
            check('TC password alone does not bypass OAuth', self.get('/bureau')[2].get('Location') == '/bureau/connexion')
            response = self.get(authorize)
        check('Simple OAuth issues an authorization code', response[0] == 302 and response[2].get('Location', '').startswith(CONSUMER + '/bureau/oauth/retour?'))
        return response[2]['Location']

def run():
    account = json.loads((ROOT / '.local/tc-identity-local.json').read_text())
    config_file = ROOT / '.local/bureau-oauth-settings.php'
    check('Dedicated private provider settings', "'local_provider' => true" in config_file.read_text().lower())
    anonymous = Session()
    check('Anonymous Bureau is protected', anonymous.get('/bureau')[2].get('Location') == '/bureau/connexion')
    check('Anonymous write is refused', anonymous.get('/bureau/adherents/ajouter', {'last_name': 'Refused'})[0] == 403)
    for endpoint in ['/oauth/authorize', '/oauth/token']:
        response = anonymous.get(PROVIDER + endpoint, {} if endpoint.endswith('token') else None)
        check('Unconfigured OAuth request refused: ' + endpoint, response[0] == 400 and 'no-store' in response[2].get('Cache-Control', ''))
    admin = Session()
    callback = admin.code(account)
    response = admin.get(callback)
    check('Complete real Simple OAuth and strict signed nonce validation', response[0] == 303 and urllib.parse.urlsplit(response[2].get('Location', '')).path == '/bureau')
    page = admin.get('/bureau')
    check('First TC Admin reaches Bureau', page[0] == 200 and 'Enregistrer un nouvel adhérent' in page[1])
    check('Admin can manage accounts', admin.get('/bureau/comptes')[0] == 200)
    check('Admin can create Bureau accounts', 'Bureau' in admin.get('/bureau/comptes/ajouter')[1])
    check('Admin can manage registrations', admin.get('/bureau/adherents')[0] == 200)
    check('Admin can manage communication', admin.get('/bureau/communication')[0] == 200)
    pages = admin.get('/admin/content/tcl-pages')
    check('Admin retains native public content editing', pages[0] == 200 and 'Modifier' in pages[1])
    edit = re.search(r'href="(/node/\d+/edit[^\"]*)"', pages[1])
    check('Public editing form available', edit is not None and admin.get(__import__('html').unescape(edit[1]))[0] == 200)
    check('Local recipe is labelled accurately', 'OAuth TC Longages, comptes et données de test' in page[1])
    # Reuse the existing native membership flow, now with the real TC provider.
    form = admin.get('/bureau/adherents/ajouter')[1]
    values = {'season': '2026-2027', 'last_name': 'TCProvider' + datetime.datetime.now().strftime('%H%M%S'), 'first_name': 'Recette', 'profile': 'adult', 'birth_date': '1990-01-01', 'email': 'fictif@example.invalid', 'phone': '0600000000', 'city': 'Exemple', 'formula': 'Fiche reçue', 'training': 'unknown', 'previous_licence': 'unknown', 'status': 'received', 'notes': 'Fictif, fournisseur TC local.'}
    saved = admin.form('/bureau/adherents/ajouter', values, form)
    check('TC OAuth Admin saves a membership dossier', saved[0] == 303)
    overview = admin.get('/bureau/adherents?recherche=TCProvider')[1]
    check('TC dossier persists and is searchable', values['last_name'] in overview)
    replay = admin.get(callback)
    check('Callback cannot be replayed', replay[0] == 403)
    check('Replay invalidates old Bureau proof', admin.get('/bureau')[2].get('Location') == '/bureau/connexion')
    reconnected = admin.get(admin.code(account))
    check('TC Admin reconnects', reconnected[0] == 303)
    unassociated = Session()
    fixtures = json.loads((ROOT / '.local/bureau-oauth-fixtures.json').read_text())
    callback = unassociated.code(fixtures['bureau'])
    check('Same-site TC user without explicit association refused', unassociated.get(callback)[0] == 403)
    challenge = Session()
    start = challenge.get('/bureau/connexion')[2]['Location']
    query = urllib.parse.parse_qs(urllib.parse.urlsplit(start).query)
    query['code_challenge_method'] = ['plain']
    check('Provider rejects plain PKCE', challenge.get(PROVIDER + '/oauth/authorize?' + urllib.parse.urlencode(query, doseq=True))[0] == 400)
    query['code_challenge_method'] = ['S256']
    query['client_id'] = ['avereo-client-not-tc']
    check('An AVEREO client cannot use TC provider', challenge.get(PROVIDER + '/oauth/authorize?' + urllib.parse.urlencode(query, doseq=True))[0] == 400)
    check('No password grant', challenge.get(PROVIDER + '/oauth/token', {'client_id': 'tc-bureau-local-dedicated', 'grant_type': 'password'})[0] == 400)
    check('No application-only grant', challenge.get(PROVIDER + '/oauth/token', {'client_id': 'tc-bureau-local-dedicated', 'grant_type': 'client_credentials'})[0] == 400)
    # A genuine issued code is still bound to the PKCE verifier and client secret.
    code_session = Session()
    callback = code_session.code(account)
    code = urllib.parse.parse_qs(urllib.parse.urlsplit(callback).query)['code'][0]
    token_values = {'grant_type': 'authorization_code', 'client_id': 'tc-bureau-local-dedicated', 'client_secret': account['client_secret'], 'redirect_uri': CONSUMER + '/bureau/oauth/retour', 'code': code, 'code_verifier': 'wrong-verifier-' + 'a' * 50}
    check('Wrong verifier cannot redeem a real TC code', challenge.get(PROVIDER + '/oauth/token', token_values)[0] in (400, 401))
    token_values['client_secret'] = 'wrong-secret'
    check('Wrong secret cannot redeem a real TC code', challenge.get(PROVIDER + '/oauth/token', token_values)[0] in (400, 401))
    link = re.search(r'href="([^"]*/user/logout[^\"]*)"', admin.get('/bureau')[1])
    check('TC Admin can sign out', link is not None)
    admin.get(__import__('html').unescape(link[1]))
    check('Signing out removes TC Bureau access', admin.get('/bureau')[2].get('Location') == '/bureau/connexion')

if __name__ == '__main__':
    report = {'checkedAt': datetime.datetime.now(datetime.timezone.utc).isoformat(), 'scope': 'local Drupal Simple OAuth 6.1.1 dedicated TC provider, fictitious accounts only', 'hostedOAuthVerified': False, 'fictitiousDataOnly': True, 'checks': CHECKS}
    try:
        run()
        report['passed'] = True
    except Exception as error:
        report['passed'] = False
        report['error'] = str(error) if isinstance(error, AssertionError) else type(error).__name__
    (ROOT / '.local/tc-identity-http-results.json').write_text(json.dumps(report, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(json.dumps({'passed': report['passed'], 'checks': len(CHECKS), 'error': report.get('error')}))
    raise SystemExit(0 if report['passed'] else 1)
