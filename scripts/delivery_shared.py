"""Site-independent gates adapted from the reviewed AVEREO Drupal delivery.

Canonical source for TCL's preparation and SSH qualification. No account,
database, document root, credential or production mutation is embedded here.
"""
# Keep the archive verifier importable by the qualified cPanel Python 3.6.
import gzip
import hashlib
import os
from pathlib import Path, PurePosixPath
import re
import stat
import subprocess
import tarfile
import time
import zipfile


def require(condition, message):
    if not condition:
        raise ValueError(message)


def digest(path):
    h = hashlib.sha256()
    with Path(path).open('rb') as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b''):
            h.update(chunk)
    return h.hexdigest()


def validate_revision(env):
    require(env.get('GITHUB_REF') == 'refs/heads/main', 'Main is required.')
    sha = env.get('APPROVED_SHA', '')
    require(re.fullmatch(r'[a-f0-9]{40}', sha) and sha == env.get('GITHUB_SHA'),
            'Running SHA must match the reviewed SHA.')
    return sha


def archive_path(name, allowed):
    require(isinstance(name, str) and name and not re.search(r'[\\:\x00-\x1f]', name),
            'Unsafe archive path.')
    path = PurePosixPath(name)
    parts = name.rstrip('/').split('/')
    require(not path.is_absolute() and all(p not in ('', '.', '..') for p in parts),
            'Unsafe archive path.')
    require(parts[0] in allowed, 'Entry outside reviewed scope.')
    return path.as_posix()


def verify_zip(path, allowed, *, max_entries=60000, max_bytes=1024**3, max_ratio=1000):
    """Read every member to check CRC and return its actual size/hash inventory."""
    inventory, seen, total = {}, set(), 0
    with zipfile.ZipFile(path) as archive:
        entries = archive.infolist()
        require(0 < len(entries) <= max_entries, 'Invalid archive entry count.')
        for entry in entries:
            name = archive_path(entry.orig_filename, allowed)
            require(entry.orig_filename == entry.filename, 'Archive filename normalized implicitly.')
            require(name not in seen, 'Duplicate archive path.')
            seen.add(name)
            mode = entry.external_attr >> 16
            kind = stat.S_IFMT(mode)
            require(kind in (0, stat.S_IFREG, stat.S_IFDIR), 'Link or special archive entry.')
            require(not entry.flag_bits & 1, 'Encrypted archive rejected.')
            total += entry.file_size
            require(total <= max_bytes, 'Archive size limit exceeded.')
            require(entry.file_size <= max_ratio * max(1, entry.compress_size),
                    'Archive expansion limit exceeded.')
            if entry.is_dir():
                require(entry.file_size == 0, 'Invalid directory entry.')
                continue
            h, size = hashlib.sha256(), 0
            with archive.open(entry) as stream:
                for chunk in iter(lambda: stream.read(1024 * 1024), b''):
                    size += len(chunk); h.update(chunk)
            require(size == entry.file_size, 'Truncated archive file.')
            inventory[name] = {'bytes': size, 'sha256': h.hexdigest()}
    require(inventory, 'Archive contains no regular file.')
    return inventory


def verify_backup_archives(sql_path, tar_path, expected_tables, expected_files, allowed):
    """AVEREO integrity algorithm; this alone never attests to restoration."""
    definitions = set()
    sql_bytes = 0
    with gzip.open(sql_path, 'rb') as stream:
        for line in stream:
            sql_bytes += len(line)
            require(sql_bytes <= 1024**3, 'SQL size limit exceeded.')
            match = re.match(rb'CREATE TABLE `([A-Za-z0-9_]+)`', line)
            if match:
                name = match.group(1).decode('ascii')
                require(name not in definitions, 'Duplicate SQL definition.')
                definitions.add(name)
    require(definitions and definitions == set(expected_tables), 'SQL inventory mismatch.')
    files, seen, total = {}, set(), 0
    with tarfile.open(tar_path, 'r:gz') as archive:
        for member in archive:
            name = archive_path(member.name, allowed)
            require(name not in seen and len(seen) < 60000, 'Duplicate or oversized backup.')
            seen.add(name)
            require(member.isfile() or member.isdir(), 'Backup links/devices rejected.')
            total += member.size
            require(total <= 1024**3, 'Backup size limit exceeded.')
            if member.isfile():
                h, size = hashlib.sha256(), 0
                with archive.extractfile(member) as stream:
                    for chunk in iter(lambda: stream.read(1024 * 1024), b''):
                        size += len(chunk); h.update(chunk)
                require(size == member.size, 'Truncated backup file.')
                files[name] = h.hexdigest()
    # tar EOF does not establish gzip-footer integrity.
    with gzip.open(tar_path, 'rb') as stream:
        while stream.read(1024 * 1024):
            pass
    require(files == expected_files, 'Backup file inventory mismatch.')
    return {'integrityVerified': True, 'restorationTested': False}


def ssh_endpoint(host, user, port):
    require(re.fullmatch(r'[a-z0-9](?:[a-z0-9.-]{0,251}[a-z0-9])?', host or ''), 'Invalid SSH host.')
    require('..' not in host and all(part for part in host.split('.')), 'Invalid SSH host.')
    require(re.fullmatch(r'[a-z][a-z0-9_]{0,31}', user or ''), 'Invalid SSH user.')
    require(re.fullmatch(r'[0-9]{1,5}', str(port)) and 1 <= int(port) <= 65535, 'Invalid SSH port.')
    return f'{user}@{host}'


def ssh_options(directory, port):
    return ['-i', str(directory/'key'), '-p', str(port), '-o', 'BatchMode=yes',
            '-o', 'StrictHostKeyChecking=yes', '-o', 'IdentitiesOnly=yes',
            '-o', 'ConnectTimeout=5', '-o', 'ConnectionAttempts=1',
            '-o', 'UserKnownHostsFile='+str(directory/'known_hosts')]


def private_ssh_directory(env):
    base = Path(env.get('RUNNER_TEMP', '')).resolve()
    require(env.get('RUNNER_TEMP') and base.is_dir(), 'Runner private directory missing.')
    return base/'o2switch-qualified-ssh'


def setup_ssh(env):
    host, user, port = (env.get(k, '') for k in ('SSH_HOST', 'SSH_USER', 'SSH_PORT'))
    endpoint = ssh_endpoint(host, user, port)
    key, hosts = env.get('SSH_KEY', ''), env.get('KNOWN_HOSTS', '')
    require(key.strip() and hosts.strip(), 'SSH secrets must be configured in the TCL environment.')
    directory = private_ssh_directory(env)
    require(not directory.exists() and not directory.is_symlink(), 'Private SSH directory already exists.')
    directory.mkdir(mode=0o700)
    try:
        (directory/'.owner').write_text('qualified-ssh-v1\n', encoding='ascii')
        for name, value in [('key', key), ('known_hosts', hosts)]:
            file = directory/name
            with file.open('x', encoding='utf-8', newline='\n') as stream:
                stream.write(value.rstrip()+'\n')
            os.chmod(file, 0o600)
        # Neither key material nor raw command output is logged.
        for args in (['ssh-keygen', '-y', '-P', '', '-f', str(directory/'key')],
                     ['ssh-keygen', '-F', host if int(port) == 22 else f'[{host}]:{port}',
                      '-f', str(directory/'known_hosts')]):
            result = subprocess.run(args, capture_output=True)
            require(result.returncode == 0, 'SSH key or reviewed host entry invalid.')
    except Exception:
        # Only the directory just created here is owned by this attempt.
        cleanup_ssh(env)
        raise
    return directory, endpoint


def cleanup_ssh(env):
    directory = private_ssh_directory(env)
    require(not directory.is_symlink(), 'Unsafe cleanup directory.')
    if directory.exists():
        require(directory.resolve() == directory and directory.is_dir(), 'Unsafe cleanup path.')
        owner = directory/'.owner'
        # The composite's unconditional cleanup must also preserve directories
        # that caused setup to refuse ownership before creating any credentials.
        if not owner.exists():
            return
        require(owner.is_file() and not owner.is_symlink()
                and owner.read_text(encoding='ascii') == 'qualified-ssh-v1\n', 'Unknown SSH directory owner.')
        require(set(p.name for p in directory.iterdir()) <= {'key', 'known_hosts', '.owner'}, 'Unexpected cleanup contents.')
        for path in directory.iterdir():
            require(path.is_file() and not path.is_symlink(), 'Unsafe cleanup file.')
            path.unlink()
        directory.rmdir()


def qualify_ssh(env):
    """Only verify SSH and the remote account; transfer and delivery are separate."""
    wait = env.get('SSH_WAIT_SECONDS', '0')
    require(re.fullmatch(r'[0-9]{1,3}', wait) and int(wait) <= 300, 'Invalid SSH wait.')
    directory, endpoint = setup_ssh(env)
    deadline = time.monotonic() + int(wait)
    try:
        while True:
            result = subprocess.run(['ssh', *ssh_options(directory, env['SSH_PORT']), endpoint, 'id -un'],
                                    capture_output=True, timeout=15)
            if result.returncode == 0:
                require(result.stdout.decode().strip() == env['SSH_USER'], 'Wrong remote account.')
                return {'sshHostVerified': True, 'sshAccountVerified': True,
                        'remoteTransferExecuted': False, 'deploymentExecuted': False}
            if time.monotonic() >= deadline:
                raise ValueError('SSH unavailable; authorize only this runner IP in cPanel, then retry.')
            time.sleep(min(10, max(0, deadline-time.monotonic())))
    finally:
        cleanup_ssh(env)
