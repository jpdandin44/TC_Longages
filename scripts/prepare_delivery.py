"""Build an unconfigured TCL Drupal candidate or qualify read-only SSH."""
# Verification also runs in cPanel with Python 3.6; building stays in CI.
import argparse
from datetime import datetime, timezone
import hashlib
import json
import os
from pathlib import Path
import re
import stat
import subprocess
import tempfile
import zipfile
from delivery_shared import digest, require, validate_revision, verify_zip, qualify_ssh, cleanup_ssh, ssh_endpoint

ROOT = Path(__file__).resolve().parent.parent
PAGES = ('index', 'competitions', 'calendrier', 'disponibilites', 'equipes', 'espace', 'contact')


def validate_inputs(env):
    sha = validate_revision(env)
    operation = env.get('OPERATION', '')
    require(operation in ('build', 'qualify-ssh'), 'Unknown preparation operation.')
    if operation == 'qualify-ssh':
        require(env.get('CONFIRMATION') == 'QUALIFY SSH TC Longages', 'Explicit SSH qualification required.')
        profile = json.loads(env.get('SSH_PROFILE', '{}'))
        require(profile.get('project') == 'tclongages' and profile.get('environment') == 'preproduction'
                and profile.get('targetHost') == 'preprod.tclongages.fr', 'Wrong TCL target profile.')
        require(profile.get('authorizationRef', '').strip(), 'Reviewed access authorization missing.')
        ssh_endpoint(profile.get('sshHost'), profile.get('sshUser'), profile.get('sshPort'))
        require(profile.get('sshHost') == env.get('SSH_HOST')
                and profile.get('sshUser') == env.get('SSH_USER')
                and str(profile.get('sshPort')) == env.get('SSH_PORT'), 'SSH endpoint differs from reviewed profile.')
    return sha


def verify_candidate(path, expected_sha):
    inventory = verify_zip(path, {'drupal', 'manifest.json'})
    with zipfile.ZipFile(path) as archive:
        manifest = json.loads(archive.read('manifest.json'))
    require(manifest.get('format') == 'tcl-drupal-delivery-v1' and manifest.get('sourceCommit') == expected_sha,
            'Wrong candidate provenance.')
    require(manifest.get('configured') is False and manifest.get('publicOpeningAuthorized') is False,
            'Unconfigured candidate required.')
    files = {key: value for key, value in inventory.items() if key != 'manifest.json'}
    require(files == manifest.get('files'), 'Candidate bytes differ from manifest.')
    required = {'drupal/composer.lock', 'drupal/composer.json', 'drupal/vendor/autoload.php',
                'drupal/web/core/lib/Drupal.php', 'drupal/web/.htaccess',
                'drupal/web/modules/custom/tcl_site/tcl_site.info.yml',
                'drupal/config/settings.hosting.example.php',
                *(f'drupal/site-pages/{page}.html' for page in PAGES)}
    require(required <= files.keys(), 'Candidate is incomplete.')
    forbidden = {'drupal/web/sites/default/settings.php', 'drupal/web/sites/default/services.yml'}
    require(not forbidden & files.keys(), 'Active settings forbidden in candidate.')
    for name in files:
        require(not name.startswith('drupal/web/sites/default/files/'), 'Public runtime data forbidden.')
        require('/.local/' not in name and '/.git/' not in name and '/.env' not in name,
                'Private file in candidate.')
    return {'sourceCommit': expected_sha, 'sourceClean': manifest.get('sourceClean'), 'archiveSha256': digest(path),
            'manifestSha256': inventory['manifest.json']['sha256'], 'regularFiles': len(files),
            'status': 'candidate_verified_unconfigured', 'deploymentExecuted': False}


def verify_receipt(archive, receipt):
    """Bind an unchanged archive to its CI receipt, never to an approval."""
    require(isinstance(receipt, dict), 'Candidate receipt must be an object.')
    require(receipt.get('status') == 'candidate_verified_unconfigured'
            and receipt.get('deploymentExecuted') is False,
            'Unconfigured preparation receipt required.')
    require(receipt.get('sourceClean') is True, 'Clean source receipt required.')
    sha = receipt.get('sourceCommit')
    require(isinstance(sha, str) and re.fullmatch(r'[a-f0-9]{40}', sha),
            'Full source commit required in receipt.')
    for key in ('archiveSha256', 'manifestSha256'):
        value = receipt.get(key)
        require(isinstance(value, str) and re.fullmatch(r'[a-f0-9]{64}', value),
                'Full digest required in receipt: ' + key)
    require(digest(archive) == receipt['archiveSha256'], 'Archive differs from preparation receipt.')
    result = verify_candidate(archive, sha)
    require(result['sourceClean'] is True, 'Clean manifest source required.')
    require(result['manifestSha256'] == receipt['manifestSha256']
            and result['regularFiles'] == receipt.get('regularFiles'),
            'Manifest or inventory differs from preparation receipt.')
    return dict(result, receiptVerified=True)


def build_candidate(output, root=ROOT):
    """Publish only a fully verified ZIP, refusing existing/racing destinations."""
    output = Path(output)
    require(not os.path.lexists(str(output)), 'Candidate already exists; reuse its ZIP and receipt.')
    output.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory(prefix='.tcl-build-', dir=str(output.parent)) as temporary:
        staged = Path(temporary) / 'candidate.zip'
        result = _build_candidate(staged, root)
        # Same filesystem: hard-link publication is atomic and cannot replace a
        # previously accepted artifact, even if another builder wins the race.
        try:
            os.link(str(staged), str(output))
        except FileExistsError:
            raise ValueError('Candidate already exists; reuse its ZIP and receipt.')
    return result


def _build_candidate(output, root=ROOT):
    """Git sources + installed dependencies + generated pages only; never whole checkout."""
    source = root/'drupal'
    revision = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=root).decode().strip()
    clean = not subprocess.check_output(['git', 'status', '--porcelain'], cwd=root).strip()
    if os.environ.get('GITHUB_ACTIONS') == 'true':
        require(revision == os.environ.get('APPROVED_SHA') and clean,
                'Checkout differs from reviewed commit before packaging.')
    tracked = subprocess.check_output(['git', 'ls-files', '-z', '--', 'drupal'], cwd=root).decode().split('\0')
    allowed_sources = [p for p in tracked if p and (p in ('drupal/composer.json', 'drupal/composer.lock')
        or p.startswith('drupal/web/') or p == 'drupal/config/settings.hosting.example.php')]
    paths = {p: root/p for p in allowed_sources}
    for directory in ('vendor', 'web/core', 'web/modules/contrib', 'web/themes/contrib', 'web/libraries'):
        target = source/directory
        if target.exists():
            for path in target.rglob('*'):
                require(not path.is_symlink(), 'Dependency link rejected.')
                if path.is_file():
                    # Dependency fixtures can ship .env examples; keep even
                    # those out rather than relaxing the candidate verifier.
                    if any(part in ('.local', '.git') or part.startswith('.env')
                           for part in path.relative_to(source).parts):
                        continue
                    paths[path.relative_to(root).as_posix()] = path
    for page in PAGES:
        paths[f'drupal/site-pages/{page}.html'] = root/'.local/drupal-public-candidate/site-pages'/f'{page}.html'
    files = {}
    output.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(output, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=6) as archive:
        for name, path in sorted(paths.items()):
            scope = root/'.local/drupal-public-candidate/site-pages' if name.startswith('drupal/site-pages/') else source
            require(path.is_file() and not path.is_symlink() and path.resolve().is_relative_to(scope.resolve()),
                    'Source missing or outside Drupal project.')
            data = path.read_bytes()
            info = zipfile.ZipInfo(name)
            info.create_system = 3
            mode = 0o755 if name.startswith('drupal/vendor/bin/') else 0o644
            info.external_attr = (stat.S_IFREG | mode) << 16
            info.compress_type = zipfile.ZIP_DEFLATED
            archive.writestr(info, data)
            files[name] = {'bytes': len(data), 'sha256': hashlib.sha256(data).hexdigest()}
        manifest = {'format': 'tcl-drupal-delivery-v1', 'sourceCommit': revision, 'sourceClean': clean,
                    'createdAt': datetime.now(timezone.utc).isoformat(), 'files': files,
                    'configured': False, 'publicOpeningAuthorized': False,
                    'missing': ['hosting profile', 'private settings', 'SQL database',
                                'initial restorable backup', 'preproduction qualification']}
        archive.writestr('manifest.json', json.dumps(manifest, ensure_ascii=False, sort_keys=True))
    return verify_candidate(output, revision)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('operation', choices=['validate', 'build', 'verify', 'verify-receipt', 'qualify-ssh', 'cleanup'])
    parser.add_argument('--archive', type=Path, default=ROOT/'.local/delivery/tc-longages-drupal.zip')
    parser.add_argument('--sha')
    parser.add_argument('--receipt', type=Path)
    args = parser.parse_args()
    if args.operation == 'validate':
        result = {'sourceCommit': validate_inputs(os.environ), 'inputsValidated': True}
    elif args.operation == 'build':
        if os.environ.get('GITHUB_ACTIONS') == 'true':
            validate_inputs(os.environ)
        result = build_candidate(args.archive)
    elif args.operation == 'verify':
        result = verify_candidate(args.archive, args.sha)
    elif args.operation == 'verify-receipt':
        if args.receipt is None:
            parser.error('--receipt is required for verify-receipt')
        result = verify_receipt(args.archive, json.loads(args.receipt.read_text(encoding='utf-8-sig')))
    elif args.operation == 'cleanup':
        cleanup_ssh(os.environ); result = {'cleanupCompleted': True}
    else:
        validate_inputs(os.environ)
        result = qualify_ssh(os.environ)
    print(json.dumps(result, indent=2))


if __name__ == '__main__':
    main()
