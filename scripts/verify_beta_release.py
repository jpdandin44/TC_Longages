"""Bind the qualified beta archive to a reviewable, immutable source snapshot.

No deployment, reconstruction, configuration, credentials or hosted writes.
"""
import argparse
import hashlib
import json
from pathlib import Path, PurePosixPath
import subprocess
import zipfile

from delivery_shared import digest, require
from prepare_delivery import verify_candidate

ROOT = Path(__file__).resolve().parents[1]
ARTIFACT = '0b6b167c33d1b770729b4480b4558b12f0ef93aca5a323571ef875cb80477a0d'
MANIFEST = '7ebc42d17ab88214c9004dc4ce20aaed4d9844c242c92924f64e825785308da9'
BINDING = '880c34de3d4154e1da3d242866e1daf5094f7cd1c804542f25abf2e619699d1c'
BASELINE = 'e0ec292dabbb15159d1f0c22aad670aee6cd480e'
SNAPSHOT = ROOT / 'config/launch-beta'
DEPENDENCIES = ('drupal/vendor/', 'drupal/web/core/')
MODULES = ('drupal/web/modules/custom/tcl_site/', 'drupal/web/modules/custom/tcl_support/')
CORE = {'drupal/core', 'drupal/core-recommended', 'drupal/core-composer-scaffold',
        'drupal/core-project-message', 'drupal/core-recipe-unpack'}


def source_name(name):
    path = PurePosixPath(name)
    require(name.startswith('drupal/') and not path.is_absolute()
            and '..' not in path.parts and '\\' not in name, 'Unsafe source path.')
    require(name not in ('drupal/web/sites/default/settings.php',
                        'drupal/web/sites/default/services.yml')
            and not name.startswith('drupal/web/sites/default/files/'), 'Runtime data forbidden.')
    if name.startswith('drupal/web/modules/custom/'):
        require(name.startswith(MODULES), 'Non-beta module forbidden.')
    return not name.startswith(DEPENDENCIES)


def read_archive(path):
    require(digest(path) == ARTIFACT, 'Qualified archive differs; keep the original unchanged.')
    result = verify_candidate(path, BASELINE)
    require(result['manifestSha256'] == MANIFEST, 'Qualified manifest differs.')
    with zipfile.ZipFile(path) as archive:
        manifest = json.loads(archive.read('manifest.json'))
    require(manifest['kind'] == 'tcl-beta-security-code-candidate'
            and manifest['deploymentAllowed'] is False
            and manifest['publicOpeningAuthorized'] is False
            and manifest['securityUpdate']['coreVersion'] == '11.4.10', 'Candidate scope differs.')
    return manifest, result


def freeze(archive_path, destination=SNAPSHOT):
    require(not destination.exists(), 'Existing frozen sources preserved.')
    manifest, result = read_archive(archive_path)
    sources = {name: item for name, item in manifest['files'].items() if source_name(name)}
    require(len(sources) == 65, 'Unexpected non-dependency inventory.')
    destination.mkdir(parents=True)
    with zipfile.ZipFile(archive_path) as archive:
        for name in sorted(sources):
            target = destination / 'source' / name
            target.parent.mkdir(parents=True, exist_ok=True)
            with target.open('xb') as stream:
                stream.write(archive.read(name))
    contract = {
        'format': 'tcl-beta-source-binding-v1', 'project': 'TC_Longages',
        'artifactSha256': ARTIFACT, 'manifestSha256': MANIFEST,
        'baselineSourceSha': BASELINE, 'coreVersion': '11.4.10',
        'artifactFileCount': len(manifest['files']), 'files': sources,
        'deploymentAllowed': False, 'publicOpeningAuthorized': False,
        'sourceRole': 'Immutable extraction for review; not an editable copy or a configured site.',
    }
    with (destination / 'source-binding.json').open('x', encoding='utf-8') as stream:
        json.dump(contract, stream, indent=2, ensure_ascii=False, sort_keys=True)
        stream.write('\n')
    return result


def verify_sources(destination=SNAPSHOT, root=ROOT):
    contract = json.loads((destination / 'source-binding.json').read_text(encoding='utf-8'))
    encoded = json.dumps(contract, sort_keys=True, separators=(',', ':')).encode()
    require(hashlib.sha256(encoded).hexdigest() == BINDING, 'Frozen source contract differs.')
    require(contract['format'] == 'tcl-beta-source-binding-v1'
            and contract['artifactSha256'] == ARTIFACT
            and contract['manifestSha256'] == MANIFEST
            and contract['baselineSourceSha'] == BASELINE
            and contract['coreVersion'] == '11.4.10'
            and contract['deploymentAllowed'] is False
            and contract['publicOpeningAuthorized'] is False, 'Source binding differs.')
    source = destination / 'source'
    files = {}
    for path in source.rglob('*'):
        require(not path.is_symlink(), 'Source symlink rejected.')
        if path.is_file():
            name = path.relative_to(source).as_posix()
            require(source_name(name), 'Dependencies do not belong in the frozen source.')
            files[name] = {'bytes': path.stat().st_size, 'sha256': digest(path)}
    require(files == contract['files'] and len(files) == 65, 'Frozen source inventory differs.')
    lock = json.loads((source / 'drupal/composer.lock').read_text(encoding='utf-8'))
    versions = {p['name']: p['version'] for p in lock['packages']}
    require(all(versions.get(name) == '11.4.10' for name in CORE), 'Core version differs.')
    require(not any(name in versions for name in ('drupal/simple_oauth', 'drupal/consumers')),
            'Bureau dependencies are outside beta scope.')
    names = subprocess.check_output(['git', 'ls-tree', '-r', '--name-only', BASELINE, '--']
                                    + list(MODULES), cwd=root).decode().splitlines()
    require(set(names) == {name for name in files if name.startswith(MODULES)}
            and len(names) == 33, 'Historical beta module inventory differs.')
    for name in names:
        raw = subprocess.check_output(['git', 'show', BASELINE + ':' + name], cwd=root)
        require(hashlib.sha256(raw).hexdigest() == files[name]['sha256'],
                'Module differs from historical beta source: ' + name)
    head = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=root).decode().strip()
    return {'status': 'passed', 'artifactSha256': ARTIFACT, 'sourceCommit': head,
            'frozenSourceFiles': len(files), 'historicalModuleFilesMatched': len(names),
            'coreVersion': '11.4.10', 'deploymentExecuted': False,
            'humanAcceptanceInferred': False}


def verify(archive_path=None, destination=SNAPSHOT, root=ROOT):
    result = verify_sources(destination, root)
    if archive_path:
        manifest, archive_result = read_archive(archive_path)
        contract = json.loads((destination / 'source-binding.json').read_text(encoding='utf-8'))
        require(contract['files'] == {name: item for name, item in manifest['files'].items()
                                     if source_name(name)}, 'Archive/source binding differs.')
        result['archiveFilesVerified'] = len(manifest['files'])
        result['archiveBytesVerified'] = archive_result['archiveSha256'] == ARTIFACT
    return result


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('operation', choices=['freeze', 'verify'])
    parser.add_argument('--archive', type=Path)
    args = parser.parse_args()
    if args.operation == 'freeze':
        require(args.archive is not None, 'Archive required for extraction.')
        freeze(args.archive)
    print(json.dumps(verify(args.archive), indent=2))
