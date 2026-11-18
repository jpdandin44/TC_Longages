"""Real local Drupal Form API, OAuth, CSRF and storage; no hosted access or mail."""
import importlib.util
import json
import pathlib
import re
import secrets
import sys
from urllib.parse import urlsplit

ROOT = pathlib.Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('oauth', ROOT / 'tests/drupal-bureau-oauth-http.py')
oauth = importlib.util.module_from_spec(spec)
spec.loader.exec_module(oauth)
checks = []
if '--basic' in sys.argv:
    original_session = oauth.Session
    class BasicSession(original_session):
        def __init__(self):
            super().__init__()
            self.client.addheaders.append(('Authorization', 'Basic Zml4dHVyZTpmaXh0dXJl'))
    oauth.Session = BasicSession


def check(label, ok):
    checks.append({'label': label, 'passed': bool(ok)})
    if not ok:
        raise AssertionError(label)


def run():
    admin = oauth.Session()
    check('Fictitious administrator authenticates through OAuth', admin.oauth()[0] == 303)
    path = '/bureau/contacts-google'
    mode = ROOT / '.local/post-fixture-mode.txt'
    mode.write_text('success')
    page = admin.get(path)
    check('Contacts form is accessible', page[0] == 200 and 'Mettre à jour les contacts' in page[1])
    check('Contacts form permits same-origin referrer for the hosting WAF', page[2].get('Referrer-Policy') == 'same-origin')
    response = admin.form(path, {'refresh': 'Mettre à jour les contacts'})
    check('Contacts refresh completes without a redirect loop', response[0] == 200 and 'Contacts Google actualisés : 1' in response[1])
    check('Duplicates removed and invalid address excluded', '1 adresses distinctes utilisables ; 1 contacts' in response[1])
    check('Summary persists on a fresh GET', '1 adresses distinctes utilisables ; 1 contacts' in admin.get(path)[1])
    for state in ['unavailable', 'wrong-group']:
        mode.write_text(state)
        error = admin.form(path, {'refresh': 'Mettre à jour les contacts'})
        check(state + ' gives a recoverable error without redirect', error[0] == 200 and 'Mise à jour impossible' in error[1])
        check(state + ' preserves previous success', '1 adresses distinctes utilisables ; 1 contacts' in admin.get(path)[1])
    mode.write_text('success')
    calls = ROOT / '.local/post-fixture-calls.jsonl'
    before = calls.read_text()
    bad = admin.form(path, {'refresh':'Mettre à jour les contacts', 'form_token':'invalid'})
    check('Invalid CSRF does not invoke Google', calls.read_text() == before)
    check('Anonymous POST is refused', oauth.Session().get(path, {'refresh':'Mettre à jour les contacts'})[0] == 403)

    path = '/bureau/communication/ajouter'
    check('Draft creation permits same-origin referrer', admin.get(path)[2].get('Referrer-Policy') == 'same-origin')
    title = '[LOCAL] Brouillon POST ' + secrets.token_hex(4)
    values = {'title':title, 'category':'club', 'body':'Texte commun fictif', 'link':'',
              'image_alt':'', 'tenup_visible':'no', 'channels[site]':'site', 'channels[whatsapp]':'whatsapp',
              'copy_whatsapp[body]':'Variante WhatsApp fictive', 'op':'Enregistrer le brouillon'}
    saved = admin.form(path, values)
    check('New draft redirects once to its review', saved[0] == 303 and re.search(r'/communication/\d+/revue$', saved[2].get('Location','')) is not None)
    location = urlsplit(saved[2]['Location'])
    check('Redirect stays on the isolated local site', not location.netloc or location.netloc == '127.0.0.1:4186')
    review_path = location.path
    review = admin.get(review_path)
    check('Review loads and retains common text and variant', review[0] == 200 and title in review[1] and 'Texte commun fictif' in review[1] and 'Variante WhatsApp fictive' in review[1])
    base = review_path.removesuffix('/revue')
    edit = base + '/modifier'
    check('Draft editing permits same-origin referrer', admin.get(edit)[2].get('Referrer-Policy') == 'same-origin')
    check('Review keeps strict referrer privacy', admin.get(review_path)[2].get('Referrer-Policy') == 'no-referrer')
    old = admin.get(edit)[1]
    values['body'] = 'Texte commun modifié'
    changed = admin.form(edit, values, old)
    check('Draft edit redirects once', changed[0] == 303)
    check('Draft edit persists after reload', 'Texte commun modifié' in admin.get(review_path)[1])
    stale = admin.form(edit, dict(values, body='Version périmée'), old)
    check('Stale revision cannot overwrite current draft', 'a changé depuis' in stale[1] and 'Version périmée' not in admin.get(review_path)[1])
    bad = admin.form(edit, dict(values, body='CSRF interdit', form_token='invalid'))
    check('Invalid CSRF does not overwrite draft', 'CSRF interdit' not in admin.get(review_path)[1])
    check('Draft remains private', oauth.Session().get('/actualites/' + base.rsplit('/',1)[1])[0] == 404)
    check('Anonymous draft POST refused', oauth.Session().get(path, values)[0] == 403)
    check('OAuth callback keeps strict referrer privacy', admin.get('/bureau/oauth/retour')[2].get('Referrer-Policy') == 'no-referrer')


if __name__ == '__main__':
    try:
        run()
    finally:
        filename = 'bureau-form-post-basic-results.json' if '--basic' in sys.argv else 'bureau-form-post-results.json'
        (ROOT / '.local' / filename).write_text(json.dumps({'checks':checks, 'scope':'local SQLite + simulated Google + loopback OAuth', 'basicBarrier':'--basic' in sys.argv, 'hostedTouched':False}, ensure_ascii=False, indent=2), encoding='utf-8')
    print(f'{len(checks)} local HTTP form checks passed; no mail or hosted writes.')
