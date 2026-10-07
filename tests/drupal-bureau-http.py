"""Real local Drupal forms/sessions/SQLite checks. No production URLs or secrets in output."""
import datetime
import http.cookiejar
import json
import pathlib
import secrets
import sqlite3
import sys
import urllib.error
import urllib.parse
import urllib.request
from html.parser import HTMLParser

ROOT = pathlib.Path(__file__).resolve().parents[1]
ORIGIN = 'http://127.0.0.1:4182'
REPORT = ROOT / '.local/v2-bureau-http-results.json'
CHECKS = []


class Inputs(HTMLParser):
    def __init__(self, body):
        super().__init__()
        self.hidden = {}
        self.feed(body)

    def handle_starttag(self, tag, attributes):
        values = dict(attributes)
        if tag == 'input' and values.get('type') == 'hidden' and values.get('name'):
            self.hidden[values['name']] = values.get('value', '')


class Session:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))

    def request(self, path, values=None):
        assert path.startswith('/') and not path.startswith('//')
        data = urllib.parse.urlencode(values, doseq=True).encode() if values is not None else None
        request = urllib.request.Request(ORIGIN + path, data=data)
        try:
            response = self.client.open(request, timeout=20)
        except urllib.error.HTTPError as error:
            response = error
        return response.status, response.read().decode('utf-8'), dict(response.headers), response.url

    def post_form(self, path, changes, body=None):
        if body is None:
            status, body, _, _ = self.request(path)
            assert status == 200, 'form unavailable'
        values = Inputs(body).hidden
        values.update(changes)
        return self.request(path, values)

    def login(self, fixture):
        result = self.post_form('/user/login', {'name': fixture['username'], 'pass': fixture['password']})
        assert result[0] == 200 and '/user/login' not in result[3], 'native local login failed'


def check(label, condition):
    CHECKS.append({'label': label, 'passed': bool(condition)})
    assert condition, label


def initial_teams_recipe():
    credentials = json.loads((ROOT / '.local/v2-local-accounts.json').read_text())
    fixtures = json.loads((ROOT / '.local/v2-local-team-ids.json').read_text())
    anonymous, bureau, captain_a, captain_b, admin = Session(), Session(), Session(), Session(), Session()
    admin.login(json.loads((ROOT / '.local/drupal-admin.json').read_text()))
    check('native administrator login', admin.request('/bureau')[0] == 200)
    for path in ['/bureau', '/bureau/comptes', '/bureau/equipes/ajouter', f"/bureau/equipes/{fixtures['A']}/modifier"]:
        status, body, _, _ = anonymous.request(path)
        check('anonymous denied ' + path, status in (403, 404) and 'Notes privées' not in body)
    check('self-registration closed', anonymous.request('/user/register')[0] == 403)
    for session, key in [(bureau, 'bureau'), (captain_a, 'capitaineA'), (captain_b, 'capitaineB')]:
        session.login(credentials[key])
        check('native password login ' + key, session.request('/bureau')[0] == 200)
    status, body, headers, _ = captain_a.request('/bureau')
    check('captain A sees assigned team only', status == 200 and 'Équipe A' in body and 'Équipe B' not in body and 'Notes privées de l’équipe B' not in body)
    check('private no-store/noindex headers', 'noindex' in headers.get('X-Robots-Tag', '') and 'no-store' in headers.get('Cache-Control', ''))
    denied_path = f"/bureau/equipes/{fixtures['B']}/modifier"
    check('direct URL to another team denied', captain_a.request(denied_path)[0] == 403)
    for path in ['/bureau/comptes', '/bureau/comptes/ajouter', '/bureau/equipes/ajouter', '/admin/people']:
        check('captain administrative route denied ' + path, captain_a.request(path)[0] == 403)
    path = f"/bureau/equipes/{fixtures['A']}/modifier"
    status, form, _, _ = captain_a.request(path)
    old_version = Inputs(form).hidden.get('team_version')
    check('captain form excludes assignment controls', status == 200 and 'name="captains[' not in form and 'name="category"' not in form)
    notes = 'Notes privées de l’équipe A — données fictives.'
    response = captain_a.post_form(path, {'notes': notes + ' Modification Capitaine.', 'name': 'Escalade interdite', 'category': 'Interdite', 'captains[]': credentials['capitaineB']['uid']}, form)
    check('captain saves notes in database', response[0] == 200 and 'Modification Capitaine.' in response[1] and 'Équipe A' in response[1] and 'Escalade interdite' not in response[1])
    check('captain cannot assign another captain by tampering', captain_b.request(path)[0] == 403)
    stale = captain_a.post_form(path, {'notes': 'Ancienne fiche à écraser', 'team_version': old_version}, form)
    check('stale form cannot overwrite newer notes', 'a changé depuis' in stale[1] and 'Modification Capitaine.' in captain_a.request('/bureau')[1])
    csrf = captain_a.post_form(path, {'notes': 'Écriture sans CSRF', 'form_token': 'invalid'})
    check('invalid CSRF rejected without writing', csrf[0] in (200, 403) and 'Écriture sans CSRF' not in captain_a.request('/bureau')[1])
    captain_a.post_form(path, {'notes': notes})
    bureau_uid = credentials['bureau']['uid']
    for uid in [1, bureau_uid]:
        check('Bureau cannot edit administrator or itself ' + str(uid), bureau.request(f'/bureau/comptes/{uid}/modifier')[0] == 403)
    check('Bureau cannot open native user administration', bureau.request('/admin/people')[0] == 403)
    create_path = '/bureau/comptes/ajouter'
    status, form, _, _ = bureau.request(create_path)
    check('Bureau role fixed to Capitaine', status == 200 and 'name="role"' not in form)
    short_name = 'recette-short-' + secrets.token_hex(4)
    short = bureau.post_form(create_path, {'name': short_name, 'mail': short_name + '@example.invalid', 'password[pass1]': 'short', 'password[pass2]': 'short', 'active': '1'})
    check('short password rejected without creating an account', '16 caractères' in short[1] and short_name not in bureau.request('/bureau/comptes')[1])
    name = 'recette-http-' + secrets.token_hex(4)
    password = secrets.token_hex(20)
    result = bureau.post_form(create_path, {'name': name, 'mail': name + '@example.invalid', 'password[pass1]': password, 'password[pass2]': password, 'role': 'tcl_bureau', 'active': '1'}, form)
    check('Bureau creates a native Capitaine account', result[0] == 200 and name in result[1] and '/bureau/comptes' in result[3])
    fresh = Session()
    fresh.login({'username': name, 'password': password})
    check('new account login persists in native database', fresh.request('/bureau')[0] == 200)
    check('forged Bureau role not applied', fresh.request('/bureau/comptes')[0] == 403)
    check('unassigned new captain has no private team', 'Aucune équipe ne vous est attribuée' in fresh.request('/bureau')[1])
    admin_name = 'bureau-http-' + secrets.token_hex(4)
    admin_password = secrets.token_hex(20)
    result = admin.post_form(create_path, {'name': admin_name, 'mail': admin_name + '@example.invalid', 'password[pass1]': admin_password, 'password[pass2]': admin_password, 'role': 'tcl_bureau', 'active': '1'})
    check('administrator creates a native Bureau account', result[0] == 200 and admin_name in result[1])
    new_bureau = Session()
    new_bureau.login({'username': admin_name, 'password': admin_password})
    check('new Bureau account can manage captains', new_bureau.request('/bureau/comptes')[0] == 200)
    check('new Bureau account cannot manage native administrators', new_bureau.request('/admin/people')[0] == 403)
    edit_path = f"/bureau/comptes/{credentials['capitaineA']['uid']}/modifier"
    try:
        block = bureau.post_form(edit_path, {'mail': 'capitaine-a-recette@example.invalid', 'password[pass1]': '', 'password[pass2]': ''})
        check('Bureau blocks account through native form', block[0] == 200 and '/bureau/comptes' in block[3])
        denied = captain_a.request('/bureau')
        check('existing session denied immediately after block', denied[0] in (403, 404) and 'Notes privées' not in denied[1])
    finally:
        bureau.post_form(edit_path, {'mail': 'capitaine-a-recette@example.invalid', 'password[pass1]': '', 'password[pass2]': '', 'active': '1'})
    # Test immediate assignment removal while the captain remains authenticated.
    captain_a = Session()
    captain_a.login(credentials['capitaineA'])
    try:
        bureau.post_form(path, {'name': 'Équipe A — recette', 'category': 'Exemple fictif', 'notes': notes})
        check('assignment revocation denies existing session', captain_a.request(path)[0] == 403 and 'Équipe A' not in captain_a.request('/bureau')[1])
    finally:
        bureau.post_form(path, {'name': 'Équipe A — recette', 'category': 'Exemple fictif', 'notes': notes, f"captains[{credentials['capitaineA']['uid']}]": str(credentials['capitaineA']['uid'])})
    check('assignment restored for continued local recipe', captain_a.request(path)[0] == 200)
    return {'createdFixtureAccount': name, 'liveProductionModified': False, 'fictitiousDataOnly': True}


def run():
    """Current priority: private Bureau enrolment, accounts and deferred teams."""
    credentials = json.loads((ROOT / '.local/v2-local-accounts.json').read_text())
    anonymous, bureau, captain, admin = Session(), Session(), Session(), Session()
    for path in ['/bureau', '/bureau/adherents', '/bureau/adherents/ajouter', '/bureau/adherents/1/modifier', '/bureau/comptes']:
        check('anonymous internal route denied ' + path, anonymous.request(path)[0] == 403)
    check('self-registration closed', anonymous.request('/user/register')[0] == 403)
    bureau.login(credentials['bureau'])
    captain.login(credentials['capitaineA'])
    admin.login(json.loads((ROOT / '.local/drupal-admin.json').read_text()))
    check('Bureau home focuses on new members', 'Nouveaux adhérents' in bureau.request('/bureau')[1])
    for path in ['/bureau/adherents', '/bureau/adherents/ajouter', '/bureau/adherents/1/modifier', '/bureau/comptes']:
        check('captain cannot read or write enrolments ' + path, captain.request(path)[0] == 403)
    for session, role in [(bureau, 'Bureau'), (admin, 'administrator')]:
        check('team management deferred for ' + role, session.request('/bureau/equipes/ajouter')[0] == 403)
    stamp = secrets.token_hex(4)
    member = {'first_name': 'Camille', 'last_name': 'Exemple-fictif-' + stamp, 'birth_date': '1990-05-12', 'season': '2026-2027', 'profile': 'adult', 'email': 'camille-' + stamp + '@example.invalid', 'phone': '', 'address': 'Adresse fictive', 'postal_code': '31410', 'city': 'Longages', 'guardian_name': '', 'guardian_email': '', 'guardian_phone': '', 'formula': 'Adhésion loisirs — recette', 'training': 'no', 'previous_licence': 'unknown', 'licence_number': '', 'status': 'received', 'notes': 'Dossier fictif de recette.'}
    def records():
        with sqlite3.connect(ROOT / '.local/drupal-runtime/site.sqlite') as connection:
            return connection.execute('SELECT id,revision,details FROM tcl_bureau_registration WHERE last_name=?', (member['last_name'],)).fetchall()
    path = '/bureau/adherents/ajouter'
    status, form, _, _ = bureau.request(path)
    check('Bureau opens a real native registration form', status == 200 and 'tcl_bureau_registration_form' in form)
    missing = dict(member, first_name='', email='', phone='')
    failed = bureau.post_form(path, missing)
    check('required identity/contact fields reject incomplete record', failed[0] == 200 and not records())
    whitespace = bureau.post_form(path, dict(member, email='', phone='   '))
    check('whitespace contact rejected before writing', whitespace[0] == 200 and not records())
    csrf = bureau.post_form(path, dict(member, form_token='invalid'))
    check('invalid registration CSRF does not write', csrf[0] in (200, 403) and not records())
    result = bureau.post_form(path, member, form)
    rows = records()
    check('adult record persisted in real SQLite', result[0] == 200 and '/bureau/adherents' in result[3] and len(rows) == 1)
    bureau.post_form(path, member, form)
    check('repeated submission does not duplicate a member', len(records()) == 1)
    duplicate = bureau.post_form(path, member)
    check('another form detects same person and season', 'existe déjà' in duplicate[1] and len(records()) == 1)
    registration_id = rows[0][0]
    edit_path = f'/bureau/adherents/{registration_id}/modifier'
    check('anonymous cannot read actual record URL', anonymous.request(edit_path)[0] == 403)
    check('captain cannot read actual record URL', captain.request(edit_path)[0] == 403)
    status, old_form, _, _ = bureau.request(edit_path)
    update = dict(member, status='to_complete', notes='<script>window.unwanted=1</script> Notes fictives.')
    result = bureau.post_form(edit_path, update, old_form)
    check('Bureau updates the record and its revision', result[0] == 200 and records()[0][1] == 2)
    display = bureau.request(edit_path)[1]
    check('notes rendered as text without executable script', '&lt;script&gt;' in display and '<script>window.unwanted=1</script>' not in display)
    stale = bureau.post_form(edit_path, dict(member, notes='Ancienne fiche à écraser'), old_form)
    check('stale revision refused without overwriting record', 'a changé depuis' in stale[1] and records()[0][1] == 2)
    search = bureau.request('/bureau/adherents?recherche=' + urllib.parse.quote(member['last_name']))
    check('private member search works', member['last_name'] in search[1])
    # A minor needs a named guardian and usable contact, without inventing consent.
    minor = dict(member, first_name='Alex', last_name='Mineur-fictif-' + stamp, birth_date='2015-04-15', profile='minor', email='', guardian_name='', guardian_email='')
    failed = bureau.post_form(path, minor)
    check('minor guardian and contact are required on server', 'responsable légal' in failed[1] and 'Le dossier est enregistré' not in failed[1])
    minor.update(guardian_name='Responsable fictif', guardian_email='responsable-' + stamp + '@example.invalid')
    result = bureau.post_form(path, minor)
    check('minor registration with guardian persists', '/bureau/adherents' in result[3] and minor['last_name'] in result[1])
    check('Bureau cannot edit administrator', bureau.request('/bureau/comptes/1/modifier')[0] == 403)
    check('Bureau cannot edit itself', bureau.request(f"/bureau/comptes/{credentials['bureau']['uid']}/modifier")[0] == 403)
    check('Bureau cannot open native user administration', bureau.request('/admin/people')[0] == 403)
    created_name = 'capitaine-http-' + stamp
    password = secrets.token_hex(20)
    account = {'name': created_name, 'mail': created_name + '@example.invalid', 'password[pass1]': password, 'password[pass2]': password, 'role': 'tcl_bureau', 'active': '1'}
    result = bureau.post_form('/bureau/comptes/ajouter', account)
    check('Bureau creates native Capitaine account', created_name in result[1] and '/bureau/comptes' in result[3])
    new_captain = Session()
    new_captain.login({'username': created_name, 'password': password})
    check('forged Bureau role is not applied', new_captain.request('/bureau/adherents')[0] == 403)
    bureau_name = 'bureau-http-' + stamp
    result = admin.post_form('/bureau/comptes/ajouter', dict(account, name=bureau_name, mail=bureau_name + '@example.invalid', role='tcl_bureau'))
    check('administrator creates native Bureau account', bureau_name in result[1])
    new_bureau = Session()
    new_bureau.login({'username': bureau_name, 'password': password})
    check('new Bureau can use enrolment interface', new_bureau.request('/bureau/adherents')[0] == 200)
    block_path = f"/bureau/comptes/{credentials['capitaineA']['uid']}/modifier"
    try:
        bureau.post_form(block_path, {'mail': 'capitaine-a-recette@example.invalid', 'password[pass1]': '', 'password[pass2]': ''})
        check('blocking account denies its existing session', captain.request('/bureau')[0] == 403)
    finally:
        bureau.post_form(block_path, {'mail': 'capitaine-a-recette@example.invalid', 'password[pass1]': '', 'password[pass2]': '', 'active': '1'})
    status, body, headers, _ = bureau.request('/bureau/adherents')
    check('private responses remain noindex and no-store', status == 200 and 'noindex' in headers.get('X-Robots-Tag', '') and 'no-store' in headers.get('Cache-Control', ''))
    return {'priority': 'Bureau enrolment of new members', 'liveProductionModified': False, 'fictitiousDataOnly': True, 'teamManagementEnabled': False}


if __name__ == '__main__':
    report = {'checkedAt': datetime.datetime.now(datetime.timezone.utc).isoformat(), 'scope': 'local native Drupal HTTP/forms/SQLite only', 'origin': ORIGIN, 'checks': CHECKS}
    try:
        report.update(run())
        report['passed'] = True
    except Exception as error:
        report['passed'] = False
        # Labels, never request payloads, cookies or passwords.
        report['error'] = str(error) if isinstance(error, AssertionError) else type(error).__name__
    REPORT.write_text(json.dumps(report, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(json.dumps({'passed': report['passed'], 'checks': len(CHECKS), 'report': '.local/v2-bureau-http-results.json', 'error': report.get('error')}))
    sys.exit(0 if report['passed'] else 1)
