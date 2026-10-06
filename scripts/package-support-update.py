"""Build an additive preproduction patch from one committed source; no secrets."""
import hashlib
import json
from pathlib import Path
import subprocess
import zipfile

ROOT = Path(__file__).resolve().parents[1]


def main():
    source = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT).decode().strip()
    files = {}
    for directory in ['drupal/web/modules/custom/tcl_site', 'drupal/web/modules/custom/tcl_support']:
        for path in sorted((ROOT / directory).rglob('*')):
            if path.is_file():
                name = path.relative_to(ROOT / 'drupal').as_posix()
                relative = path.relative_to(ROOT).as_posix()
                data = subprocess.check_output(['git', 'show', source + ':' + relative], cwd=ROOT)
                if data != path.read_bytes(): raise ValueError('Uncommitted deployment source: ' + relative)
                files[name] = data
    for name in ['index', 'competitions', 'calendrier', 'disponibilites', 'equipes', 'espace', 'contact']:
        path = ROOT / '.local/drupal-public-candidate/site-pages' / (name + '.html')
        files['site-pages/' + name + '.html'] = path.read_bytes()
    for name in ['backup_site.py', 'restore_backup_files.py', 'support-update-hosting.py', 'support-update-hosting.php']:
        files['tools/' + name] = subprocess.check_output(['git', 'show', source + ':scripts/' + name], cwd=ROOT)
    manifest = {'kind': 'tcl-preproduction-support-editor-patch', 'format': 1, 'sourceSha': source,
                'targetHost': 'preprod.tclongages.fr', 'productionAllowed': False,
                'files': {name: {'bytes': len(data), 'sha256': hashlib.sha256(data).hexdigest()}
                          for name, data in sorted(files.items())}}
    manifest_bytes = (json.dumps(manifest, sort_keys=True, ensure_ascii=False, indent=2) + '\n').encode()
    package = ROOT / '.local' / ('tcl-support-editor-' + source[:12] + '.zip')
    if package.exists(): raise ValueError('Package already exists; do not overwrite a qualified artifact.')
    with zipfile.ZipFile(package, 'x', zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for name, data in sorted(files.items()):
            info = zipfile.ZipInfo(name, (2026, 10, 6, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            archive.writestr(info, data)
        archive.writestr('manifest.json', manifest_bytes)
    with zipfile.ZipFile(package) as archive:
        if archive.testzip() is not None: raise ValueError('Package CRC failed.')
    receipt = {'sourceSha': source, 'package': str(package), 'artifactSha256': hashlib.sha256(package.read_bytes()).hexdigest(),
               'manifestSha256': hashlib.sha256(manifest_bytes).hexdigest(), 'files': len(files), 'bytes': package.stat().st_size}
    (ROOT / '.local/support-delivery-package.json').write_text(json.dumps(receipt, indent=2) + '\n', encoding='utf-8')
    print(json.dumps(receipt))


if __name__ == '__main__':
    main()
