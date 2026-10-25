"""Prepare the limited V2 update from committed blobs, without Core, config or secrets."""
import hashlib
import json
from pathlib import Path
import subprocess
import zipfile

ROOT = Path(__file__).resolve().parents[1]
PREFIXES = ('drupal/web/modules/custom/tcl_bureau/', 'drupal/web/modules/custom/tcl_bureau_oauth/')
EXACT = ()


def main():
    source = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT).decode().strip()
    paths = subprocess.check_output(['git', 'ls-tree', '-r', '--name-only', source], cwd=ROOT).decode().splitlines()
    selected = [path for path in paths if path.startswith(PREFIXES) or path in EXACT]
    if not all(path in selected for path in EXACT) or not any(path.startswith(PREFIXES[1]) for path in selected):
        raise ValueError('Incomplete committed Bureau/OAuth source.')
    unexpected = subprocess.check_output(['git', 'ls-files', '--others', '--exclude-standard', '--', *PREFIXES], cwd=ROOT).decode().splitlines()
    if unexpected:
        raise ValueError('Untracked deployment source; commit it before packaging.')
    files = {}
    for path in selected:
        data = subprocess.check_output(['git', 'show', source + ':' + path], cwd=ROOT)
        actual = (ROOT / path).read_bytes()
        if data != actual and data.replace(b'\r\n', b'\n') != actual.replace(b'\r\n', b'\n'):
            raise ValueError('Uncommitted deployment source: ' + path)
        files[path.removeprefix('drupal/')] = data
    manifest = {'kind': 'tcl-v2-module-update', 'format': 1, 'sourceSha': source,
                'targetHost': 'preprod.tclongages.fr', 'productionAllowed': False,
                'privateConfigurationIncluded': False, 'databaseIncluded': False,
                'editorialTemplatesIncluded': False, 'localFixtureIncluded': False,
                'dependencyInstallationRequired': False, 'simpleOAuthVersion': '6.1.1',
                'hostedCorePreserved': True, 'embeddedPdfJsVersion': '6.4.299',
                'readyForDirectDeployment': False,
                'files': {name: {'bytes': len(data), 'sha256': hashlib.sha256(data).hexdigest()} for name, data in sorted(files.items())}}
    encoded = (json.dumps(manifest, sort_keys=True, indent=2) + '\n').encode()
    target = ROOT / '.local' / ('tcl-v2-update-' + source[:12] + '.zip')
    if target.exists(): raise ValueError('Candidate already exists; preserve the qualified package.')
    with zipfile.ZipFile(target, 'x', zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for name, data in sorted(files.items()):
            info = zipfile.ZipInfo(name, (2026, 10, 12, 0, 0, 0)); info.compress_type = zipfile.ZIP_DEFLATED
            archive.writestr(info, data)
        archive.writestr('manifest.json', encoded)
    with zipfile.ZipFile(target) as archive:
        if archive.testzip() is not None or set(archive.namelist()) != set(files) | {'manifest.json'}:
            raise ValueError('Candidate archive verification failed.')
    receipt = {'sourceSha': source, 'package': str(target), 'artifactSha256': hashlib.sha256(target.read_bytes()).hexdigest(),
               'manifestSha256': hashlib.sha256(encoded).hexdigest(), 'files': len(files), 'bytes': target.stat().st_size,
               'targetHost': 'preprod.tclongages.fr', 'productionAllowed': False, 'hostedInstallationPerformed': False}
    (ROOT / '.local/bureau-oauth-package.json').write_text(json.dumps(receipt, indent=2) + '\n', encoding='utf-8')
    print(json.dumps({'sourceSha': source, 'artifactSha256': receipt['artifactSha256'], 'files': len(files), 'bytes': receipt['bytes'], 'deployed': False}))

if __name__ == '__main__': main()
