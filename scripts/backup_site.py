"""Private backup of the installed TCL preproduction; no restore or deployment.

Server-compatible Python 3.6. Secrets and SQL stay below the existing private
directory. Drush is invoked through PHP, avoiding Composer proxy mode assumptions.
"""
import argparse
from datetime import datetime, timezone
import gzip
import hashlib
import json
import os
from pathlib import Path
import re
import stat
import subprocess
import tarfile


def need(condition, message):
    if not condition:
        raise ValueError(message)


def sha256(path):
    result = hashlib.sha256()
    with path.open('rb') as stream:
        for part in iter(lambda: stream.read(1024 * 1024), b''):
            result.update(part)
    return result.hexdigest()


def regular_tree(root):
    """Reject links/special files instead of backing up external targets."""
    need(root.is_dir() and not root.is_symlink(), 'Backup source must be a real directory.')
    paths = []
    for path in sorted(root.rglob('*')):
        mode = path.lstat().st_mode
        need(stat.S_ISREG(mode) or stat.S_ISDIR(mode), 'Link or special backup source rejected.')
        if stat.S_ISREG(mode):
            paths.append(path)
    return paths


def archive_sources(output, sources):
    inventory = {}
    need(not os.path.lexists(str(output)), 'Backup archive already exists.')
    # x mode and private umask prevent overwrite and transient world readability.
    with tarfile.open(str(output), 'x:gz') as archive:
        for name, directory in sources.items():
            need(re.fullmatch(r'[a-z-]+', name), 'Invalid backup prefix.')
            for path in regular_tree(directory):
                relative = name + '/' + path.relative_to(directory).as_posix()
                before = {'bytes': path.stat().st_size, 'sha256': sha256(path)}
                archive.add(str(path), arcname=relative, recursive=False)
                need(before == {'bytes': path.stat().st_size, 'sha256': sha256(path)},
                     'Backup source changed during snapshot.')
                inventory[relative] = before
    os.chmod(str(output), 0o600)
    with tarfile.open(str(output), 'r:gz') as archive:
        members = archive.getmembers()
        need(len(members) == len(inventory), 'Backup inventory count differs.')
        for member in members:
            need(member.isfile() and member.name in inventory, 'Unexpected backup entry.')
            with archive.extractfile(member) as stream:
                result = hashlib.sha256()
                size = 0
                for part in iter(lambda: stream.read(1024 * 1024), b''):
                    size += len(part)
                    result.update(part)
            need(inventory[member.name] == {'bytes': size, 'sha256': result.hexdigest()},
                 'Backup bytes differ from source inventory.')
    return inventory


def qualify(account, root, private, runtime, waiting):
    need(re.fullmatch(r'daje[0-9]{4,8}', account), 'Wrong TC account.')
    home = Path('/home2') / account
    need(root == home / 'tcl-preproduction/drupal'
         and private == home / 'tcl-preproduction/private'
         and waiting == home / 'public_html', 'Wrong backup target.')
    need(runtime.parent == private and re.fullmatch(r'(?:runtime-files|first-install)-[a-z0-9]+', runtime.name),
         'Wrong private runtime directory.')
    for directory in (root, private, runtime, waiting):
        need(directory.is_dir() and not directory.is_symlink()
             and directory.resolve() == directory, 'Backup directory missing or redirected.')
    need(stat.S_IMODE(private.stat().st_mode) == 0o700, 'Private directory must have mode 0700.')
    need((runtime / 'database.json').is_file() and (runtime / 'runtime-settings.php').is_file(),
         'Installed private settings missing.')
    # Read only the settings loader path, never the credentials it loads.
    need(str(runtime / 'runtime-settings.php') in
         (root / 'web/sites/default/settings.php').read_text(), 'Runtime differs from installed settings.')
    need(hasattr(os, 'geteuid'), 'Hosted Linux identity required.')
    import pwd
    need(pwd.getpwuid(os.geteuid()).pw_name == account, 'Backup process belongs to another account.')


def backup(account, root, private, runtime, waiting, php):
    qualify(account, root, private, runtime, waiting)
    need(php == Path('/usr/local/bin/php') and php.is_file(), 'Qualified PHP required.')
    previous_mask = os.umask(0o077)
    try:
        parent = private / 'backups'
        need(not parent.is_symlink(), 'Redirected backup directory.')
        parent.mkdir(mode=0o700, exist_ok=True)
        need(stat.S_IMODE(parent.stat().st_mode) == 0o700, 'Private backup directory must have mode 0700.')
        name = datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%SZ')
        output = parent / name
        output.mkdir(mode=0o700)
        sql = output / 'database.sql.gz'
        result = subprocess.run([str(php), str(root / 'vendor/drush/drush/drush.php'),
            '--root=' + str(root / 'web'), '--uri=https://preprod.tclongages.fr',
            'sql:dump', '--gzip', '--result-file=' + str(output / 'database.sql'),
            '--extra-dump=--single-transaction --skip-lock-tables --no-tablespaces'],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=120)
        need(result.returncode == 0 and sql.is_file(), 'SQL backup failed; no deployment allowed.')
        os.chmod(str(sql), 0o600)
        sql_bytes = 0
        with gzip.open(str(sql), 'rb') as stream:
            for part in iter(lambda: stream.read(1024 * 1024), b''):
                sql_bytes += len(part)
        need(sql_bytes > 0, 'Empty SQL backup.')
        sources = {'drupal': root, 'runtime': runtime, 'waiting': waiting}
        for name in ('files', 'config-sync'):
            if (private / name).is_dir():
                sources[name] = private / name
        archive = output / 'files.tar.gz'
        inventory = archive_sources(archive, sources)
        receipt = {'format': 'tcl-private-backup-v1', 'createdAt': datetime.now(timezone.utc).isoformat(),
            'target': 'https://preprod.tclongages.fr/', 'account': account,
            'directory': str(output), 'sqlSha256': sha256(sql), 'sqlUncompressedBytes': sql_bytes,
            'filesSha256': sha256(archive), 'inventory': inventory,
            'integrityVerified': True, 'restorationTested': False, 'deploymentExecuted': False}
        with (output / 'receipt.json').open('x') as stream:
            json.dump(receipt, stream, sort_keys=True)
        # Keep filenames/digests only on stdout; no SQL, settings or inventory contents.
        return {key: value for key, value in receipt.items() if key != 'inventory'}
    finally:
        os.umask(previous_mask)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--account', required=True)
    parser.add_argument('--runtime', type=Path)
    args = parser.parse_args()
    home = Path('/home2') / args.account
    runtime = args.runtime
    if runtime is None:
        loader = (home / 'tcl-preproduction/drupal/web/sites/default/settings.php').read_text()
        matches = re.findall(r"require\s+'([^']+/runtime-settings\.php)'\s*;", loader)
        need(len(matches) == 1, 'Single installed runtime loader required.')
        runtime = Path(matches[0]).parent
    print(json.dumps(backup(args.account, home / 'tcl-preproduction/drupal',
        home / 'tcl-preproduction/private', runtime, home / 'public_html', Path('/usr/local/bin/php'))))


if __name__ == '__main__':
    main()
