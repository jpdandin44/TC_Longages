"""Restore a verified TCL file backup into a new private copy only.

Python 3.6 compatible. No SQL write, original replacement or public opening.
The SQL restore and Drupal bootstrap remain separate required checks.
"""
import argparse
from datetime import datetime, timezone
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import re
import stat
import tarfile
import tempfile


def need(condition, message):
    if not condition:
        raise ValueError(message)


def sha256(path):
    result = hashlib.sha256()
    with path.open('rb') as stream:
        for part in iter(lambda: stream.read(1024 * 1024), b''):
            result.update(part)
    return result.hexdigest()


def no_links(path):
    for parent in (path,) + tuple(path.parents):
        need(not parent.is_symlink(), 'Redirected private path rejected.')


def entry_path(name):
    path = PurePosixPath(name)
    need(not path.is_absolute() and path.as_posix() == name
         and len(path.parts) > 1 and all(p not in ('', '.', '..') for p in path.parts)
         and '\\' not in name and '\x00' not in name
         and path.parts[0] in ('drupal', 'runtime', 'waiting', 'files', 'config-sync'),
         'Unsafe backup entry.')
    return path


def restore_files(backup, parent, sql_digest, files_digest):
    no_links(backup)
    no_links(parent)
    need(backup.is_dir() and parent.is_dir(), 'Private directories required.')
    need(all(re.fullmatch(r'[a-f0-9]{64}', value) for value in (sql_digest, files_digest)),
         'Reviewed backup digests required.')
    for name in ('receipt.json', 'database.sql.gz', 'files.tar.gz'):
        path = backup / name
        need(path.is_file() and not path.is_symlink(), 'Backup file missing or redirected.')
    receipt = json.loads((backup / 'receipt.json').read_text(encoding='utf-8'))
    need(receipt.get('format') == 'tcl-private-backup-v1'
         and receipt.get('integrityVerified') is True, 'Unqualified backup receipt.')
    need(receipt.get('filesSha256') == files_digest == sha256(backup / 'files.tar.gz')
         and receipt.get('sqlSha256') == sql_digest == sha256(backup / 'database.sql.gz'),
         'Reviewed backup bytes differ.')
    inventory = receipt.get('inventory')
    need(isinstance(inventory, dict) and 0 < len(inventory) <= 100000, 'Invalid file inventory.')
    need(sum(item['bytes'] for item in inventory.values()) <= 2 * 1024**3, 'Backup exceeds reviewed size limit.')
    for name, item in inventory.items():
        entry_path(name)
        need(isinstance(item.get('bytes'), int) and item['bytes'] >= 0
             and re.fullmatch(r'[a-f0-9]{64}', item.get('sha256', '')), 'Invalid file record.')
    previous_mask = os.umask(0o077)
    try:
        with tarfile.open(str(backup / 'files.tar.gz'), 'r:gz') as archive:
            members = archive.getmembers()
            names = [member.name for member in members]
            need(len(names) == len(set(names)) == len(inventory)
                 and set(names) == set(inventory), 'Archive inventory mismatch.')
            for member in members:
                entry_path(member.name)
                need(member.isfile() and member.size == inventory[member.name]['bytes'],
                     'Non-regular or unexpected backup entry.')
            # Every target is newly owned; existing directories are never replaced.
            target = Path(tempfile.mkdtemp(prefix='restore-', dir=str(parent)))
            for member in members:
                path = target.joinpath(*entry_path(member.name).parts)
                path.parent.mkdir(mode=0o700, parents=True, exist_ok=True)
                with archive.extractfile(member) as source, path.open('xb') as output:
                    for part in iter(lambda: source.read(1024 * 1024), b''):
                        output.write(part)
                need(path.stat().st_size == inventory[member.name]['bytes']
                     and sha256(path) == inventory[member.name]['sha256'], 'Restored file bytes differ.')
                os.chmod(str(path), 0o600)
        result = {'format': 'tcl-private-file-restore-v1',
            'checkedAt': datetime.now(timezone.utc).isoformat(), 'directory': str(target),
            'backupDirectory': str(backup), 'sqlSha256': sql_digest, 'filesSha256': files_digest,
            'regularFiles': len(inventory), 'filesRestoredAndVerified': True,
            'databaseRestored': False, 'drupalBootstrapped': False,
            'restorationTested': False, 'deploymentExecuted': False}
        with (target / 'restore-receipt.json').open('x') as stream:
            json.dump(result, stream, sort_keys=True)
        return result
    finally:
        os.umask(previous_mask)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--account', required=True)
    parser.add_argument('--backup-name', required=True)
    parser.add_argument('--sql-sha256', required=True)
    parser.add_argument('--files-sha256', required=True)
    args = parser.parse_args()
    need(re.fullmatch(r'daje[0-9]{4}', args.account), 'TC account required.')
    need(re.fullmatch(r'[0-9]{8}T[0-9]{6}Z', args.backup_name), 'Exact backup name required.')
    import pwd
    need(pwd.getpwuid(os.geteuid()).pw_name == args.account, 'Wrong hosted identity.')
    private = Path('/home2') / args.account / 'tcl-preproduction/private'
    no_links(private)
    need(private.is_dir() and stat.S_IMODE(private.stat().st_mode) == 0o700, 'Private root must be 0700.')
    parent = private / 'restorations'
    no_links(parent)
    parent.mkdir(mode=0o700, exist_ok=True)
    need(stat.S_IMODE(parent.stat().st_mode) == 0o700, 'Restore parent must be 0700.')
    print(json.dumps(restore_files(private / 'backups' / args.backup_name, parent,
                                 args.sql_sha256, args.files_sha256)))


if __name__ == '__main__':
    main()
