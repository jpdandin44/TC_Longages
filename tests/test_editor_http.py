"""Native node form round trip on the isolated local SQLite runtime only."""
import json
import re
from datetime import datetime, timezone
from html.parser import HTMLParser
from test_support_http import PROJECT, client, request, Fields


class EditFields(HTMLParser):
    def __init__(self):
        super().__init__()
        self.values = {}
        self.active = None
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        name = attrs.get('name')
        if tag == 'input' and name and attrs.get('type') not in ('submit', 'checkbox', 'radio'):
            self.values[name] = attrs.get('value', '')
        if tag == 'input' and name and attrs.get('type') == 'checkbox' and 'checked' in attrs:
            self.values[name] = attrs.get('value', '1')
        if tag == 'textarea' and name:
            self.active = name
            self.values[name] = ''
    def handle_data(self, data):
        if self.active:
            self.values[self.active] += data
    def handle_endtag(self, tag):
        if tag == 'textarea':
            self.active = None


def main():
    checks = []
    def check(ok, label):
        if not ok: raise AssertionError(label)
        checks.append({'label': label, 'passed': True})
    anon = client()
    check(request(anon, '/admin/content/tcl-pages')[0] == 403, 'Éditeur privé refusé au visiteur')
    admin = client()
    _, body, _ = request(admin, '/user/login')
    hidden = Fields(); hidden.feed(body)
    credentials = json.loads((PROJECT / '.local/drupal-admin.json').read_text(encoding='utf-8'))
    status, _, _ = request(admin, '/user/login', hidden.hidden | {'name': credentials['username'], 'pass': credentials['password'], 'op': 'Log in'})
    del credentials
    check(status == 200, 'Session Drupal de recette ouverte')
    status, body, _ = request(admin, '/admin/content/tcl-pages')
    check(status == 200 and body.count('>Modifier<') == 7, 'Sept pages disponibles dans le répertoire natif')
    check('tcl-support-link' not in body, 'Aucun bouton public de signalement dans l’administration')
    paths = re.findall(r'href="(/node/\d+/edit[^\"]*)"', body)
    path = paths[0].replace('&amp;', '&')
    status, body, _ = request(admin, path)
    edit = EditFields(); edit.feed(body)
    check(status == 200 and any('[regions]' in key for key in edit.values), 'Rubriques disponibles dans le formulaire Drupal')
    caption_key = next(key for key, value in edit.values.items() if '[regions]' in key and value == 'Le court de Longages · photo fournie par le club')
    original = edit.values.copy()
    before = request(anon, '/club')[1]
    new_caption = 'Recette éditoriale locale — <script>texte sans exécution</script>'
    edit.values[caption_key] = new_caption
    edit.values['op'] = 'Save'
    edit.values['revision_log[0][value]'] = 'Essai local fictif de la légende.'
    status, body, _ = request(admin, path, edit.values)
    check(status == 200 and 'has been updated' in body, 'Modification enregistrée par le vrai formulaire de contenu')
    public = request(anon, '/club')[1]
    check('Recette éditoriale locale' in public and '&lt;script&gt;texte sans exécution&lt;/script&gt;' in public, 'Texte publié et HTML saisi échappé')
    check('<script>texte sans exécution</script>' not in public, 'Aucune injection de script dans la légende')
    _, body, _ = request(admin, path)
    restore = EditFields(); restore.feed(body)
    restore.values[caption_key] = original[caption_key]
    restore.values['op'] = 'Save'
    restore.values['revision_log[0][value]'] = 'Retour au texte initial après recette locale.'
    status, _, _ = request(admin, path, restore.values)
    check(status == 200 and request(anon, '/club')[1] == before, 'Texte et balisage public restaurés à l’identique')
    revision_path = path.split('/edit')[0] + '/revisions'
    status, body, _ = request(admin, revision_path)
    check(status == 200 and 'Essai local fictif' in body and 'Retour au texte initial' in body, 'Révisions des deux enregistrements conservées')
    status, body, _ = request(admin, '/admin/content/tcl-pages')
    for page in ['competitions', 'calendrier', 'disponibilites', 'equipes', 'espace', 'contact']:
        check(request(anon, '/' + page + '.html')[0] == 200, 'Page publique préservée : ' + page)
    report = {'observedAt': datetime.now(timezone.utc).isoformat(), 'scope': 'local-native-editor', 'outgoingMail': False, 'checks': checks}
    (PROJECT / '.local/editor-http-verification.json').write_text(json.dumps(report, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(str(len(checks)) + ' contrôles HTTP de l’édition Drupal réussis ; texte initial rétabli.')


if __name__ == '__main__':
    main()
