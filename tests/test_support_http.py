"""Explicit local integration runner; it never starts a browser or sends mail."""
from __future__ import annotations

import http.cookiejar
import json
import re
import time
from datetime import datetime, timezone
from html.parser import HTMLParser
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.parse import urlencode
from urllib.request import HTTPCookieProcessor, Request, build_opener

PROJECT = Path(__file__).resolve().parents[1]
ORIGIN = 'http://127.0.0.1:4183'
CHECKS = []


class Fields(HTMLParser):
    def __init__(self):
        super().__init__()
        self.hidden = {}

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'input' and attrs.get('type') == 'hidden' and 'name' in attrs:
            self.hidden[attrs['name']] = attrs.get('value', '')


def check(condition, label):
    if not condition:
        raise AssertionError(label)
    CHECKS.append({'label': label, 'passed': True})


def client():
    return build_opener(HTTPCookieProcessor(http.cookiejar.CookieJar()))


def request(opener, path, values=None, origin=ORIGIN):
    headers = {'Origin': origin} if values is not None else {}
    req = Request(ORIGIN + path, None if values is None else urlencode(values).encode(), headers)
    try:
        response = opener.open(req, timeout=20)
    except HTTPError as error:
        response = error
    return response.status, response.read().decode('utf-8'), response.headers


def form(opener):
    status, html, _ = request(opener, '/signaler-un-probleme?page=%2Fcalendrier.html')
    check(status == 200, 'Formulaire anonyme accessible')
    fields = Fields()
    fields.feed(html)
    check(bool(fields.hidden.get('support_token')), 'Jeton CSRF de session présent pour un visiteur anonyme')
    return fields.hidden | {
        'category': 'problem', 'subject': 'Recette locale fictive — calendrier',
        'description': '<script>window.tclSupportXss=true</script>\nDonnées fictives. Étapes : ouvrir le calendrier. Attendu : liste des événements. Constat : exemple de test.',
        'page': '/calendrier.html', 'email': 'testeur@example.invalid',
        'website': '', 'information': '1', 'op': 'Envoyer ma demande',
    }


def main():
    anonymous = client()
    for attempt in range(20):
        try:
            if request(anonymous, '/user/login')[0] == 200:
                break
        except URLError:
            if attempt == 19:
                raise
            time.sleep(0.1)
    for page in ['index', 'competitions', 'calendrier', 'disponibilites', 'equipes', 'espace', 'contact']:
        status, body, headers = request(anonymous, '/' + page + '.html')
        check(status == 200 and body.count('>Signaler un problème sur le site</a>') == 1,
              f'Bouton unique sur {page}')
        check('no-store' in headers.get('Cache-Control', '') and 'noindex' in headers.get('X-Robots-Tag', ''),
              f'Cache privé et non-indexation sur {page}')
    for path in ['/admin/reports/tcl-support', '/admin/reports/tcl-support/1']:
        check(request(anonymous, path)[0] == 403, f'Demande privée refusée au visiteur : {path}')
    for values, label, text, origin in [
        ({'support_token': ''}, 'Soumission sans CSRF refusée', 'session du formulaire', ORIGIN),
        ({}, 'Origine étrangère refusée', 'session du formulaire', 'https://autre-site.example.invalid'),
        ({'website': 'spam'}, 'Champ piège renseigné refusé', 'ne peut pas être accepté', ORIGIN),
        ({'page': 'https://example.invalid/?token=private'}, 'Page hors périmètre refusée', 'ne peut pas être accepté', ORIGIN),
        ({'description': 'x' * 4001}, 'Description trop longue refusée', '4000', ORIGIN),
        ({'information': ''}, 'Confirmation personnelle obligatoire', 'Confirmez', ORIGIN),
    ]:
        payload = form(anonymous) | values
        status, body, _ = request(anonymous, '/signaler-un-probleme', payload, origin)
        check(status == 200 and text in body and 'est enregistrée' not in body, label)
    payload = form(anonymous)
    status, body, _ = request(anonymous, '/signaler-un-probleme', payload)
    match = re.search(r'Votre demande n° (\d+) est enregistrée', body)
    check(status == 200 and bool(match), 'Demande valide enregistrée puis confirmation affichée')
    number = match.group(1)
    _, duplicate, _ = request(anonymous, '/signaler-un-probleme', payload)
    check(f'Votre demande n° {number} est enregistrée' in duplicate, 'Double soumission renvoie la même demande')
    check(request(client(), '/admin/reports/tcl-support/' + number)[0] == 403,
          'Référence connue sans accès au contenu privé')
    admin = client()
    status, body, _ = request(admin, '/user/login')
    fields = Fields()
    fields.feed(body)
    private_credentials = json.loads((PROJECT / '.local/drupal-admin.json').read_text(encoding='utf-8'))
    payload = fields.hidden | {'name': private_credentials['username'], 'pass': private_credentials['password'], 'op': 'Log in'}
    status, _, _ = request(admin, '/user/login', payload)
    del private_credentials, payload
    check(status == 200, 'Connexion du compte local de recette')
    status, body, _ = request(admin, '/admin/reports/tcl-support')
    check(status == 200 and 'Recette locale fictive' in body and 'capturé en local' in body, 'Demande visible dans le suivi privé avec notification capturée')
    status, body, _ = request(admin, '/admin/reports/tcl-support/' + number)
    fields = Fields()
    fields.feed(body)
    check(status == 200 and 'testeur@example.invalid' in body, 'Coordonnée consultable par le compte autorisé')
    check('&lt;script&gt;window.tclSupportXss=true&lt;/script&gt;' in body and '<script>window.tclSupportXss=true</script>' not in body,
          'Description affichée comme texte, sans exécution de HTML')
    payload = fields.hidden | {'status': 'in_progress', 'owner': 'Responsable fictif', 'note': 'Analyse de recette locale, aucune demande réelle.', 'op': 'Enregistrer le traitement'}
    status, body, _ = request(admin, '/admin/reports/tcl-support/' + number, payload)
    check(status == 200 and 'Traitement et note de suivi enregistrés' in body, 'Traitement enregistré depuis le formulaire privé')
    check('Analyse de recette locale, aucune demande réelle.' in body, 'Note visible dans le journal privé')
    payload['note'] = 'Note périmée à ne pas conserver'
    status, body, _ = request(admin, '/admin/reports/tcl-support/' + number, payload)
    check(status == 200 and 'modifiée entre-temps' in body, 'Modification concurrente refusée par le formulaire réel')
    status, body, _ = request(admin, '/admin/reports/tcl-support?etat=resolved')
    check(status == 200 and 'Recette locale fictive' not in body, 'Filtre du suivi respecte l’état demandé')
    report = {'observedAt': datetime.now(timezone.utc).isoformat(), 'scope': 'local-http-only',
              'origin': ORIGIN, 'outgoingMail': False, 'checks': CHECKS}
    (PROJECT / '.local/support-http-verification.json').write_text(json.dumps(report, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(f'{len(CHECKS)} contrôles HTTP du support réussis ; aucun courriel externe envoyé.')


if __name__ == '__main__':
    main()
    from test_editor_http import main as editor_main
    editor_main()
