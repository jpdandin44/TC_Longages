"""Prepare the approved immutable TCL ZIP in a new, unserved production root.

No database import, document-root change, maintenance removal or public opening.
Python 3.6 compatible; secrets are personally entered in the private input file.
"""
import argparse
from datetime import datetime, timezone
import json
import os
from pathlib import Path, PurePosixPath
import re
import stat
import zipfile

from delivery_shared import digest, require
from prepare_delivery import verify_receipt


def no_links(path):
    for item in (path,) + tuple(path.parents):
        require(not item.is_symlink(), 'Redirected staging path.')


def qualify_profile(profile):
    require(profile.get('project') == 'tclongages'
            and profile.get('environment') == 'production', 'Production TCL scope required.')
    account = profile.get('account', '')
    require(re.fullmatch(r'daje[0-9]{4}', account), 'Primary TC account required.')
    for key, size in [('sourceCommit', 40), ('archiveSha256', 64), ('manifestSha256', 64)]:
        require(re.fullmatch(r'[a-f0-9]{' + str(size) + '}', profile.get(key, '')), 'Full candidate identity required.')
    home = PurePosixPath('/home2') / account
    release = home / 'tcl-production/releases' / profile['archiveSha256'][:16]
    require(profile.get('composerRoot') == str(release / 'drupal')
            and profile.get('documentRoot') == str(release / 'drupal/web')
            and profile.get('privateRoot') == str(home / 'tcl-production/private'), 'Unreviewed production path.')
    require(profile.get('database') == account + '_tclprod'
            and profile.get('databaseUser') == account + '_tclprod', 'Dedicated production SQL required.')
    require(profile.get('targetHost') == 'tclongages.fr'
            and profile.get('authorizationRef', '').strip(), 'Exact target and preparation agreement required.')
    require(profile.get('officialRootChangeAllowed') is False
            and profile.get('publicOpeningAllowed') is False, 'Preparation cannot authorize a switch or opening.')
    return Path(str(home)), Path(str(release))


def private_write(path, data):
    no_links(path)
    with path.open('xb') as stream:
        os.chmod(str(path), 0o600)
        stream.write(data)


def stage(archive, receipt, profile):
    home, release = qualify_profile(profile)
    require(os.name == 'posix', 'Hosted Linux identity required.')
    import pwd
    require(pwd.getpwuid(os.geteuid()).pw_name == profile['account'], 'Wrong hosted account.')
    require(archive == home / 'tcl-preproduction/private/tc-longages-drupal.zip', 'Reviewed source archive required.')
    no_links(archive)
    require(archive.is_file(), 'Source archive missing.')
    result = verify_receipt(archive, receipt)
    for key in ('sourceCommit', 'archiveSha256', 'manifestSha256'):
        require(result[key] == profile[key], 'Reviewed candidate differs: ' + key)
    no_links(release)
    require(not os.path.lexists(str(release)), 'An existing release must never be replaced.')
    private = Path(profile['privateRoot'])
    no_links(private)
    previous_mask = os.umask(0o077)
    try:
        private.mkdir(mode=0o700, parents=True, exist_ok=True)
        require(stat.S_IMODE(private.stat().st_mode) == 0o700, 'Private root must be 0700.')
        require(not os.path.lexists(str(private / 'hosting-input.json'))
                and not os.path.lexists(str(private / 'production-stage.json')), 'Previous preparation needs review.')
        release.mkdir(mode=0o700, parents=True)
        with zipfile.ZipFile(archive) as bundle:
            manifest = json.loads(bundle.read('manifest.json'))
            for name, entry in manifest['files'].items():
                target = release.joinpath(*name.split('/'))
                target.parent.mkdir(mode=0o700, parents=True, exist_ok=True)
                private_write(target, bundle.read(name))
                require(target.stat().st_size == entry['bytes'] and digest(target) == entry['sha256'],
                        'Staged file differs from immutable manifest.')
        # No HTTP route points here; owner-only permissions stay until qualification.
        private_write(private / 'profile.json', (json.dumps(profile, indent=2) + '\n').encode('utf-8'))
        private_write(private / 'hosting-input.json', b'{\n  "databasePassword": ""\n}\n')
        for name in ('files', 'temp', 'config-sync'):
            target = private / name
            no_links(target)
            target.mkdir(mode=0o700, exist_ok=True)
        record = dict(result, format='tcl-production-stage-v1',
            preparedAt=datetime.now(timezone.utc).isoformat(), composerRoot=profile['composerRoot'],
            documentRoot=profile['documentRoot'], account=profile['account'],
            status='code_verified_private_input_pending', configurationActivated=False,
            databaseRestored=False, officialRootChanged=False, publicOpeningExecuted=False)
        private_write(private / 'production-stage.json', (json.dumps(record, indent=2) + '\n').encode('utf-8'))
        return record
    finally:
        os.umask(previous_mask)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--profile', required=True, type=Path)
    parser.add_argument('--receipt', required=True, type=Path)
    args = parser.parse_args()
    for path in (args.profile, args.receipt):
        no_links(path)
        require(path.is_file(), 'Preparation input absent.')
    profile = json.loads(args.profile.read_text(encoding='utf-8'))
    receipt = json.loads(args.receipt.read_text(encoding='utf-8'))
    print(json.dumps(stage(Path('/home2') / profile['account'] /
                          'tcl-preproduction/private/tc-longages-drupal.zip', receipt, profile)))


if __name__ == '__main__':
    main()
