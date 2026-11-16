"""Loopback-only catalogue and two-step registration with fictitious identities."""
import importlib.util
import json
import pathlib
import secrets
import sqlite3
import subprocess
from datetime import datetime, timezone

ROOT = pathlib.Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('oauth_recipe', ROOT / 'tests/drupal-bureau-oauth-http.py')
oauth = importlib.util.module_from_spec(spec)
spec.loader.exec_module(oauth)
checks = []


def check(label, ok):
    checks.append({'label': label, 'passed': bool(ok)})
    assert ok, label


def setup(mode):
    subprocess.run(['php', '-c', str(ROOT / '.local/drupal-tools/php.ini'), str(ROOT / 'tests/registration-recipe-setup.php'), mode], check=True, stdout=subprocess.DEVNULL)


def run():
    anonymous, bureau, captain = oauth.Session(), oauth.Session(), oauth.Session()
    setup('disable')
    check('Registration closed without recipe flag', anonymous.get('/adherer')[0] == 403)
    setup('enable')
    check('Bureau OAuth connects', bureau.oauth()[0] == 303)
    check('Captain OAuth connects', captain.oauth('captain-fixture')[0] == 303)
    check('Anonymous cannot mutate catalogue', anonymous.get('/bureau/saison', {'season': '2026-2027'})[0] == 403)
    check('Captain cannot mutate catalogue', captain.get('/bureau/saison', {'season': '2026-2027'})[0] == 403)
    fields = {'season': '2026-2027', 'notice': 'Texte fictif de recette <script>alert(1)</script>', 'slots': '', 'save': 'Enregistrer le brouillon'}
    for i, (identity, label, profile, price) in enumerate([('adult-test', 'Formule adulte fictive', 'adult', '70,00'), ('minor-test', 'Formule mineur fictive', 'minor', '40,00')]):
        for key, value in {'id': identity, 'label': label, 'profile': profile, 'price': price}.items():
            fields[f'formulas[{i}][{key}]'] = value
    # Ignore the rest of an existing catalogue by explicitly clearing displayed rows.
    body = bureau.get('/bureau/saison')[1]
    import re
    for i in set(re.findall(r'name="formulas\[(\d+)\]\[id\]"', body)) - {'0', '1'}:
        for key in ['id', 'label', 'price']:
            fields[f'formulas[{i}][{key}]'] = ''
        fields[f'formulas[{i}][profile]'] = 'adult'
    check('Catalogue saves draft', bureau.form('/bureau/saison', fields, body)[0] == 303)
    stale = bureau.get('/bureau/saison')[1]
    publish = dict(fields); publish.pop('save'); publish.update({'publish': 'Publier pour la recette', 'confirm': '1'})
    check('Bad catalogue CSRF rejected', bureau.form('/bureau/saison', dict(publish, form_token='forged'))[0] != 303)
    check('Catalogue publishes explicitly', bureau.form('/bureau/saison', publish)[0] == 303)
    check('Stale catalogue cannot overwrite', 'catalogue a changé' in bureau.form('/bureau/saison', fields, stale)[1])
    body = anonymous.get('/adherer')[1]
    check('Published options visible', 'Formule adulte fictive' in body and '70,00' in body)
    home = anonymous.get('/')[1]
    if 'id="adherer"' in home:
        check('Editorial homepage links to published recipe season', 'id="tcl-registration-entry"' in home and '2026-2027' in home and 'Formulaire d’adhésion — test' in home and '/adherer' in home and 'données fictives' in home)
    else:
        check('Tariff homepage uses same published catalogue', 'Formule adulte fictive' in home and 'RECETTE FICTIVE' in home and 'Tester une demande fictive' in home)
    check('Published notice escaped', '<script>alert(1)</script>' not in body and '&lt;script&gt;' in body)
    check('Missing slots explicit', 'Créneaux à compléter' in body)
    check('Anonymous form has CSRF token', bool(oauth.Inputs(body).hidden.get('form_token')))
    name = 'Recette-' + secrets.token_hex(5)
    values = {'profile': 'adult', 'first_name': 'Alex Exemple', 'last_name': name, 'birth_date': '1990-01-01', 'email': 'adulte@example.invalid', 'phone': '', 'formula': 'adult-test', 'training': 'yes', 'previous_licence': 'unknown', 'review': 'Vérifier ma demande fictive', 'status': 'checked', 'notes': 'FORGED-PRIVATE-NOTE', 'amountCents': '1'}
    other = oauth.Session()
    check('Token bound to initiating session', 'Votre demande fictive' not in other.form('/adherer', values, body)[1])
    bad = anonymous.form('/adherer', dict(values, form_token='forged'), body)
    check('Anonymous bad CSRF rejected', 'Votre demande fictive' not in bad[1])
    review = anonymous.form('/adherer', values)
    (ROOT / '.local/registration-review.html').write_text(review[1], encoding='utf-8')
    check('Two-step summary shown', 'Votre demande fictive' in review[1] and '70,00' in review[1])
    with sqlite3.connect(ROOT / '.local/drupal-runtime/site.sqlite') as db:
        check('Review does not persist', db.execute('SELECT COUNT(*) FROM tcl_bureau_registration WHERE last_name=?', (name,)).fetchone()[0] == 0)
    submit = {'send': 'Déposer la demande fictive', 'fictional_confirmation': '1'}
    done = anonymous.form('/adherer', submit, review[1])
    (ROOT / '.local/registration-done.html').write_text(done[1], encoding='utf-8')
    check('Fictitious request accepted', 'demande fictive a été reçue' in done[1])
    anonymous.form('/adherer', submit, review[1])
    with sqlite3.connect(ROOT / '.local/drupal-runtime/site.sqlite') as db:
        rows = db.execute('SELECT id,status,details FROM tcl_bureau_registration WHERE last_name=?', (name,)).fetchall()
        check('Double click produces one record', len(rows) == 1)
        record_id, status, details = rows[0]
        saved = json.loads(details)
        check('Status and tariff set by server', status == 'received' and saved['catalogue_snapshot']['formula']['amountCents'] == 7000)
        check('Recipe origin explicit', saved['recipe'] is True and saved['origin'] == 'web_recipe')
        check('Forged private notes ignored', saved['notes'] == '')
    edit = {key: value for key, value in saved.items() if isinstance(value, str)}
    edit['status'] = 'to_complete'
    check('Bureau can process fictitious dossier', bureau.form(f'/bureau/adherents/{record_id}/modifier', edit)[0] == 303)
    with sqlite3.connect(ROOT / '.local/drupal-runtime/site.sqlite') as db:
        revised = json.loads(db.execute('SELECT details FROM tcl_bureau_registration WHERE id=?', (record_id,)).fetchone()[0])
        check('Bureau edit preserves snapshot and recipe origin', revised['catalogue_snapshot'] == saved['catalogue_snapshot'] and revised['recipe'] is True)
    duplicate_review = anonymous.form('/adherer', values)[1]
    check('Separate duplicate submission rejected', 'existe déjà' in anonymous.form('/adherer', submit, duplicate_review)[1])
    fields['formulas[0][price]'] = '80,00'
    bureau.form('/bureau/saison', fields)
    check('New draft leaves published price unchanged', '70,00' in anonymous.get('/adherer')[1])
    check('Homepage draft also stays private', '80,00' not in anonymous.get('/')[1])
    # Open a request on old catalogue, then publish a different price.
    old_review = anonymous.form('/adherer', dict(values, last_name=name+'Stale'))[1]
    publish['formulas[0][price]'] = '80,00'
    bureau.form('/bureau/saison', publish)
    check('Stale registration requires refreshed catalogue', 'tarifs ont changé' in anonymous.form('/adherer', submit, old_review)[1])
    with sqlite3.connect(ROOT / '.local/drupal-runtime/site.sqlite') as db:
        saved = json.loads(db.execute('SELECT details FROM tcl_bureau_registration WHERE id=?', (record_id,)).fetchone()[0])
        check('Existing snapshot remains 70 euros', saved['catalogue_snapshot']['formula']['amountCents'] == 7000)
    check('Anonymous cannot read private record', anonymous.get(f'/bureau/adherents/{record_id}/modifier')[0] in [302, 303, 403])
    check('Captain cannot read private record', captain.get(f'/bureau/adherents/{record_id}/modifier')[0] == 403)
    minor = dict(values, profile='minor', first_name='Camille Exemple', last_name=name+'Mineur', birth_date='2015-01-01', email='enfant@example.invalid', guardian_name='Recette Parent', guardian_email='parent@example.invalid', guardian_phone='', formula='minor-test')
    minor_review = anonymous.form('/adherer', minor)
    check('Minor request reviewed', 'Votre demande fictive' in minor_review[1])
    check('Minor request persisted', 'demande fictive a été reçue' in anonymous.form('/adherer', submit, minor_review[1])[1])
    invalid = anonymous.form('/adherer', dict(minor, guardian_name='', guardian_email=''))
    check('Minor requires guardian', 'responsable légal' in invalid[1] and 'Votre demande fictive' not in invalid[1])
    mismatch = anonymous.form('/adherer', dict(values, formula='minor-test'))
    check('Formula must match profile', 'correspondant au profil' in mismatch[1])
    check('Anonymous response is private and noindex', 'no-store' in anonymous.get('/adherer')[2].get('Cache-Control', '') and 'noindex' in anonymous.get('/adherer')[2].get('X-Robots-Tag', ''))
    setup('disable')
    check('Disabling flag closes existing session', anonymous.get('/adherer')[0] == 403)
    setup('enable')


if __name__ == '__main__':
    report = {'checkedAt': datetime.now(timezone.utc).isoformat(), 'scope': 'isolated local HTTP, fictitious identities only', 'checks': checks}
    try:
        run()
        report['passed'] = True
    finally:
        (ROOT / '.local/registration-recipe-http.json').write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding='utf-8')
    print(f'{len(checks)} catalogue/registration HTTP checks passed.')
