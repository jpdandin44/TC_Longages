"""Native HTTP contact and editing discovery, isolated SQLite/capture only."""
import json
import re
import subprocess
from datetime import datetime, timezone
from test_support_http import PROJECT, client, request, Fields, ORIGIN


def main():
    checks = []
    def check(ok, label):
        if not ok: raise AssertionError(label)
        checks.append({'label': label, 'passed': True})
    anon = client()
    for page in ['index', 'competitions', 'calendrier', 'disponibilites', 'equipes', 'espace', 'contact']:
        status, body, _ = request(anon, '/' + page + '.html')
        check(status == 200 and 'tcl-edit-bar' not in body, 'Aucune commande d’édition anonyme : ' + page)
        check(body.count('>Signaler un problème sur le site</a>') == 1, 'Signalement conservé : ' + page)
    admin = client()
    _, body, _ = request(admin, '/user/login')
    hidden = Fields(); hidden.feed(body)
    credentials = json.loads((PROJECT / '.local/drupal-admin.json').read_text(encoding='utf-8'))
    check(request(admin, '/user/login', hidden.hidden | {'name': credentials['username'], 'pass': credentials['password'], 'op':'Log in'})[0] == 200, 'Session éditoriale de recette ouverte')
    del credentials
    for page in ['index', 'competitions', 'calendrier', 'disponibilites', 'equipes', 'espace', 'contact']:
        status, body, headers = request(admin, '/' + page + '.html')
        match = re.search(r'href="(/node/\d+/edit\?[^"]*)">Modifier cette page</a>', body)
        check(status == 200 and bool(match) and 'Pages du club</a>' in body, 'Commandes d’édition visibles : ' + page)
        path = match.group(1).replace('&amp;', '&')
        check(('destination=' in path and (page + '.html') in path) and request(admin, path)[0] == 200, 'Formulaire natif et retour sur la page : ' + page)
        check('no-store' in headers.get('Cache-Control',''), 'Réponse administrateur privée : ' + page)

    def payload():
        status, body, _ = request(anon, '/contact.html')
        check(status == 200 and 'tcl-club-contact' in body and 'id="contact-prepare"' not in body and 'Aperçu du futur formulaire' not in body, 'Formulaire natif affiché dans la page du site')
        fields = Fields(); fields.feed(body)
        check(bool(fields.hidden.get('contact_token')), 'Protection CSRF de session anonyme présente')
        return fields.hidden | {'name':'Essai fictif TCL', 'reply':'testeur@example.invalid',
          'message':'Essai fictif du contact : <script>aucune exécution</script>',
          'website':'', 'information':'1', 'op':'Envoyer mon message'}
    for changed, label, expected, origin in [
        ({'contact_token':''}, 'CSRF absent refusé', 'session du formulaire', ORIGIN),
        ({}, 'Origine étrangère refusée', 'session du formulaire', 'https://example.invalid'),
        ({'website':'spam'}, 'Piège antispam refusé', 'ne peut pas être accepté', ORIGIN),
        ({'message':'x'*2001}, 'Message trop long refusé', '2000', ORIGIN),
        ({'reply':'pas-une-coordonnee'}, 'Coordonnée invalide refusée', 'valide', ORIGIN),
        ({'reply':'test@example.invalid\r\nBcc: pirate@example.invalid'}, 'Injection d’en-tête refusée', 'valide', ORIGIN),
        ({'information':''}, 'Accord de transmission obligatoire', 'Confirmez', ORIGIN),
    ]:
        status, body, _ = request(anon, '/contact.html', payload() | changed, origin)
        check(status == 200 and expected in body and 'Essai local réussi' not in body, label)
    first = payload()
    status, body, _ = request(anon, '/contact.html', first)
    check(status == 200 and 'Essai local réussi' in body, 'Message valide accepté et capturé sans envoi réel')
    status, body, _ = request(anon, '/contact.html', first)
    check(status == 200 and 'Essai local réussi' in body, 'Double soumission confirmée sans nouvel envoi')
    status, body, _ = request(anon, '/contact.html', payload() | {'reply':'+33 6 12 34 56 78'})
    check(status == 200 and 'Essai local réussi' in body, 'Contact par téléphone accepté sans Reply-To invalide')
    for _ in range(3):
        check('Essai local réussi' in request(anon, '/contact.html', payload())[1], 'Quota de cinq messages autorisé')
    check('nombre de messages autorisé' in request(anon, '/contact.html', payload())[1], 'Sixième message limité par le serveur')
    subprocess.run(['php','-c',str(PROJECT/'.local/drupal-tools/php.ini'),str(PROJECT/'tests/contact-runtime.php')],cwd=PROJECT,check=True)
    report = {'observedAt':datetime.now(timezone.utc).isoformat(), 'scope':'local-native-form-and-editor-links',
       'outgoingMail':False, 'checks':checks}
    (PROJECT/'.local/contact-http-verification.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
    print(str(len(checks))+' contrôles HTTP contact/édition réussis ; aucun courriel réel envoyé.')


if __name__ == '__main__': main()
