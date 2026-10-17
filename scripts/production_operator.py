"""Private transaction controller; not an installed or qualified o2switch adapter.

Only the future reviewed hosting adapter performs Drupal/files/SQL operations.
The repository configuration is disabled and contains no deployable release.
"""
import contextlib
from datetime import datetime, timezone
import hashlib
import json
import os
from pathlib import Path
import re
import sys
import time

DIRECTORY = Path('/home2/daje5127/tcl-production/private/github-deployment')
TARGET = 'https://tclongages.fr'
TERMINAL = ('completed', 'refused_before_mutation')


def need(condition):
    if not condition:
        raise ValueError('Private deployment gate refused.')


def hashed(value):
    need(isinstance(value, str) and re.fullmatch(r'[a-f0-9]{64}', value))
    return value


def persist(path, record):
    temporary = path.with_suffix('.tmp')
    with temporary.open('x', encoding='utf-8') as stream:
        os.chmod(str(temporary), 0o600)
        json.dump(record, stream, sort_keys=True)
        stream.flush()
        os.fsync(stream.fileno())
    os.replace(str(temporary), str(path))
    if os.name == 'posix':
        descriptor = os.open(str(path.parent), os.O_RDONLY)
        try:
            os.fsync(descriptor)
        finally:
            os.close(descriptor)


def checked_health(backend, archive, maintenance):
    health = backend.health(archive, maintenance)
    need(health.get('archiveSha256') == archive
         and health.get('maintenanceActive') is maintenance
         and all(health.get(key) is True for key in
                 ('httpChecked', 'drupalChecked', 'businessDataPreserved',
                  'settingsPreserved', 'preproductionPreserved')))


def transaction(request, backend, journal, now=time.time):
    """Execute under a server lock. Adapter receipts never become human approvals."""
    need(not journal.exists() and not journal.with_suffix('.tmp').exists())
    for previous in journal.parent.glob('*.json'):
        need(json.loads(previous.read_text(encoding='utf-8')).get('status') in TERMINAL)
    record = {'format': 'tcl-production-result-v1', 'runId': request['runId'],
              'target': TARGET, 'operation': request['operation'], 'releaseId': request['releaseId'],
              'status': 'started', 'databaseRestored': False, 'preproductionChanged': False,
              'backupVerified': False, 'restoreTested': False, 'healthChecked': False,
              'events': []}

    def checkpoint(stage):
        record['events'].append({'stage': stage, 'at': datetime.now(timezone.utc).isoformat()})
        persist(journal, record)

    started, closed, touched_code, backup = now(), False, False, None
    checkpoint('preflight')
    try:
        release = request['release']
        before = backend.preflight(request)
        need(before.get('activeArchiveSha256') == release['expectedActiveSha256']
             and type(before.get('maintenanceActive')) is bool
             and all(before.get(key) is True for key in
                     ('candidateVerified', 'schemaCompatible', 'rollbackSafe', 'diskSpaceChecked',
                      'privateBackupLocationChecked', 'isolatedRestoreTargetChecked', 'writersQuiesceSupported')))
        record['previousArchiveSha256'] = before['activeArchiveSha256']
        record['previousMaintenanceActive'] = before['maintenanceActive']
        checkpoint('enter_maintenance')
        closed = True  # Record uncertainty even if this operation fails halfway.
        backend.enter_maintenance()
        checkpoint('backup')
        backup = backend.backup(request['runId'])
        need(isinstance(backup.get('id'), str) and re.fullmatch(r'[a-z0-9][a-z0-9-]{0,63}', backup['id']))
        for key in ('sqlSha256', 'filesSha256'):
            hashed(backup.get(key))
        need(type(backup.get('createdAt')) in (int, float) and started <= backup['createdAt'] <= now()
             and backup.get('integrityVerified') is True and backup.get('complete') is True)
        record['backupId'] = backup['id']
        record['backupVerified'] = True
        checkpoint('restore_in_isolation')
        restored = backend.restore_verify(backup)
        need(all(restored.get(key) == backup[key] for key in ('id', 'sqlSha256', 'filesSha256'))
             and all(restored.get(key) is True for key in
                     ('filesRestored', 'sqlRestoredInIsolation', 'drupalBooted',
                      'activeSitesUntouched', 'outboundMessagesDisabled'))
             and now() - backup['createdAt'] <= 1200)
        record['restoreTested'] = True
        checkpoint('switch_code')
        touched_code = True
        backend.switch_code(request, backup)
        checkpoint('check_closed')
        checked_health(backend, release['archiveSha256'], True)
        checkpoint('restore_previous_maintenance')
        # A site closed by its administrator is never opened by this workflow.
        backend.finish_maintenance(before['maintenanceActive'])
        checked_health(backend, release['archiveSha256'], before['maintenanceActive'])
        record['healthChecked'] = True
        record['status'] = 'completed'
        checkpoint('completed')
        return record
    except Exception:
        record['status'] = 'needs_private_inspection' if closed else 'refused_before_mutation'
        checkpoint('failed')
        if closed:
            try:
                backend.enter_maintenance()
                if touched_code and record['restoreTested']:
                    checkpoint('automatic_code_recovery')
                    backend.restore_code(backup)
                    checked_health(backend, record['previousArchiveSha256'], True)
                    record['status'] = 'code_recovered_maintenance_retained'
                checkpoint('maintenance_retained')
            except Exception:
                record['status'] = 'recovery_unconfirmed'
                checkpoint('manual_recovery_required')
        # Any failure remains a failed workflow, including a successful recovery.
        raise RuntimeError('Private inspection required.') from None


def private_file(path):
    need(all(not p.is_symlink() for p in (path,) + tuple(path.parents)))
    need(path.is_file() and path.stat().st_uid == os.geteuid()
         and path.stat().st_mode & 0o077 == 0)
    return path.read_bytes()


def validate_request(request, profile, catalog):
    need(profile.get('enabled') is True and catalog.get('enabled') is True)
    need(request.get('format') == 'tcl-production-request-v1' and request.get('ready') is True
         and request.get('target') == TARGET and request.get('operation') in ('deploy', 'rollback'))
    need(request.get('workflowSha') == profile.get('approvedWorkflowSha')
         and re.fullmatch(r'[a-f0-9]{40}', request.get('workflowSha', '')))
    need(re.fullmatch(r'[1-9][0-9]{0,19}', request.get('runId', '')))
    catalog_hash = hashlib.sha256(json.dumps(catalog, sort_keys=True, separators=(',', ':')).encode()).hexdigest()
    need(request.get('catalogSha256') == catalog_hash
         and catalog.get('target') == TARGET and catalog.get('repository') == 'jpdandin44/TC_Longages')
    release_id = request.get('releaseId', '')
    need(re.fullmatch(r'[a-z0-9][a-z0-9-]{0,63}', release_id))
    release = catalog.get('releases', {}).get(release_id)
    need(isinstance(release, dict) and request.get('release') == release
         and release.get('qualified') is True and release.get('schemaChanges') is False
         and release.get('codeRollbackCompatible') is True)
    for key in ('archiveSha256', 'expectedActiveSha256', 'qualificationSha256'):
        hashed(release.get(key))
    for key in ('operatorSha256', 'adapterSha256'):
        need(request.get(key) == hashed(catalog.get(key)))
    need(request.get('authorizationRef') == catalog.get('qualificationRef'))
    if request['operation'] == 'rollback':
        need(re.fullmatch(r'[a-z0-9][a-z0-9-]{0,63}', request.get('backupId', '')))
    else:
        need(request.get('backupId') == '')


def main():
    import fcntl
    import pwd
    need(os.name == 'posix' and pwd.getpwuid(os.geteuid()).pw_name == 'daje5127')
    need(Path(__file__) == DIRECTORY / 'production_operator.py'
         and DIRECTORY.is_dir() and DIRECTORY.resolve() == DIRECTORY
         and DIRECTORY.stat().st_mode & 0o077 == 0)
    request_bytes = sys.stdin.buffer.read(65537)
    need(len(request_bytes) <= 65536)
    request = json.loads(request_bytes)
    profile = json.loads(private_file(DIRECTORY / 'profile.json'))
    catalog = json.loads(private_file(DIRECTORY / 'catalog.json'))
    validate_request(request, profile, catalog)
    need(hashlib.sha256(private_file(Path(__file__))).hexdigest() == request['operatorSha256'])
    adapter = private_file(DIRECTORY / 'hosting_adapter.py')
    need(hashlib.sha256(adapter).hexdigest() == request['adapterSha256'])
    os.umask(0o077)
    for name in ('execution.lock', 'journals'):
        path = DIRECTORY / name
        need(not path.is_symlink())
        if path.exists():
            need(path.stat().st_uid == os.geteuid() and path.stat().st_mode & 0o077 == 0)
    with (DIRECTORY / 'execution.lock').open('a') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        journals = DIRECTORY / 'journals'
        journals.mkdir(mode=0o700, exist_ok=True)
        need(journals.is_dir() and all(p.is_file() and not p.is_symlink() for p in journals.iterdir()))
        need(not list(journals.glob('*.tmp')))
        # Adapter diagnostics stay private even if a library writes to stdout.
        with (journals / (request['runId'] + '.log')).open('x', encoding='utf-8') as log:
            with contextlib.redirect_stdout(log), contextlib.redirect_stderr(log):
                namespace = {'__file__': str(DIRECTORY / 'hosting_adapter.py'), '__name__': 'tcl_hosting_adapter'}
                exec(compile(adapter, namespace['__file__'], 'exec'), namespace)
                result = transaction(request, namespace['Backend'](DIRECTORY), journals / (request['runId'] + '.json'))
        public_keys = ('format', 'target', 'operation', 'runId', 'releaseId', 'status',
                       'databaseRestored', 'preproductionChanged', 'backupVerified', 'restoreTested', 'healthChecked')
        print(json.dumps({key: result[key] for key in public_keys}))


if __name__ == '__main__':
    try:
        main()
    except Exception:
        print('Private operator refused or needs inspection.', file=sys.stderr)
        sys.exit(1)
