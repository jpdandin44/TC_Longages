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
    bureau, anonymous = oauth.Session(), oauth.Session()
    check('Bureau OAuth connects', bureau.oauth()[0] == 303)
    path = '/bureau/communication/ajouter'
    values = {'title': 'Recette canaux et PDF ' + secrets.token_hex(4), 'category': 'club', 'body': 'Essai fictif', 'link': '', 'image_alt': 'Affiche de recette', 'tenup_visible': 'no', 'channels[whatsapp]': 'whatsapp'}
    page = bureau.get(path)[1]
    check('Three channel choices shown', all('channels[' + key + ']' in page for key in ['site', 'facebook', 'whatsapp']))
    missing = dict(values); missing.pop('channels[whatsapp]')
    check('Empty selection rejected', 'Choisissez au moins un canal' in bureau.form(path, missing)[1])
    bad = dict(values, **{'channels[injected]': 'injected'})
    check('Unknown channel rejected', bureau.form(path, bad)[0] != 303)
    response = bureau.form(path, values)
    check('WhatsApp-only draft saved', response[0] == 303)
    post = re.search(r'/communication/(\d+)/revue', response[2]['Location'])[1]
    base = '/bureau/communication/' + post
    action = lambda name: bureau.form(base + '/' + name, {'personal_confirmation': '1'})
    check('Draft validates', action('validate')[0] == 303)
    review = bureau.get(base + '/revue')[1]
    check('Approved message offers explicit manual copy preparation', 'data-copy-message' in review and 'data-copy-status' in review and 'sharing-tools.js' in review)
    check('Only selected social action offered', 'Ouvrir WhatsApp' in review and 'Ouvrir Facebook' not in review and 'Publier sur le site' not in review)
    check('Direct site publication refused', action('publish')[0] != 303)
    check('Private article remains absent', anonymous.get('/actualites/' + post)[0] == 404)
    # Exercise actual upload Form API with fictitious PNG and PDF files.
    import struct, zlib, urllib.request, urllib.error
    def chunk(kind, data):
        return struct.pack('!I', len(data)) + kind + data + struct.pack('!I', zlib.crc32(kind + data))
    png = b'\x89PNG\r\n\x1a\n' + chunk(b'IHDR', struct.pack('!2I5B', 80, 120, 8, 2, 0, 0, 0)) + chunk(b'IDAT', zlib.compress((b'\x00' + b'\xa6\x19\x2e' * 80) * 120)) + chunk(b'IEND', b'')
    def upload(payload, filename, mime):
        fields = dict(oauth.Inputs(bureau.get(base + '/modifier')[1]).hidden, **values)
        boundary = 'TCL' + secrets.token_hex(16)
        parts = [(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n').encode() for k,v in fields.items()]
        parts += [(f'--{boundary}\r\nContent-Disposition: form-data; name="files[poster]"; filename="{filename}"\r\nContent-Type: {mime}\r\n\r\n').encode(), payload, f'\r\n--{boundary}--\r\n'.encode()]
        request = urllib.request.Request(oauth.ORIGIN + base + '/modifier', data=b''.join(parts), headers={'Content-Type': 'multipart/form-data; boundary=' + boundary})
        try: result = bureau.client.open(request, timeout=30)
        except urllib.error.HTTPError as error: result = error
        return result.status, result.read()
    def binary(session, path):
        try: result = session.client.open(oauth.ORIGIN + path, timeout=30)
        except urllib.error.HTTPError as error: result = error
        return result.status, result.read(), dict(result.headers)
    check('PNG upload accepted', upload(png, 'recette.png', 'image/png')[0] == 303)
    objects = [b'<< /Type /Catalog /Pages 2 0 R >>', b'<< /Type /Pages /Kids [3 0 R] /Count 1 >>', b'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 300] >>']
    pdf = b'%PDF-1.4\n'; offsets = []
    for i, obj in enumerate(objects, 1):
        offsets.append(len(pdf)); pdf += str(i).encode() + b' 0 obj\n' + obj + b'\nendobj\n'
    xref = len(pdf)
    pdf += b'xref\n0 4\n0000000000 65535 f \n' + b''.join(f'{n:010} 00000 n \n'.encode() for n in offsets)
    pdf += f'trailer\n<< /Root 1 0 R /Size 4 >>\nstartxref\n{xref}\n%%EOF\n'.encode()
    check('PDF import accepted', upload(pdf, 'recette.pdf', 'application/pdf')[0] == 303)
    downloaded = binary(bureau, base + '/image')
    check('PDF original preserved with safe download headers', downloaded[1] == pdf and 'sandbox' in downloaded[2].get('Content-Security-Policy','') and downloaded[2].get('X-Content-Type-Options') == 'nosniff')
    check('PDF represented as a download link', 'Télécharger l’affiche PDF' in bureau.get(base + '/revue')[1])
    check('Invalid PDF rejected without losing previous file', upload(b'%PDF-1.4\ninvalid', 'bad.pdf', 'application/pdf')[0] == 200 and binary(bureau, base + '/image')[1] == pdf)
    check('Oversized PDF rejected', upload(b'%PDF-1.4\n' + b' ' * (8 * 1024 * 1024) + b'%%EOF', 'large.pdf', 'application/pdf')[0] != 303)
    values['channels[site]'] = 'site'; values['channels[facebook]'] = 'facebook'
    check('Channels can be changed', bureau.form(base + '/modifier', values)[0] == 303)
    check('Channel change resets approval', 'État : Brouillon' in bureau.get(base + '/revue')[1])
    action('validate'); action('publish')
    check('Selected site can publish PDF', anonymous.get('/actualites/' + post)[0] == 200)
    public_image = '/actualites/' + post + '/image'
    check('Published PDF accessible', binary(anonymous, public_image)[0] == 200)
    stale = bureau.get(base + '/modifier')[1]
    values.pop('channels[site]')
    check('Removing site withdraws immediately', bureau.form(base + '/modifier', values)[0] == 303 and anonymous.get('/actualites/' + post)[0] == 404 and binary(anonymous, public_image)[0] == 404)
    check('Stale revision cannot restore site', 'a changé depuis' in bureau.form(base + '/modifier', dict(values, **{'channels[site]':'site'}), stale)[1])
    action('archive')

if __name__ == '__main__':
    report = {'checkedAt': datetime.now(timezone.utc).isoformat(), 'scope': 'local HTTP with fictitious OAuth only', 'hostedVerified': False, 'checks': checks}
    try:
        run()
        report['passed'] = True
    except Exception as error:
        report['passed'] = False
        report['error'] = str(error) if isinstance(error, AssertionError) else type(error).__name__
    (ROOT / '.local/communication-pdf-channels-tests.json').write_text(json.dumps(report, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(json.dumps({'passed': report['passed'], 'checks': len(checks), 'error': report.get('error')}))
    raise SystemExit(0 if report['passed'] else 1)
