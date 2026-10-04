"""Stage and install one approved Drupal ZIP in the empty primary TC preprod root.

Python 3.6 compatible. No SSH, production switch, database purge or opening.
All private configuration is entered by the responsible person on the server.
"""
import argparse
from datetime import datetime, timezone
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import re
import shutil
import stat
import subprocess
import tempfile
import zipfile

from delivery_shared import digest, require
from prepare_delivery import verify_candidate

DENY = b'Options -Indexes\nRequire all denied\n'
HERE = Path(__file__).resolve().parent


def contained(path, root):
    try:
        path.relative_to(root)
        return path != root
    except ValueError:
        return False


def no_links(path):
    for item in (path,) + tuple(path.parents):
        require(not item.is_symlink(), 'A path contains a symbolic link.')


def read_json(path):
    no_links(path)
    require(path.is_file(), 'Required JSON file is absent.')
    with path.open(encoding='utf-8') as stream:
        return json.load(stream)


def validate_profile(profile):
    require(profile.get('project') == 'tclongages'
            and profile.get('environment') == 'preproduction', 'Preproduction only.')
    account = profile.get('account', '')
    require(profile.get('accountRole') == 'primary_tc'
            and re.fullmatch(r'daje[0-9]{4}', account), 'Unqualified primary TC account.')
    host = profile.get('targetHost', '')
    require(host == 'preprod.tclongages.fr', 'Unreviewed target host.')
    home = PurePosixPath(profile.get('home', ''))
    require(str(home) == '/home2/' + account, 'Unexpected account home.')
    root = home / 'tcl-preproduction' / 'drupal'
    require(str(root) == profile.get('composerRoot')
            and str(root / 'web') == profile.get('documentRoot'), 'Unexpected document root.')
    require(profile.get('database') == account + '_tclpreprod'
            and profile.get('databaseUser') == account + '_tcl', 'Dedicated SQL scope required.')
    for field, length in (('sourceCommit', 40), ('archiveSha256', 64), ('manifestSha256', 64)):
        require(re.fullmatch('[a-f0-9]{' + str(length) + '}', profile.get(field, '')),
                'Invalid candidate identity: ' + field)
    require(profile.get('phpBinary') == '/usr/local/bin/php', 'Unqualified PHP executable.')
    return Path(str(home)), Path(str(root))


def runtime_gate(profile, qualification):
    require(os.name == 'posix', 'Linux runtime required.')
    import pwd
    require(pwd.getpwuid(os.geteuid()).pw_name == profile['account'], 'Wrong runtime account.')
    require(profile.get('installationAuthorizationRef', '').strip(), 'Installation agreement absent.')
    require(qualification.get('targetHost') == profile['targetHost']
            and qualification.get('environment') == 'preproduction', 'Wrong qualified target.')
    for field in ('accountRole', 'account', 'home', 'composerRoot', 'documentRoot', 'database', 'databaseUser'):
        require(qualification.get(field) == profile[field], 'Qualification scope mismatch: ' + field)
    for flag in ('dnsVerified', 'recognizedHttpsVerified', 'httpPhpVerified', 'webRootProtectionVerified'):
        require(qualification.get(flag) is True, 'Unqualified target: ' + flag)
    require(qualification.get('phpVersion', '').startswith('8.3.'), 'PHP 8.3 HTTP required.')
    require(qualification.get('checkedAt') and qualification.get('evidenceRef'), 'Qualification evidence absent.')
    checked = datetime.strptime(qualification['checkedAt'], '%Y-%m-%dT%H:%M:%SZ').replace(tzinfo=timezone.utc)
    age = (datetime.now(timezone.utc) - checked).total_seconds()
    require(0 <= age <= 3600, 'Target qualification must be refreshed within one hour.')


def empty_prepared_root(root):
    """Only the previously prepared closure and an empty cgi-bin are permitted."""
    no_links(root)
    require(root.is_dir() and (root / 'web').is_dir(), 'Prepared root absent.')
    require(set(p.name for p in root.iterdir()) == {'web'}, 'Composer root is not empty.')
    web = root / 'web'
    require(set(p.name for p in web.iterdir()) <= {'.htaccess', 'cgi-bin'}, 'Web root is not empty.')
    closure = web / '.htaccess'
    no_links(closure)
    require(closure.is_file() and closure.read_bytes() == DENY, 'Initial closure differs.')
    cgi = web / 'cgi-bin'
    if cgi.exists() or cgi.is_symlink():
        no_links(cgi)
        require(cgi.is_dir() and not any(cgi.iterdir()), 'cgi-bin contains data.')


def candidate(archive, profile):
    no_links(archive)
    require(digest(archive) == profile['archiveSha256'], 'ZIP digest differs.')
    result = verify_candidate(archive, profile['sourceCommit'])
    require(result['manifestSha256'] == profile['manifestSha256']
            and result['sourceClean'] is True, 'Candidate provenance differs.')
    return result


def private_write(path, data):
    with path.open('xb') as stream:
        os.chmod(path, 0o600)
        stream.write(data)


def receipt_write(path, record):
    private_write(path, (json.dumps(record, ensure_ascii=False, indent=2) + '\n').encode('utf-8'))


def stage(archive, profile, qualification):
    home, root = validate_profile(profile)
    runtime_gate(profile, qualification)
    no_links(home)
    empty_prepared_root(root)
    result = candidate(archive, profile)
    private = home / 'tcl-preproduction' / 'private'
    no_links(private)
    private.mkdir(mode=0o700, exist_ok=True)
    require(stat.S_IMODE(private.stat().st_mode) == 0o700, 'Private directory permissions differ.')
    state_path = private / 'first-install-state.json'
    require(not state_path.exists() and not state_path.is_symlink(), 'A previous attempt must be reviewed.')
    run = Path(tempfile.mkdtemp(prefix='first-install-', dir=str(private)))
    prepared = run / 'drupal'
    with zipfile.ZipFile(archive) as bundle:
        for member in bundle.infolist():
            if member.is_dir() or member.filename == 'manifest.json':
                continue
            dest = run / member.filename
            require(contained(dest, run), 'Extraction outside private staging.')
            dest.parent.mkdir(mode=0o755, parents=True, exist_ok=True)
            with bundle.open(member) as source, dest.open('xb') as output:
                shutil.copyfileobj(source, output)
            mode = 0o755 if member.filename.startswith('drupal/vendor/bin/') else 0o644
            os.chmod(dest, mode)
    # Re-read extracted bytes independently before changing the document root.
    with zipfile.ZipFile(archive) as bundle:
        manifest = json.loads(bundle.read('manifest.json'))
    for name, entry in manifest['files'].items():
        file = run / name
        require(file.is_file() and file.stat().st_size == entry['bytes']
                and digest(file) == entry['sha256'], 'Extracted bytes differ.')
    private_write(run / 'candidate.htaccess', (prepared / 'web' / '.htaccess').read_bytes())
    (prepared / 'web' / '.htaccess').write_bytes(DENY)
    for folder in ('files', 'temp', 'config-sync'):
        target = private / folder
        no_links(target)
        target.mkdir(mode=0o700, exist_ok=True)
        require(stat.S_IMODE(target.stat().st_mode) == 0o700, 'Private runtime permissions differ.')
    # This template contains no usable credential. Its editing is a human handoff.
    template = {'databasePassword': '', 'adminName': 'admin', 'adminMail': '', 'adminPassword': ''}
    example = private / 'hosting-input.example.json'
    if not example.exists():
        receipt_write(example, template)
    receipt_write(run / 'profile.json', profile)
    shutil.copyfile(HERE / 'first-install-hosting.php', run / 'first-install-hosting.php')
    os.chmod(run / 'first-install-hosting.php', 0o600)
    state = {'format': 'tcl-first-install-v1', 'status': 'prepared_private',
             'sourceCommit': profile['sourceCommit'], 'archiveSha256': profile['archiveSha256'],
             'manifestSha256': profile['manifestSha256'], 'runDirectory': str(run),
             'profileSha256': digest(run / 'profile.json'), 'helperSha256': digest(run / 'first-install-hosting.php'),
             'qualificationSha256': hashlib.sha256(json.dumps(qualification, sort_keys=True).encode()).hexdigest(),
             'checkedAt': datetime.now(timezone.utc).isoformat(), 'publicOpeningExecuted': False}
    # Exclusive ownership marker precedes any change of the existing root.
    # An interrupted attempt is retained for review, never silently repeated.
    receipt_write(state_path, state)
    # Recheck immediately before the two bounded renames; never merge trees.
    empty_prepared_root(root)
    root.rename(run / 'initial-root')
    try:
        prepared.rename(root)
    except Exception:
        (run / 'initial-root').rename(root)
        raise
    state['status'] = 'staged_closed'
    replacement = private / 'first-install-state.next.json'
    receipt_write(replacement, state)
    replacement.replace(state_path)
    return {**result, 'status': 'staged_closed', 'remoteTransferExecuted': True,
            'installationExecuted': False, 'publicOpeningExecuted': False}


def php_operation(operation, profile, qualification):
    home, root = validate_profile(profile)
    runtime_gate(profile, qualification)
    private = home / 'tcl-preproduction' / 'private'
    state_path = private / 'first-install-state.json'
    state = read_json(state_path)
    require(state.get('archiveSha256') == profile['archiveSha256']
            and state.get('sourceCommit') == profile['sourceCommit'], 'Installed candidate differs.')
    run = Path(state.get('runDirectory', ''))
    require(contained(run, private) and run.parent == private, 'Invalid private attempt directory.')
    no_links(run)
    helper = run / 'first-install-hosting.php'
    require(digest(helper) == state.get('helperSha256')
            and digest(run / 'profile.json') == state.get('profileSha256')
            and read_json(run / 'profile.json') == profile, 'Installation tools or profile differ.')
    if operation == 'install':
        require(state.get('status') == 'staged_closed'
                and (root / 'web' / '.htaccess').read_bytes() == DENY, 'Installation requires a closed fresh attempt.')
        require(not (root / 'web/sites/default/settings.php').exists(), 'Active settings already exist.')
    args = [profile['phpBinary'], '-d', 'zend.exception_ignore_args=1', str(helper), operation]
    process = subprocess.run(args, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=900)
    # Drupal errors can contain input values. Never print raw subprocess output.
    lines = process.stdout.decode('utf-8', errors='replace').splitlines()
    records = [line[len('TCL_RESULT:'):] for line in lines if line.startswith('TCL_RESULT:')]
    require(process.returncode == 0 and len(records) == 1, 'PHP operation failed; retain the closed attempt for review.')
    result = json.loads(records[0])
    if operation == 'install':
        # A fresh PHP process verifies the final settings and actual mail plugin.
        checked = subprocess.run(args[:-1] + ['check'], stdout=subprocess.PIPE,
                                 stderr=subprocess.PIPE, timeout=120)
        check_records = [line[len('TCL_RESULT:'):] for line in
                         checked.stdout.decode('utf-8', errors='replace').splitlines()
                         if line.startswith('TCL_RESULT:')]
        require(checked.returncode == 0 and len(check_records) == 1, 'Fresh Drupal check failed; keep Apache closed.')
        result = json.loads(check_records[0])
    keys = {'checkedAt', 'drupalVersion', 'phpVersion', 'environment', 'maintenanceEnabled',
            'tclSiteEnabled', 'mailNeutralized', 'registration', 'cronAutorunDisabled',
            'databaseConnectionVerified', 'sqlVersion', 'installationExecuted', 'publicOpeningExecuted'}
    require(set(result) == keys, 'Unexpected PHP receipt fields; do not disclose raw output.')
    require(all(result[key] is True for key in ('maintenanceEnabled', 'tclSiteEnabled', 'mailNeutralized',
                                              'cronAutorunDisabled', 'databaseConnectionVerified', 'installationExecuted'))
            and result['registration'] == 'admin_only' and result['environment'] == 'preproduction'
            and result['publicOpeningExecuted'] is False, 'Drupal closure checks failed.')
    stamp = datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%S%fZ')
    receipt_write(run / (operation + '-receipt-' + stamp + '.json'), result)
    if operation == 'install':
        # Maintenance has been verified before exposing the native login page.
        # Drupal remains closed for anonymous visitors; installer/update denied.
        core = (run / 'candidate.htaccess').read_bytes()
        closure = b'\n<FilesMatch "^(install|update|settings)\\.php$">\n  Require all denied\n</FilesMatch>\n'
        temporary = root / 'web' / '.htaccess.first-install'
        private_write(temporary, core + closure)
        os.chmod(temporary, 0o644)
        temporary.replace(root / 'web' / '.htaccess')
        state['status'] = 'installed_maintenance'
        state['installationExecuted'] = True
        state['checkedAt'] = datetime.now(timezone.utc).isoformat()
        replacement = private / 'first-install-state.next.json'
        receipt_write(replacement, state)
        replacement.replace(state_path)
    return result


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('operation', choices=('plan', 'stage', 'install', 'check'))
    parser.add_argument('--profile', type=Path, required=True)
    parser.add_argument('--qualification', type=Path)
    parser.add_argument('--archive', type=Path)
    args = parser.parse_args()
    profile = read_json(args.profile)
    validate_profile(profile)
    if args.operation == 'plan':
        require(args.archive, 'Candidate archive required.')
        result = {**candidate(args.archive, profile), 'status': 'local_plan_verified',
                  'installationAdapterAvailable': True, 'publicOpeningExecuted': False}
    else:
        require(args.qualification, 'Target qualification receipt required.')
        qualification = read_json(args.qualification)
        if args.operation == 'stage':
            require(args.archive, 'Candidate archive required.')
            result = stage(args.archive, profile, qualification)
        else:
            result = php_operation(args.operation, profile, qualification)
    print(json.dumps(result, ensure_ascii=False, indent=2))


if __name__ == '__main__':
    main()
