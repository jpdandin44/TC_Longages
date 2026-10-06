"""Stage, recover and apply one pinned additive patch to TCL preproduction only.

Server Python 3.6 compatible. No production write, new database/user, new
credential, public opening or uncontrolled update of other Drupal modules.
"""
import argparse
from datetime import datetime, timezone
import gzip
import hashlib
import importlib.util
import json
import os
from pathlib import Path, PurePosixPath
import re
import shutil
import stat
import subprocess
import zipfile


def need(condition, message):
    if not condition: raise ValueError(message)


def digest(path):
    value = hashlib.sha256()
    with path.open('rb') as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b''): value.update(chunk)
    return value.hexdigest()


def run(args, data=None, env=None):
    process = subprocess.run(args, input=data, stdout=subprocess.PIPE, stderr=subprocess.PIPE, env=env, timeout=900)
    if process.returncode:
        # Expose error coordinates, never raw SQL, private row values or credentials.
        found = re.search(r'ERROR ([0-9]+).*?at line ([0-9]+)', process.stderr.decode('utf-8', errors='replace'))
        hint = ' SQL error ' + found.group(1) + ' at line ' + found.group(2) if found else ''
        try:
            failure = json.loads(process.stdout.decode())
            if failure.get('success') is False: hint = ' stage=' + failure['stage'] + ' type=' + failure['errorType']
        except (ValueError, KeyError, AttributeError): pass
        raise ValueError('Private operation failed.' + hint)
    return process.stdout


def php(candidate, operation, account, root, env=None):
    raw = run(['/usr/local/bin/php', '-d', 'zend.exception_ignore_args=1',
               str(candidate / 'tools/support-update-hosting.php'), operation, account, str(root)], env=env)
    result = json.loads(raw.decode())
    need(result.get('success') is not False and result.get('maintenanceEnabled') is True
         and result.get('environment') == 'preproduction', 'Closed Drupal verification failed.')
    return result


def rewrite_sql(text, names, prefix):
    """Rewrite table positions outside SQL string literals; never column names."""
    chunks = re.split(r"('(?:[^'\\]|\\.|'')*'|\"(?:[^\"\\]|\\.|\"\")*\")", text, flags=re.S)
    table_position = re.compile(r'((?:(?:CREATE|DROP|ALTER)\s+TABLE(?:\s+IF\s+(?:NOT\s+)?EXISTS)?|INSERT\s+INTO|REFERENCES)\s+)`([a-zA-Z0-9_]+)`', re.I)
    for index in range(0, len(chunks), 2):
        chunk = chunks[index]
        need(not re.search(r'\b(?:CREATE|DROP|ALTER)\s+(?:DATABASE|VIEW|TRIGGER|PROCEDURE|FUNCTION)\b|\bUSE\s+`', chunk, re.I), 'Unsupported database-wide dump object.')
        def replace(match):
            need(match.group(2) in names, 'Unexpected table in private SQL snapshot.')
            return match.group(1) + '`' + prefix + match.group(2) + '`'
        chunks[index] = table_position.sub(replace, chunk)
        def replace_locks(match):
            def table(item):
                need(item.group(1) in names, 'Unexpected locked table in private SQL snapshot.')
                return '`' + prefix + item.group(1) + '`'
            return match.group(1) + re.sub(r'`([a-zA-Z0-9_]+)`', table, match.group(2)) + match.group(3)
        chunks[index] = re.sub(r'(\bLOCK\s+TABLES\s+)([^;]+)(;)', replace_locks, chunks[index], flags=re.I)
    return ''.join(chunks)


def module(path, name):
    spec = importlib.util.spec_from_file_location(name, str(path))
    result = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(result)
    return result


def write_json(path, data):
    with path.open('x', encoding='utf-8') as stream: json.dump(data, stream, ensure_ascii=False, indent=2)
    os.chmod(str(path), 0o600)


def verify_candidate(candidate, source, package_digest):
    manifest = json.loads((candidate / 'manifest.json').read_text(encoding='utf-8'))
    need(manifest['kind'] == 'tcl-preproduction-support-editor-patch' and manifest['format'] == 1
         and manifest['targetHost'] == 'preprod.tclongages.fr' and manifest['productionAllowed'] is False
         and manifest['sourceSha'] == source, 'Wrong patch target or source.')
    for name, entry in manifest['files'].items():
        path = candidate / name
        need(path.is_file() and not path.is_symlink() and path.stat().st_size == entry['bytes']
             and digest(path) == entry['sha256'], 'Candidate file differs from pinned manifest.')
    stage = candidate / 'stage-receipt.json'
    if stage.exists():
        receipt = json.loads(stage.read_text(encoding='utf-8'))
        need(receipt['sourceSha'] == source and receipt['artifactSha256'] == package_digest, 'Stage belongs to another artifact.')
    return manifest


def mysql_config(runtime, output, account):
    secret = json.loads((runtime / 'database.json').read_text(encoding='utf-8'))
    need(secret['name'] == account + '_tclpreprod' and secret['user'] == account + '_tcl'
         and secret['host'] == 'localhost' and str(secret['port']) == '3306', 'Database is outside preproduction scope.')
    def quoted(value):
        return '"' + str(value).replace('\\', '\\\\').replace('"', '\\"').replace('\n', '\\n').replace('\r', '\\r') + '"'
    content = '[client]\n' + ''.join(key + '=' + quoted(secret[field]) + '\n' for key, field in
                                               [('user', 'user'), ('password', 'password'), ('host', 'host'), ('port', 'port'), ('database', 'name')])
    with output.open('x', encoding='utf-8') as stream: stream.write(content)
    os.chmod(str(output), 0o600)


def stage_patch(args, private, root, candidate):
    need(not candidate.exists(), 'Candidate staging already exists; inspect it instead of replacing it.')
    archive_path = Path(args.archive)
    need(archive_path.parent == private and archive_path.is_file() and not archive_path.is_symlink()
         and digest(archive_path) == args.sha256, 'Uploaded patch digest differs.')
    with zipfile.ZipFile(str(archive_path)) as archive:
        need(archive.testzip() is None, 'Patch archive CRC differs.')
        entries = archive.infolist()
        names = [entry.filename for entry in entries]
        need(len(names) == len(set(names)) and 'manifest.json' in names, 'Duplicate patch entries.')
        manifest = json.loads(archive.read('manifest.json').decode())
        need(set(names) == set(manifest['files']) | {'manifest.json'}, 'Unexpected package content.')
        for entry in entries:
            path = PurePosixPath(entry.filename)
            need(not path.is_absolute() and '\\' not in entry.filename and all(part not in ('', '.', '..') for part in path.parts)
                 and not entry.is_dir() and entry.file_size < 15 * 1024**2
                 and (entry.filename == 'manifest.json' or entry.filename.startswith(('site-pages/', 'web/modules/custom/tcl_site/', 'web/modules/custom/tcl_support/', 'tools/'))), 'Unsafe patch member.')
        candidate.mkdir(mode=0o700)
        for entry in entries:
            destination = candidate / entry.filename
            destination.parent.mkdir(mode=0o700, parents=True, exist_ok=True)
            with destination.open('xb') as stream: stream.write(archive.read(entry.filename))
            os.chmod(str(destination), 0o600)
    verify_candidate(candidate, args.source, args.sha256)
    before = php(candidate, 'check', args.account, root)
    loader = (root / 'web/sites/default/settings.php').read_text()
    matches = re.findall(r"require\s+'([^']+/runtime-settings\.php)'\s*;", loader)
    need(len(matches) == 1, 'Unrecognized private settings loader.')
    runtime = Path(matches[0]).parent
    backup_api = module(candidate / 'tools/backup_site.py', 'tcl_support_backup')
    total_bytes = sum(path.stat().st_size for path in backup_api.regular_tree(root))
    need(shutil.disk_usage(str(private)).free > total_bytes * 3 + 30 * 1024**2, 'Insufficient room for a verified recovery copy.')
    backup = backup_api.backup(args.account, root, private, runtime, Path('/home2') / args.account / 'public_html', Path('/usr/local/bin/php'))
    print(json.dumps({'step': 'fresh_backup_verified'}), flush=True)
    restore_api = module(candidate / 'tools/restore_backup_files.py', 'tcl_support_restore')
    restoration_parent = private / 'restorations'
    restoration_parent.mkdir(mode=0o700, exist_ok=True)
    need(not restoration_parent.is_symlink(), 'Redirected restoration parent.')
    restored = restore_api.restore_files(Path(backup['directory']), restoration_parent, backup['sqlSha256'], backup['filesSha256'])
    recovery = Path(restored['directory'])
    print(json.dumps({'step': 'restored_files_verified'}), flush=True)
    with gzip.open(str(Path(backup['directory']) / 'database.sql.gz'), 'rt', encoding='utf-8') as stream: sql = stream.read()
    original_tables = re.findall(r'^CREATE TABLE `([a-zA-Z0-9_]+)`', sql, re.M)
    need(original_tables and len(original_tables) == len(set(original_tables)), 'Unrecognized private SQL snapshot.')
    prefix = 'sr_' + args.sha256[:12] + '_'
    need(all(len(prefix + name) <= 64 for name in original_tables), 'Recovery table name exceeds server limits.')
    cnf = recovery / 'recovery-client.cnf'
    mysql_config(runtime, cnf, args.account)
    mysql_args = ['mysql', '--defaults-extra-file=' + str(cnf), '--batch', '--skip-column-names']
    present = run(mysql_args + ['--execute=SHOW TABLES']).decode().splitlines()
    need(not any(name.startswith(prefix) for name in present), 'Recovery namespace is already populated.')
    rewritten = rewrite_sql(sql, set(original_tables), prefix)
    run(mysql_args, rewritten.encode())
    after_tables = run(mysql_args + ['--execute=SHOW TABLES']).decode().splitlines()
    need(set(name for name in after_tables if name.startswith(prefix)) == set(prefix + name for name in original_tables), 'Recovery table inventory differs.')
    print(json.dumps({'step': 'restored_sql_inventory_verified', 'tables': len(original_tables)}), flush=True)
    restored_settings = recovery / 'drupal/web/sites/default/settings.php'
    with restored_settings.open('a') as stream:
        stream.write("\n// CLI-only isolated recovery namespace; original tables are not overwritten.\nif (PHP_SAPI === 'cli' && preg_match('/^sr_[a-f0-9]{12}_$/', getenv('TCL_SUPPORT_RESTORE_PREFIX') ?: '')) { $databases['default']['default']['prefix'] = getenv('TCL_SUPPORT_RESTORE_PREFIX'); }\n")
    env = os.environ.copy(); env['TCL_SUPPORT_RESTORE_PREFIX'] = prefix
    restored_drupal = php(candidate, 'restore-check', args.account, recovery / 'drupal', env=env)
    cnf.unlink()
    backup['restorationTested'] = True
    receipt = {'operation': 'staged_with_recovery', 'checkedAt': datetime.now(timezone.utc).isoformat(),
               'sourceSha': args.source, 'artifactSha256': args.sha256, 'target': 'https://preprod.tclongages.fr/',
               'before': before, 'backup': backup, 'restoredFiles': restored, 'restoredDrupal': restored_drupal,
               'databaseRestoration': {'namespace': prefix, 'tables': len(original_tables), 'originalTablesOverwritten': False},
               'deploymentExecuted': False, 'productionWritten': False}
    write_json(candidate / 'stage-receipt.json', receipt)
    return receipt


def apply_patch(args, private, root, candidate):
    manifest = verify_candidate(candidate, args.source, args.sha256)
    staged = json.loads((candidate / 'stage-receipt.json').read_text(encoding='utf-8'))
    need(staged['backup']['restorationTested'] is True and staged['restoredDrupal']['restorationPrefixVerified'] is True,
         'Recovery was not tested; refuse installation.')
    need(not (candidate / 'apply-receipt.json').exists(), 'Patch already applied; read its receipt.')
    php(candidate, 'check', args.account, root)
    loader = (root / 'web/sites/default/settings.php').read_text()
    runtime = Path(re.findall(r"require\s+'([^']+/runtime-settings\.php)'\s*;", loader)[0])
    marker = '// TCL_SUPPORT_EDITOR_PREPRODUCTION_V1'
    content = runtime.read_text()
    need(marker not in content, 'Private support settings already exist; reconcile before any target write.')
    for name, entry in sorted(manifest['files'].items()):
        if name.startswith('tools/'): continue
        destination = root / name
        need(destination.resolve() == destination and all(not part.is_symlink() for part in (destination,) + tuple(destination.parents)), 'Redirected deployment path.')
        destination.parent.mkdir(mode=0o755, parents=True, exist_ok=True)
        temporary = destination.with_name(destination.name + '.support-new')
        with temporary.open('xb') as stream: stream.write((candidate / name).read_bytes())
        os.chmod(str(temporary), 0o644)
        os.replace(str(temporary), str(destination))
        need(digest(destination) == entry['sha256'], 'Installed file digest differs.')
    # Web requests stay in capture. The single authorized CLI test can use PHP mail.
    content += "\n" + marker + "\n$tclSupportProbe = PHP_SAPI === 'cli' && getenv('TCL_SUPPORT_REAL_PROBE') === '1';\n$settings['tcl_support_enabled'] = TRUE;\n$settings['tcl_support_mail_mode'] = $tclSupportProbe ? 'transport' : 'capture';\n$settings['tcl_support_transport_qualified'] = $tclSupportProbe;\n$config['system.mail']['interface']['tcl_support_report'] = $tclSupportProbe ? 'php_mail' : 'test_mail_collector';\nunset($tclSupportProbe);\n"
    temporary = runtime.with_name('runtime-settings-support-new.php')
    with temporary.open('x') as stream: stream.write(content)
    os.chmod(str(temporary), 0o600)
    os.replace(str(temporary), str(runtime))
    initialized = php(candidate, 'initialize', args.account, root)
    checked = php(candidate, 'check', args.account, root)
    need(checked.get('editablePages') == 7 and checked['supportEnabled'] is True and checked['mailMode'] == 'capture', 'Installed feature check differs.')
    receipt = {'operation': 'installed_closed_preproduction', 'checkedAt': datetime.now(timezone.utc).isoformat(),
               'sourceSha': args.source, 'artifactSha256': args.sha256, 'target': 'https://preprod.tclongages.fr/',
               'candidateFilesVerified': len(manifest['files']), 'initialized': initialized, 'checked': checked,
               'backupReceipt': 'stage-receipt.json', 'maintenancePreserved': True, 'productionWritten': False}
    write_json(candidate / 'apply-receipt.json', receipt)
    return receipt


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('operation', choices=['stage', 'apply', 'mail-test'])
    parser.add_argument('--account', required=True)
    parser.add_argument('--source', required=True)
    parser.add_argument('--sha256', required=True)
    parser.add_argument('--archive')
    args = parser.parse_args()
    need(re.fullmatch(r'daje[0-9]{4}', args.account) and re.fullmatch(r'[a-f0-9]{40}', args.source)
         and re.fullmatch(r'[a-f0-9]{64}', args.sha256), 'Exact account and candidate required.')
    import pwd
    need(pwd.getpwuid(os.geteuid()).pw_name == args.account, 'Wrong hosted identity.')
    private = Path('/home2') / args.account / 'tcl-preproduction/private'
    root = Path('/home2') / args.account / 'tcl-preproduction/drupal'
    need(private.resolve() == private and root.resolve() == root and stat.S_IMODE(private.stat().st_mode) == 0o700, 'Invalid private preproduction target.')
    os.umask(0o077)
    candidate = private / ('support-candidate-' + args.source[:12] + '-' + args.sha256[:12])
    if args.operation == 'stage': result = stage_patch(args, private, root, candidate)
    elif args.operation == 'apply': result = apply_patch(args, private, root, candidate)
    else:
        verify_candidate(candidate, args.source, args.sha256)
        need((candidate / 'apply-receipt.json').exists(), 'Installed patch receipt required.')
        need(not (candidate / 'mail-test-attempt.json').exists(), 'A real-mail attempt already exists; inspect it instead of resending.')
        write_json(candidate / 'mail-test-attempt.json', {'recordedAt': datetime.now(timezone.utc).isoformat(), 'recipient': 'support@tclongages.fr'})
        result = php(candidate, 'mail-test', args.account, root)
        write_json(candidate / 'mail-test-receipt.json', result)
    print(json.dumps({'operation': result['operation'], 'sourceSha': args.source, 'artifactSha256': args.sha256,
                      'receiptDirectory': str(candidate), 'productionWritten': False}))


if __name__ == '__main__':
    main()
