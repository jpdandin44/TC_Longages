"""Restore the reviewed backup into the newly approved, empty production DB.

No reinstall, existing DB overwrite, document-root switch or public opening.
Passwords remain in private files; a partial import requires manual review.
Python 3.6 compatible. Preparation and SQL rights need their own human records.
"""
import argparse
from datetime import datetime, timezone
import gzip
import json
import os
from pathlib import Path
import re
import subprocess
import tempfile

from delivery_shared import digest, require
from stage_production import qualify_profile, no_links, private_write

SQL_SHA = '708530a6886ea09b6233dfd6f2783b292546816de3ee87c982c9e047ba03e070'
FILES_SHA = '3605ca1fb0148feea7067083a8aa5388d0864b20fe943f0f1f30b69dbf3d5734'


def sql_payload(path):
    no_links(path)
    require(path.is_file() and digest(path) == SQL_SHA, 'Reviewed SQL backup required.')
    with gzip.open(str(path), 'rb') as stream:
        content = stream.read(64 * 1024**2 + 1)
    require(0 < len(content) <= 64 * 1024**2, 'SQL restore size exceeded.')
    # Dedicated DB rights are also checked on the actual server before import.
    require(not re.search(rb'^\s*(?:/\*![0-9]+\s*)?(?:USE\s|(?:CREATE|DROP|ALTER)\s+DATABASE\s|GRANT\s|REVOKE\s|(?:CREATE|DROP)\s+USER\s|SET\s+GLOBAL\s|LOAD\s+DATA\s|SOURCE\s|DELIMITER\s)', content, re.I | re.M),
            'SQL crosses the reviewed database scope.')
    tables = re.findall(rb'^CREATE TABLE `([a-zA-Z0-9_]+)`', content, re.M)
    require(tables and len(tables) == len(set(tables)), 'Invalid backup table inventory.')
    return content, len(tables)


def option_value(value):
    require(isinstance(value, str) and value and '\0' not in value, 'Private SQL password required.')
    return '"' + value.replace('\\', '\\\\').replace('"', '\\"').replace('\n', '\\n').replace('\r', '\\r').replace('\t', '\\t') + '"'


def php_preflight(private, profile):
    code = r'''<?php
ini_set('zend.exception_ignore_args','1'); ini_set('display_errors','0');
try {
 $p=json_decode(file_get_contents(PROFILE),true,512,JSON_THROW_ON_ERROR);
 $i=json_decode(file_get_contents(INPUT),true,512,JSON_THROW_ON_ERROR);
 $pdo=new PDO('mysql:host=localhost;port=3306;dbname='.$p['database'].';charset=utf8mb4',
   $p['databaseUser'],$i['databasePassword'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $s=$pdo->query('SELECT DATABASE() AS db,CURRENT_USER() AS account,@@character_set_database AS charset,@@collation_database AS collation,@@default_storage_engine AS engine')->fetch(PDO::FETCH_ASSOC);
 if ($s['db']!==$p['database'] || explode('@',$s['account'])[0]!==$p['databaseUser'] || $s['charset']!=='utf8mb4' || $s['collation']!=='utf8mb4_unicode_ci' || strcasecmp($s['engine'],'InnoDB')!==0) throw new RuntimeException('Scope');
 $grants=$pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN);
 $expected=['ALTER','CREATE','CREATE TEMPORARY TABLES','DELETE','DROP','INDEX','INSERT','LOCK TABLES','SELECT','UPDATE']; sort($expected); $matched=false;
 foreach($grants as $g) {
  if (str_starts_with($g,'GRANT USAGE ON *.* TO ')) continue;
  if (!preg_match('/^GRANT (.*?) ON `([^`]+)`\.\* TO /',$g,$m)
      || str_replace('\\_','_',$m[2])!==$p['database']) throw new RuntimeException('Extra grant');
  $rights=array_map('trim',explode(',',$m[1])); sort($rights);
  if($rights!==$expected) throw new RuntimeException('Rights'); $matched=true;
 }
 if(!$matched) throw new RuntimeException('Rights absent');
 $q=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=?');
 $q->execute([$p['database']]); if((int)$q->fetchColumn()!==0) throw new RuntimeException('Not empty');
 echo json_encode(['emptyDatabaseVerified'=>true,'dedicatedRightsVerified'=>true]);
} catch(Throwable $e) { echo json_encode(['success'=>false,'errorType'=>get_class($e),'errorCode'=>$e->getCode()]); exit(1); }
'''
    code = code.replace('PROFILE', json.dumps(str(private / 'profile.json'))).replace('INPUT', json.dumps(str(private / 'hosting-input.json')))
    result = subprocess.run(['/usr/local/bin/php'], input=code.encode('utf-8'),
                            stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=30)
    require(result.returncode == 0, 'Production SQL preflight failed; no import performed.')
    record = json.loads(result.stdout.decode('utf-8'))
    require(record.get('emptyDatabaseVerified') is True and record.get('dedicatedRightsVerified') is True,
            'Empty dedicated SQL scope not proved.')
    return record


RUNTIME = r'''<?php
declare(strict_types=1);
$p=json_decode(file_get_contents(__DIR__.'/profile.json'),true,512,JSON_THROW_ON_ERROR);
$d=json_decode(file_get_contents(__DIR__.'/database.json'),true,512,JSON_THROW_ON_ERROR);
foreach(['NAME'=>'name','USER'=>'user','PASSWORD'=>'password','HOST'=>'host','PORT'=>'port'] as $key=>$field) {
 putenv('TCL_DB_'.$key.'='.$d[$field]);
}
putenv('TCL_HOSTING_ENABLE=1'); putenv('TCL_ENVIRONMENT=production'); putenv('TCL_PUBLIC_INDEXING=0');
putenv('TCL_HASH_SALT='.$d['hashSalt']);
putenv('TCL_PRIVATE_FILES='.__DIR__.'/files'); putenv('TCL_TEMP_FILES='.__DIR__.'/temp');
putenv('TCL_CONFIG_SYNC='.__DIR__.'/config-sync');
require $p['composerRoot'].'/config/settings.hosting.example.php';
ini_set('session.cookie_secure','1');
unset($p,$d,$key,$field);
'''

BOOTSTRAP_CHECK = r'''\Drupal::state()->set('system.maintenance_mode', TRUE);
$r=['databaseConnectionVerified'=>true,'coreVersion'=>\Drupal::VERSION,'environment'=>getenv('TCL_ENVIRONMENT'),
'defaultLanguage'=>\Drupal::languageManager()->getDefaultLanguage()->getId(),
'maintenanceActive'=>(bool)\Drupal::state()->get('system.maintenance_mode'),
'mailBackend'=>\Drupal::config('system.mail')->get('interface.default'),
'registration'=>\Drupal::config('user.settings')->get('register'),
'cronInterval'=>\Drupal::config('automated_cron.settings')->get('interval'),
'publicIndexing'=>getenv('TCL_PUBLIC_INDEXING'),
'tclSiteInstalled'=>\Drupal::moduleHandler()->moduleExists('tcl_site'),
'tableCount'=>(int)\Drupal::database()->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetchField()];
echo 'TCL_RESULT:'.json_encode($r).'\n';'''


def bootstrap(root, table_count):
    result = subprocess.run(['/usr/local/bin/php', str(root / 'vendor/drush/drush/drush.php'),
        '--root=' + str(root / 'web'), '--uri=https://tclongages.fr', 'php:eval', BOOTSTRAP_CHECK],
        stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=90)
    require(result.returncode == 0, 'Closed Drupal bootstrap failed.')
    output = result.stdout.decode('utf-8')
    marker = 'TCL_RESULT:'
    require(marker in output, 'Drupal verification receipt absent.')
    record = json.JSONDecoder().raw_decode(output.split(marker, 1)[1])[0]
    require(record.get('databaseConnectionVerified') is True and record.get('coreVersion') == '11.4.8'
        and record.get('environment') == 'production' and record.get('defaultLanguage') == 'fr'
        and record.get('maintenanceActive') is True and record.get('mailBackend') == 'tcl_null_mail'
        and record.get('registration') == 'admin_only' and record.get('cronInterval') == 0
        and record.get('publicIndexing') == '0' and record.get('tclSiteInstalled') is True
        and record.get('tableCount') == table_count,
        'Closed French production settings not verified.')
    return record


def copy_verified_data(source, target, inventory, prefix):
    if not source.exists():
        return
    no_links(source)
    for item in sorted(source.rglob('*')):
        no_links(item)
        relative = item.relative_to(source)
        destination = target / relative
        if item.is_dir():
            destination.mkdir(mode=0o700, parents=True, exist_ok=True)
            continue
        name = prefix + '/' + relative.as_posix()
        require(item.is_file() and name in inventory and digest(item) == inventory[name]['sha256'],
                'Restored runtime data differs from backup.')
        destination.parent.mkdir(mode=0o700, parents=True, exist_ok=True)
        private_write(destination, item.read_bytes())


def restore(private, restored, rights_authorization):
    profile = json.loads((private / 'profile.json').read_text(encoding='utf-8'))
    home, release = qualify_profile(profile)
    require(os.name == 'posix', 'Hosted Linux required.')
    import pwd
    require(pwd.getpwuid(os.geteuid()).pw_name == profile['account'], 'Wrong hosted identity.')
    require(rights_authorization.strip() and private == home / 'tcl-production/private', 'Reviewed SQL rights and target required.')
    require(restored.parent == home / 'tcl-preproduction/private/restorations'
            and re.fullmatch(r'restore-[a-z0-9_]+', restored.name), 'Private restored copy required.')
    for path in (private, release, restored):
        no_links(path)
        require(path.is_dir() and path.resolve() == path, 'Private directory missing or redirected.')
    file_receipt = json.loads((restored / 'restore-receipt.json').read_text(encoding='utf-8'))
    require(file_receipt.get('filesRestoredAndVerified') is True
            and file_receipt.get('sqlSha256') == SQL_SHA and file_receipt.get('filesSha256') == FILES_SHA,
            'Verified backup file restoration required.')
    backup = home / 'tcl-preproduction/private/backups/20261005T075955Z'
    no_links(backup)
    receipt = json.loads((backup / 'receipt.json').read_text(encoding='utf-8'))
    require(digest(backup / 'files.tar.gz') == FILES_SHA == receipt.get('filesSha256'), 'File backup changed.')
    payload, table_count = sql_payload(backup / 'database.sql.gz')
    root = Path(profile['composerRoot'])
    require(not os.path.lexists(str(private / 'production-restore-attempt.json'))
            and not os.path.lexists(str(root / 'web/sites/default/settings.php')), 'Existing attempt requires review.')
    no_links(private / 'hosting-input.json')
    password = json.loads((private / 'hosting-input.json').read_text(encoding='utf-8')).get('databasePassword')
    escaped = option_value(password)
    preflight = php_preflight(private, profile)
    previous_mask = os.umask(0o077)
    try:
        attempt = {'startedAt': datetime.now(timezone.utc).isoformat(), 'database': profile['database'],
            'rightsAuthorizationRef': rights_authorization, 'sqlSha256': SQL_SHA, 'success': False}
        private_write(private / 'production-restore-attempt.json', json.dumps(attempt).encode('utf-8'))
        database = {'name': profile['database'], 'user': profile['databaseUser'], 'password': password,
                    'host': 'localhost', 'port': '3306', 'hashSalt': os.urandom(32).hex()}
        private_write(private / 'database.json', json.dumps(database).encode('utf-8'))
        private_write(private / 'runtime-settings.php', RUNTIME.encode('utf-8'))
        loader = ("<?php\nrequire '" + str(private / 'runtime-settings.php') + "';\n").encode('utf-8')
        private_write(root / 'web/sites/default/settings.php', loader)
        # Adapt only the newly restored copy's private loader; the source is untouched.
        restored_settings = restored / 'drupal/web/sites/default/settings.php'
        no_links(restored_settings)
        require(restored_settings.is_file(), 'Restored settings loader absent.')
        restored_settings.write_bytes(loader)
        copy_verified_data(restored / 'drupal/web/sites/default/files', root / 'web/sites/default/files',
                           receipt['inventory'], 'drupal/web/sites/default/files')
        for name in ('files', 'config-sync'):
            copy_verified_data(restored / name, private / name, receipt['inventory'], name)
        client = None
        try:
            with tempfile.NamedTemporaryFile(mode='w', prefix='mysql-', suffix='.cnf',
                                              dir=str(private), delete=False) as stream:
                client = Path(stream.name)
                stream.write('[client]\nuser=' + profile['databaseUser'] + '\npassword=' + escaped
                             + '\nhost=localhost\nport=3306\ndefault-character-set=utf8mb4\n')
            result = subprocess.run(['/bin/mysql', '--defaults-file=' + str(client), '--binary-mode=1',
                                     '--local-infile=0', '--database=' + profile['database']], input=payload,
                                    stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=120)
            require(result.returncode == 0, 'SQL import failed; partial target must be reviewed, never purged automatically.')
        finally:
            if client is not None:
                client.unlink()  # Only this newly owned ephemeral credential file.
        restored_check = bootstrap(restored / 'drupal', table_count)
        production_check = bootstrap(root, table_count)
        record = {'format': 'tcl-production-restore-v1', 'checkedAt': datetime.now(timezone.utc).isoformat(),
            'database': profile['database'], 'sqlSha256': SQL_SHA, 'filesSha256': FILES_SHA,
            'backupTableCount': table_count, 'preflight': preflight, 'restoredDrupal': restored_check,
            'productionDrupal': production_check, 'sourceArchiveSha256': profile['archiveSha256'],
            'databaseRestored': True, 'restorationTested': True, 'configurationActivated': True,
            'officialRootChanged': False, 'publicOpeningExecuted': False}
        private_write(private / 'production-restore-receipt.json', json.dumps(record, indent=2).encode('utf-8'))
        return record
    finally:
        os.umask(previous_mask)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--restored-copy', required=True, type=Path)
    parser.add_argument('--rights-authorization-ref', required=True)
    args = parser.parse_args()
    print(json.dumps(restore(Path('/home2/daje5127/tcl-production/private'), args.restored_copy,
                             args.rights_authorization_ref)))


if __name__ == '__main__':
    main()
