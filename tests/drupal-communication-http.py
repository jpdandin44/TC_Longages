"""Real local Drupal communication recipe: sessions, CSRF, publication and images."""
import datetime
import importlib.util
import json
import pathlib
import secrets
import sqlite3
import struct
import urllib.error
import urllib.request
import zlib

ROOT = pathlib.Path(__file__).resolve().parents[1]
ORIGIN = 'http://127.0.0.1:4182'
spec = importlib.util.spec_from_file_location('bureau_http', ROOT / 'tests/drupal-bureau-http.py')
bureau_module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(bureau_module)
Session, Inputs = bureau_module.Session, bureau_module.Inputs
CHECKS = []


def check(label, condition):
    CHECKS.append({'label': label, 'passed': bool(condition)})
    assert condition, label


def multipart(session, path, values, image, filename='poster.png', content_type='image/png'):
    body = session.request(path)[1]
    fields = Inputs(body).hidden
    fields.update(values)
    boundary = 'TCL' + secrets.token_hex(16)
    chunks = []
    for name, value in fields.items():
        chunks.append((f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"\r\n\r\n{value}\r\n').encode('utf-8'))
    chunks.append((f'--{boundary}\r\nContent-Disposition: form-data; name="files[poster]"; filename="{filename}"\r\nContent-Type: {content_type}\r\n\r\n').encode())
    chunks.extend([image, f'\r\n--{boundary}--\r\n'.encode()])
    request = urllib.request.Request(ORIGIN + path, data=b''.join(chunks), headers={'Content-Type': 'multipart/form-data; boundary=' + boundary})
    try:
        result = session.client.open(request, timeout=30)
    except urllib.error.HTTPError as error:
        result = error
    return result.status, result.read().decode('utf-8'), dict(result.headers), result.url


def png():
    def chunk(kind, data):
        return struct.pack('!I', len(data)) + kind + data + struct.pack('!I', zlib.crc32(kind + data))
    return b'\x89PNG\r\n\x1a\n' + chunk(b'IHDR', struct.pack('!2I5B', 32, 32, 8, 2, 0, 0, 0)) + chunk(b'IDAT', zlib.compress((b'\x00' + b'\xa6\x19\x2e' * 32) * 32)) + chunk(b'IEND', b'')


def run():
    credentials = json.loads((ROOT / '.local/v2-local-accounts.json').read_text(encoding='utf-8'))
    anonymous, bureau, captain, admin = Session(), Session(), Session(), Session()
    bureau.login(credentials['bureau'])
    captain.login(credentials['capitaineA'])
    admin.login(json.loads((ROOT / '.local/drupal-admin.json').read_text(encoding='utf-8')))
    for role, session in [('anonymous', anonymous), ('captain', captain)]:
        for path in ['/bureau/communication', '/bureau/communication/ajouter', '/bureau/communication/1/revue', '/bureau/communication/1/modifier', '/bureau/communication/1/image', '/bureau/communication/1/publish']:
            check(role + ' cannot read or write communication ' + path, session.request(path)[0] == 403)
    check('administrator and Bureau have access', admin.request('/bureau/communication')[0] == 200 and bureau.request('/bureau/communication')[0] == 200)
    check('Bureau navigation includes communication', 'href="/fr/bureau/communication"' in bureau.request('/bureau/adherents')[1])
    path = '/bureau/communication/ajouter'
    stamp = secrets.token_hex(4)
    title = 'Actualité fictive — recette ' + stamp
    values = {'title': title, 'category': 'club', 'body': 'Message fictif <script>window.unwanted=1</script>\nDeuxième ligne.', 'link': 'https://tenup.fft.fr/club/60310230', 'image_alt': '', 'tenup_visible': 'no'}
    def row(post_id):
        with sqlite3.connect(ROOT / '.local/drupal-runtime/site.sqlite') as db:
            db.row_factory = sqlite3.Row
            # Drupal binds SQLite values as strings. Read the declared BLOB as
            # bytes explicitly instead of asking Python to decode JPEG as UTF-8.
            result = db.execute('SELECT id,title,status,revision,approved_revision,validated_by,body,image_alt,CAST(image_data AS BLOB) AS image_data FROM tcl_bureau_post WHERE id=?', (post_id,)).fetchone()
            return dict(result) if result else None
    def count():
        with sqlite3.connect(ROOT / '.local/drupal-runtime/site.sqlite') as db:
            return db.execute('SELECT count(*) FROM tcl_bureau_post WHERE title=?', (title,)).fetchone()[0]
    form = bureau.request(path)[1]
    check('missing title/body rejected', 'Le titre est obligatoire' in bureau.post_form(path, dict(values, title=' ', body=''))[1] and count() == 0)
    check('unsafe external URL rejected', 'adresse HTTPS' in bureau.post_form(path, dict(values, link='javascript:alert(1)'))[1] and count() == 0)
    check('invalid CSRF does not create a draft', bureau.post_form(path, dict(values, form_token='invalid'))[0] in (200, 403) and count() == 0)
    created = bureau.post_form(path, values, form)
    check('draft persisted in database', created[0] == 200 and '/revue' in created[3] and count() == 1)
    post_id = int(created[3].split('/communication/')[1].split('/')[0])
    bureau.post_form(path, values, form)
    check('double submission does not duplicate draft', count() == 1)
    review, edit = f'/bureau/communication/{post_id}/revue', f'/bureau/communication/{post_id}/modifier'
    public, public_image = f'/actualites/{post_id}', f'/actualites/{post_id}/image'
    check('draft and draft image unavailable to visitors', anonymous.request(public)[0] == 404 and anonymous.request(public_image)[0] == 404 and title not in anonymous.request('/actualites')[1])
    preview = bureau.request(review)
    check('four previews, safe escaped text and no premature sharing', all('Aperçu ' + name in preview[1] for name in ['Site', 'Facebook', 'WhatsApp', 'ADOC']) and '<script>window.unwanted=1</script>' not in preview[1] and 'wa.me' not in preview[1])
    check('private headers', 'noindex' in preview[2].get('X-Robots-Tag', '') and 'no-store' in preview[2].get('Cache-Control', ''))
    before = row(post_id)
    bureau.request(f'/bureau/communication/{post_id}/validate')
    check('GET confirmation does not validate', row(post_id)['status'] == 'draft')
    check('confirmation displays exact escaped content', 'window.unwanted=1' in bureau.request(f'/bureau/communication/{post_id}/validate')[1] and '<script>window.unwanted=1</script>' not in bureau.request(f'/bureau/communication/{post_id}/validate')[1])
    bureau.post_form(f'/bureau/communication/{post_id}/validate', {})
    check('personal confirmation required', row(post_id)['status'] == 'draft')
    bureau.post_form(f'/bureau/communication/{post_id}/validate', {'personal_confirmation': 1, 'form_token': 'invalid'})
    check('CSRF protects validation', row(post_id)['status'] == 'draft')
    bureau.post_form(f'/bureau/communication/{post_id}/publish', {'personal_confirmation': 1})
    check('publication without validation refused', row(post_id)['status'] == 'draft' and anonymous.request(public)[0] == 404)
    bureau.post_form(f'/bureau/communication/{post_id}/validate', {'personal_confirmation': 1})
    checked = row(post_id)
    check('validation records exact revision and actor', checked['status'] == 'validated' and checked['approved_revision'] == checked['revision'] and checked['validated_by'] == credentials['bureau']['uid'])
    check('validation alone does not publish', anonymous.request(public)[0] == 404)
    prepared = bureau.request(review)[1]
    check('manual external services on reviewed revision', all(link in prepared for link in ['https://wa.me/?text=', 'https://www.facebook.com/tc.longages.31', 'https://adoc.app.fft.fr/adoc/', 'https://tenup.fft.fr/club/60310230']) and 'Message à copier' in prepared)
    old_publication = bureau.request(f'/bureau/communication/{post_id}/publish')[1]
    old_edit = bureau.request(edit)[1]
    bureau.post_form(f'/bureau/communication/{post_id}/publish', {'personal_confirmation': 1}, old_publication)
    published = anonymous.request(public)
    check('explicit publication visible to anonymous visitors', published[0] == 200 and title in published[1] and title in anonymous.request('/actualites')[1] and title in anonymous.request('/')[1])
    check('public content escaped and private data absent', '<script>window.unwanted=1</script>' not in published[1] and credentials['bureau']['username'] not in published[1] and 'Partager manuellement' not in published[1])
    bureau.post_form(edit, dict(values, body='Écrasement depuis ancienne fiche'), old_edit)
    check('old edit cannot overwrite published revision', row(post_id)['status'] == 'published' and 'Écrasement' not in row(post_id)['body'])
    values['body'] = 'Modification fictive, nouvelle revue obligatoire.'
    bureau.post_form(edit, values)
    check('editing withdraws publication and invalidates approval', row(post_id)['status'] == 'draft' and row(post_id)['approved_revision'] == 0 and anonymous.request(public)[0] == 404 and title not in anonymous.request('/')[1])
    bureau.post_form(f'/bureau/communication/{post_id}/publish', {'personal_confirmation': 1}, old_publication)
    check('old confirmation cannot republish changed content', row(post_id)['status'] == 'draft')
    invalid = multipart(bureau, edit, values, b'<?php echo "unsafe";?>', 'poster.php', 'image/jpeg')
    check('forged image is rejected without changing revision', 'Image JPEG' in invalid[1] and row(post_id)['image_data'] in (b'', ''))
    missing_alt = multipart(bureau, edit, values, png())
    check('image description required before saving', 'description de l’affiche' in missing_alt[1] and row(post_id)['image_data'] in (b'', ''))
    values['image_alt'] = 'Affiche fictive de recette'
    uploaded = multipart(bureau, edit, values, png())
    image_row = row(post_id)
    check('real image upload normalized and persisted', uploaded[0] == 200 and bytes(image_row['image_data']).startswith(b'\xff\xd8') and len(image_row['image_data']) <= 512000)
    image_path = f'/bureau/communication/{post_id}/image'
    # Binary images use the same session; do not feed them through the HTML helper.
    request = urllib.request.Request(ORIGIN + image_path)
    private_img = bureau.client.open(request, timeout=20)
    check('private normalized JPEG served with safe headers', private_img.status == 200 and private_img.headers['Content-Type'] == 'image/jpeg' and private_img.headers['X-Content-Type-Options'] == 'nosniff' and private_img.read().startswith(b'\xff\xd8'))
    check('private image denied to anonymous and captain', anonymous.request(image_path)[0] == 403 and captain.request(image_path)[0] == 403 and anonymous.request(public_image)[0] == 404)
    for action in ['validate', 'publish']:
        bureau.post_form(f'/bureau/communication/{post_id}/{action}', {'personal_confirmation': 1})
    actual_image = anonymous.client.open(ORIGIN + public_image, timeout=20)
    check('image becomes public only after explicit publication', actual_image.status == 200 and actual_image.read().startswith(b'\xff\xd8') and 'Affiche fictive' in anonymous.request(public)[1])
    values['body'] = 'x' * 2100
    bureau.post_form(edit, values)
    check('ADOC limit warns without truncating site content', 'Contenu trop long pour ADOC' in bureau.request(review)[1] and len(row(post_id)['body']) == 2100)
    for action in ['validate', 'publish', 'withdraw']:
        bureau.post_form(f'/bureau/communication/{post_id}/{action}', {'personal_confirmation': 1})
        if action == 'validate':
            too_long = bureau.request(review)[1]
            check('ADOC preparation blocked without affecting WhatsApp', 'Préparation ADOC indisponible' in too_long and 'https://adoc.app.fft.fr/adoc/' not in too_long and 'https://wa.me/?text=' in too_long)
    check('withdrawal removes content and image immediately', row(post_id)['status'] == 'validated' and anonymous.request(public)[0] == 404 and anonymous.request(public_image)[0] == 404)
    bureau.post_form(edit, dict(values, remove_image=1))
    check('removing image revokes approval and removes stored bytes', row(post_id)['status'] == 'draft' and row(post_id)['image_data'] in (b'', '') and row(post_id)['image_alt'] == '')
    bureau.post_form(f'/bureau/communication/{post_id}/archive', {'personal_confirmation': 1})
    check('archive preserves record and removes active/public views', row(post_id)['status'] == 'archived' and title not in bureau.request('/bureau/communication')[1] and anonymous.request(public)[0] == 404)
    check('archived record cannot be edited', bureau.request(edit)[0] == 404)
    check('archived record available for recovery by Bureau', title in bureau.request('/bureau/communication/archives')[1] and anonymous.request('/bureau/communication/archives')[0] == 403)
    bureau.post_form(f'/bureau/communication/{post_id}/restore', {'personal_confirmation': 1})
    check('restoration returns to draft without publication or approval', row(post_id)['status'] == 'draft' and row(post_id)['approved_revision'] == 0 and anonymous.request(public)[0] == 404)
    bureau.post_form(f'/bureau/communication/{post_id}/archive', {'personal_confirmation': 1})
    check('existing enrolments and account screens remain available', bureau.request('/bureau/adherents')[0] == 200 and bureau.request('/bureau/comptes')[0] == 200)
    return {'fixturePostId': post_id, 'fictitiousDataOnly': True, 'externalPublication': False, 'productionModified': False}


if __name__ == '__main__':
    report = {'observedAt': datetime.datetime.now(datetime.timezone.utc).isoformat(), 'target': ORIGIN, 'checks': CHECKS}
    try:
        report['result'] = run()
        report['status'] = 'passed'
    except Exception as error:
        report['status'] = 'failed'
        report['error'] = str(error)
    (ROOT / '.local/communication-http-results.json').write_text(json.dumps(report, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(json.dumps({'status': report['status'], 'checks': len(CHECKS), 'error': report.get('error')}, ensure_ascii=False))
    raise SystemExit(0 if report['status'] == 'passed' else 1)
