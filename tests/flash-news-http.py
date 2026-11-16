"""Loopback-only OAuth/Bureau/anonymous homepage integration using fictitious news."""
import importlib.util
import json
import pathlib
import re
import secrets
from datetime import datetime, timezone

ROOT = pathlib.Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('oauth_recipe', ROOT / 'tests/drupal-bureau-oauth-http.py')
oauth = importlib.util.module_from_spec(spec)
spec.loader.exec_module(oauth)
checks = []


def check(label, result):
    checks.append({'label': label, 'passed': bool(result)})
    assert result, label


def run():
    bureau, anonymous, captain = oauth.Session(), oauth.Session(), oauth.Session()
    check('Bureau OAuth fixture connects', bureau.oauth()[0] == 303)
    check('Captain OAuth fixture connects', captain.oauth('captain-fixture')[0] == 303)
    path = '/bureau/communication/ajouter'
    values = {'title': 'Rencontre du club — exemple fictif ' + secrets.token_hex(4),
              'category': 'club', 'body': 'Une actualité fictive pour vérifier le bandeau. <script>alert(1)</script>',
              'link': '', 'image_alt': '', 'tenup_visible': 'no', 'channels[site]': 'site', 'channels[facebook]': 'facebook', 'channels[whatsapp]': 'whatsapp'}
    check('Anonymous cannot create news', anonymous.get(path, values)[0] == 403)
    check('Captain cannot create news', captain.get(path, values)[0] == 403)
    response = bureau.form(path, values)
    check('Bureau creates draft', response[0] == 303)
    match = re.search(r'/communication/(\d+)/revue', response[2].get('Location', ''))
    check('Draft reference returned', match is not None)
    post = match[1]
    title = values['title']
    public = '/actualites/' + post

    def homepage():
        response = anonymous.get('/')
        check('Public homepage responds', response[0] == 200)
        return response[1]

    def action(name, fields=None):
        return bureau.form('/bureau/communication/' + post + '/' + name,
                           fields if fields is not None else {'personal_confirmation': '1'})

    check('Draft absent on homepage', title not in homepage())
    action('validate')
    check('Validated-only news absent on homepage', title not in homepage())
    action('publish', {'personal_confirmation': '1', 'form_token': 'invalid'})
    check('Invalid CSRF cannot publish', title not in homepage())
    action('publish')
    html = homepage()
    check('Published news appears in banner', re.search(r'id="flash-news".*?' + re.escape(title), html, re.S))
    check('Script text escaped in public banner', '<script>alert(1)</script>' not in html and '&lt;script&gt;alert(1)&lt;/script&gt;' in html)
    check('Public detail responds', anonymous.get(public)[0] == 200)
    check('Index alias shows the same banner', title in anonymous.get('/index.html')[1])
    action('withdraw')
    check('Withdrawal removes banner content and detail', title not in homepage() and anonymous.get(public)[0] == 404)
    action('publish')
    check('Explicit republication displays content', title in homepage())
    values['body'] = 'Texte fictif modifié ; cette révision doit être validée à nouveau.'
    check('Edit returns to draft', bureau.form('/bureau/communication/' + post + '/modifier', values)[0] == 303)
    check('Edit removes formerly published revision', title not in homepage() and anonymous.get(public)[0] == 404)
    action('validate'); action('publish'); action('archive')
    check('Archive removes content', title not in homepage() and anonymous.get(public)[0] == 404)
    action('restore')
    check('Restore remains a private draft', title not in homepage() and anonymous.get(public)[0] == 404)
    action('archive')


if __name__ == '__main__':
    report = {'checkedAt': datetime.now(timezone.utc).isoformat(), 'scope': 'local HTTP with fictitious OAuth only', 'hostedVerified': False, 'checks': checks}
    try:
        run()
        report['passed'] = True
    except Exception as error:
        report['passed'] = False
        report['error'] = str(error) if isinstance(error, AssertionError) else type(error).__name__
    (ROOT / '.local/flash-news-http-results.json').write_text(json.dumps(report, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(json.dumps({'passed': report['passed'], 'checks': len(checks), 'error': report.get('error')}))
    raise SystemExit(0 if report['passed'] else 1)
