"""Private TC preparation: exact PR19 patch, fresh backup and isolated rehearsal.

Python 3.6 compatible. No active installation in stage; application code is the
already merged candidate. All commands capture secrets/SQL instead of printing.
"""
import argparse, gzip, hashlib, importlib.util, json, os, pwd, re, shutil, stat, subprocess, zipfile
from datetime import datetime, timezone
from pathlib import Path, PurePosixPath

ROOT = Path('/home2/daje5127/tcl-preproduction/drupal')
PRIVATE = Path('/home2/daje5127/tcl-preproduction/private')
PHP = Path('/usr/local/bin/php')
SOURCE = '5fcd2a63e42573b2e8e31c4e1086dff4ba63557d'
ARTIFACT = '702d4e5b697dcae2b388324e0011ad06ef0a30858d8732fea667d332cd9d7431'
STAGE = PRIVATE / 'bureau-702d4e5b697d'
PREFIX = 'sr_702d4e5b697d_'

def need(ok, message):
    if not ok: raise ValueError(message)

def digest(path):
    h = hashlib.sha256()
    with path.open('rb') as stream:
        for block in iter(lambda: stream.read(1024 * 1024), b''): h.update(block)
    return h.hexdigest()

def load(path, name):
    spec = importlib.util.spec_from_file_location(name, str(path))
    result = importlib.util.module_from_spec(spec); spec.loader.exec_module(result); return result

def run(arguments, data=None, cwd=None, env=None, log=None):
    process = subprocess.run(arguments, input=data, cwd=cwd, env=env, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=900)
    if log:
        log.write_bytes(process.stdout + process.stderr); os.chmod(str(log), 0o600)
    if process.returncode:
        raise ValueError('Private command failed: ' + Path(arguments[0]).name)
    return process.stdout

def write(path, data, mode=0o600):
    need(not path.is_symlink() and all(not parent.is_symlink() for parent in path.parents), 'Redirected path')
    path.parent.mkdir(mode=0o700, parents=True, exist_ok=True)
    temp = path.with_name(path.name + '.bureau-new')
    parent_mode = stat.S_IMODE(path.parent.stat().st_mode)
    protected_settings = path.parent == ROOT/'web/sites/default' and not parent_mode & 0o200
    if protected_settings:
        need(path.parent.stat().st_uid == os.geteuid() and not parent_mode & 0o022, 'Settings owner or protection')
        os.chmod(str(path.parent), parent_mode | 0o200)
    try:
        with temp.open('xb') as stream: stream.write(data)
        os.chmod(str(temp), mode); os.replace(str(temp), str(path))
    finally:
        if protected_settings: os.chmod(str(path.parent), parent_mode)

def json_write(path, data):
    need(not path.exists(), 'Receipt already exists')
    write(path, (json.dumps(data, ensure_ascii=False, indent=2) + '\n').encode())

def runtime(root):
    names = re.findall(r"require\s+'([^']+/runtime-settings\.php)'\s*;", (root / 'web/sites/default/settings.php').read_text())
    need(len(names) == 1, 'Single installed runtime required')
    path = Path(names[0]); need(path.resolve() == path and not path.is_symlink(), 'Runtime path')
    if root == ROOT: need(path.parent.parent == PRIVATE, 'Active runtime scope')
    else: need(str(path).startswith(str(root.parent) + '/runtime/'), 'Restored runtime scope')
    return path

def php(operation, root, env=None):
    raw = run([str(PHP), '-d', 'zend.exception_ignore_args=1', '-d', 'opcache.enable_cli=0', str(PRIVATE / 'bureau-preproduction.php'), operation, str(root)], env=env)
    result = json.loads(raw.decode()); need(result.get('success') is True, 'PHP operator rejected: ' + json.dumps({k:result.get(k) for k in ['phase','errorType','errorFile','errorLine']})); return result

def apache_plan(original):
    need(original.count('Require valid-user') == 1 and 'AuthType Basic' in original and 'Site de test TC Longages' in original, 'Existing Basic protection')
    bounded = '<RequireAny>\n  Require valid-user\n  <RequireAll>\n    Require ip 109.234.167.71 127.0.0.1 ::1\n    Require expr "%{THE_REQUEST} =~ m#^(POST /oauth/token|GET /oauth/userinfo) HTTP/[0-9.]+$#"\n  </RequireAll>\n</RequireAny>'
    return original.replace('Require valid-user', bounded).encode()

def preflight(archive):
    need(pwd.getpwuid(os.geteuid()).pw_name == 'daje5127', 'Hosting owner')
    need(ROOT.resolve() == ROOT and PRIVATE.resolve() == PRIVATE and stat.S_IMODE(PRIVATE.stat().st_mode) == 0o700, 'Hosting scope')
    need(archive.parent == PRIVATE and not archive.is_symlink() and digest(archive) == ARTIFACT, 'Application artifact')
    receipt = json.loads((STAGE/'stage-receipt.json').read_text())
    need(receipt['artifactSha256'] == ARTIFACT and receipt['sourceSha'] == SOURCE and receipt['databaseRestoration']['allActiveRowsCompared'] and receipt['databaseRestoration']['allActiveSchemasCompared'] and receipt['rehearsal']['adminRoleAndRegistrationVerified'], 'Qualified rehearsal')
    rt = runtime(ROOT)
    need(digest(rt) == receipt['runtimeHashBefore'] and digest(ROOT/'web/.htaccess') == receipt['htaccessHashBefore'], 'Runtime or Basic protection drift')
    need(all(digest(ROOT/name) == value if value is not None else not (ROOT/name).exists() for name,value in receipt['originalFiles'].items()), 'Application code drift')
    current = php('inspect', ROOT)
    need(not current['adminAlreadyExists'] and current['editorialDigest'] == receipt['before']['editorialDigest'] and current['users'] == receipt['before']['users'] and current['maintenance'] == receipt['before']['maintenance'], 'Active Drupal drift')
    manifest = json.loads((STAGE/'manifest.json').read_text())
    need(all(digest(STAGE/name) == entry['sha256'] for name,entry in manifest['files'].items()), 'Staged source drift')
    recovery = Path(receipt['recoveryRoot'])
    need(recovery.resolve() == recovery and str(recovery).startswith(str(PRIVATE/'restorations')+'/restore-'), 'Recovery scope')
    old_lock = json.loads((ROOT/'composer.lock').read_text()); new_lock = json.loads((STAGE/'composer.lock').read_text())
    old_packages = {p['name']:p for p in old_lock['packages']}; new_packages = {p['name']:p for p in new_lock['packages']}
    need(all(name in new_packages and old['version'] == new_packages[name]['version'] and old.get('dist') == new_packages[name].get('dist') for name,old in old_packages.items()), 'Existing dependency changed')
    need(len(set(new_packages)-set(old_packages)) == 13 and new_packages['drupal/core']['version'] == '11.4.8' and new_packages['drupal/simple_oauth']['version'] == '6.1.1', 'Dependency pins')
    for name in ['consumers','simple_oauth']:
        need(not (ROOT/'web/modules/contrib'/name).exists() and (recovery/'web/modules/contrib'/name).is_dir(), 'Contrib promotion scope')
    need(not (PRIVATE/'tc-identity').exists() and not (STAGE/'apply-receipt.json').exists() and not (STAGE/'apply-started.json').exists(), 'Existing provisioning or installation')
    write(STAGE/'htaccess-after', apache_plan((ROOT/'web/.htaccess').read_text()))
    return receipt, manifest, recovery

def apply(archive, approved):
    need(approved, 'Action-time security approval required')
    receipt, manifest, recovery = preflight(archive)
    safety = STAGE/'before-active'; safety.mkdir(mode=0o700)
    for name in ['web/sites/default/settings.php','web/.htaccess','web/modules/custom/tcl_site/src/Controller/ClubPageController.php']:
        write(safety/name, (ROOT/name).read_bytes())
    json_write(STAGE/'apply-started.json', {'observedAt':datetime.now(timezone.utc).isoformat(),'sourceSha':SOURCE,'artifactSha256':ARTIFACT,'scope':'preproduction only','userPasswordEntry':'hand-off'})
    vendor_new = ROOT/'vendor.bureau-new'
    need(not vendor_new.exists() and not (STAGE/'vendor-before').exists(), 'Vendor promotion collision')
    for item in (recovery/'vendor/composer').glob('autoload*.php'):
        need(str(recovery).encode() not in item.read_bytes(), 'Nonportable autoloader')
    shutil.copytree(str(recovery/'vendor'), str(vendor_new))
    drush = [str(PHP), str(ROOT/'vendor/bin/drush'), '--root='+str(ROOT/'web'), '--uri=https://preprod.tclongages.fr']
    run(drush+['state:set','system.maintenance_mode','1','--input-format=integer'])
    try:
        os.rename(str(ROOT/'vendor'), str(STAGE/'vendor-before'))
        os.rename(str(vendor_new), str(ROOT/'vendor'))
        for name in ['consumers','simple_oauth']:
            shutil.copytree(str(recovery/'web/modules/contrib'/name), str(ROOT/'web/modules/contrib'/name))
        install_files(ROOT, manifest)
        php('activate', ROOT)
        run(drush+['updatedb','--yes'], log=STAGE/'active-updates.log')
        provisioned = php('provision', ROOT)
        finish(receipt, provisioned)
    except Exception:
        # Close the new integration and restore the V1 entry points. Preserve all
        # database rows, new modules and dependencies; no full SQL overwrite.
        for name in ['web/sites/default/settings.php','web/.htaccess','web/modules/custom/tcl_site/src/Controller/ClubPageController.php']:
            write(ROOT/name, (safety/name).read_bytes(), 0o600 if name.endswith('settings.php') else 0o644)
        try:
            run(drush+['cache:rebuild'])
            run(drush+['state:set','system.maintenance_mode','1' if receipt['before']['maintenance'] else '0','--input-format=integer'])
        except Exception: pass
        raise

def finish(receipt, provisioned):
    settings = ROOT/'web/sites/default/settings.php'
    line = b"\nrequire '/home2/daje5127/tcl-preproduction/private/tc-identity/settings.php';\n"
    base = (STAGE/'before-active/web/sites/default/settings.php').read_bytes()
    need(settings.read_bytes() in [base, base+line], 'Settings changed during interrupted installation')
    write(settings, base+line, 0o600)
    write(ROOT/'web/.htaccess', (STAGE/'htaccess-after').read_bytes(), 0o644)
    drush = [str(PHP), str(ROOT/'vendor/bin/drush'), '--root='+str(ROOT/'web'), '--uri=https://preprod.tclongages.fr']
    run(drush+['cache:rebuild'])
    run(drush+['state:set','system.maintenance_mode','1' if receipt['before']['maintenance'] else '0','--input-format=integer'])
    verified = php('verify', ROOT)
    need(verified['editorialDigest'] == receipt['before']['editorialDigest'] and not verified['adminHasPassword'] and verified['maintenance'] == receipt['before']['maintenance'], 'Preserved content or password ownership')
    probes = {}
    for path,method in [('/', 'GET'),('/oauth/token','POST'),('/oauth/userinfo','GET')]:
        args = ['curl','--silent','--show-error','--max-time','20','--output','/dev/null','--write-out','%{http_code}','--request',method,'https://preprod.tclongages.fr'+path]
        if method == 'POST': args += ['--data','grant_type=authorization_code&client_id=invalid-test-client']
        probes[path] = int(run(args).decode())
    need(probes['/'] == 401 and probes['/oauth/token'] in [400,403] and probes['/oauth/userinfo'] in [400,403], 'Basic protection and backend reachability')
    json_write(STAGE/'apply-receipt.json', {'observedAt':datetime.now(timezone.utc).isoformat(),'sourceSha':SOURCE,'artifactSha256':ARTIFACT,'mergeCommit':receipt['mergeCommit'],'backup':receipt['backup'],'verification':verified,'provisioning':provisioned,'httpWithoutBasicFromServer':probes,'settingsDirectoryModeRestored':oct(stat.S_IMODE(settings.parent.stat().st_mode)),'passwordSetByAgent':False,'userPasswordSetupPending':True,'oauthHostedLoginVerified':False,'messagesSent':0,'productionWritten':False})
    print(json.dumps({'step':'admin_password_setup_ready','adminName':'Admin_TC_Longages','adminUid':provisioned['adminUid'],'passwordSetByAgent':False,'pagesPreserved':7,'basicProtectionVerified':True,'oauthHostedLoginVerified':False,'messagesSent':0,'productionWritten':False}), flush=True)

def resume(archive, approved):
    need(approved and pwd.getpwuid(os.geteuid()).pw_name == 'daje5127', 'Authorized hosting owner')
    need(ROOT.resolve() == ROOT and PRIVATE.resolve() == PRIVATE and archive.parent == PRIVATE and digest(archive) == ARTIFACT, 'Resume target and artifact')
    receipt = json.loads((STAGE/'stage-receipt.json').read_text()); started = json.loads((STAGE/'apply-started.json').read_text())
    need(started['sourceSha'] == SOURCE and started['artifactSha256'] == ARTIFACT and not (STAGE/'apply-receipt.json').exists(), 'Resume marker')
    need(digest(runtime(ROOT)) == receipt['runtimeHashBefore'], 'Original runtime changed')
    manifest = json.loads((STAGE/'manifest.json').read_text())
    controller = 'web/modules/custom/tcl_site/src/Controller/ClubPageController.php'
    for name,pin in manifest['files'].items():
        need(digest(STAGE/name) == pin['sha256'], 'Resume source changed')
        need(digest(ROOT/name) == pin['sha256'] or (name == controller and (ROOT/name).read_bytes() == (STAGE/'before-active'/name).read_bytes()), 'Active payload drift')
    original_ht = (STAGE/'before-active/web/.htaccess').read_bytes()
    need((ROOT/'web/.htaccess').read_bytes() in [original_ht, (STAGE/'htaccess-after').read_bytes()], 'Protection drift')
    provisioned = php('provisioncheck', ROOT)
    need(provisioned['editorialDigest'] == receipt['before']['editorialDigest'], 'Editorial drift')
    write(ROOT/controller, (STAGE/controller).read_bytes(), 0o644)
    try: finish(receipt, provisioned)
    except Exception:
        for name in ['web/sites/default/settings.php','web/.htaccess',controller]:
            write(ROOT/name, (STAGE/'before-active'/name).read_bytes(), 0o600 if name.endswith('settings.php') else 0o644)
        run([str(PHP),str(ROOT/'vendor/bin/drush'),'--root='+str(ROOT/'web'),'cache:rebuild'])
        raise

def extract(archive):
    need(archive.parent == PRIVATE and not archive.is_symlink() and digest(archive) == ARTIFACT and not STAGE.exists(), 'Candidate scope')
    with zipfile.ZipFile(str(archive)) as source:
        need(source.testzip() is None, 'Candidate CRC')
        entries = source.infolist(); names = [item.filename for item in entries]
        manifest = json.loads(source.read('manifest.json').decode())
        need(len(names) == len(set(names)) and set(names) == set(manifest['files']) | {'manifest.json'}, 'Candidate inventory')
        need(manifest['sourceSha'] == SOURCE and manifest['targetHost'] == 'preprod.tclongages.fr' and manifest['productionAllowed'] is False and manifest.get('simpleOAuthVersion') == '6.1.1', 'Candidate pins')
        STAGE.mkdir(mode=0o700)
        for item in entries:
            parts = PurePosixPath(item.filename)
            allowed = item.filename in ['manifest.json','composer.json','composer.lock','config/bureau-oauth.example.php','config/tc-identity.example.php','web/modules/custom/tcl_site/src/Controller/ClubPageController.php'] or item.filename.startswith(('web/modules/custom/tcl_bureau/','web/modules/custom/tcl_bureau_oauth/','web/modules/custom/tcl_identity/'))
            need(allowed and not parts.is_absolute() and '\\' not in item.filename and all(p not in ('','.','..') for p in parts.parts) and not item.is_dir() and item.file_size < 1024 ** 2 and stat.S_IFMT(item.external_attr >> 16) != stat.S_IFLNK, 'Candidate member')
            data = source.read(item.filename)
            if item.filename != 'manifest.json': need(len(data) == manifest['files'][item.filename]['bytes'] and hashlib.sha256(data).hexdigest() == manifest['files'][item.filename]['sha256'], 'Candidate bytes')
            write(STAGE / item.filename, data)
    return manifest

def install_files(root, manifest):
    for name, entry in manifest['files'].items():
        write(root / name, (STAGE / name).read_bytes(), 0o644)
        need(digest(root / name) == entry['sha256'], 'Copied source differs')

def stage(archive):
    for path in [ROOT, PRIVATE]: need(path.resolve() == path and not path.is_symlink(), 'Physical target')
    need(stat.S_IMODE(PRIVATE.stat().st_mode) == 0o700, 'Private directory permissions')
    manifest = extract(archive)
    before = php('inspect', ROOT); need(not before['adminAlreadyExists'], 'Existing TC Admin needs separate inspection')
    rt = runtime(ROOT); rt_hash = digest(rt)
    originals = {name:digest(ROOT/name) if (ROOT/name).is_file() else None for name in manifest['files']}
    native = load(PRIVATE / 'support-update-hosting.py', 'tcl_bureau_native')
    backup_api = load(PRIVATE / 'backup_site.py', 'tcl_bureau_backup')
    restore_api = load(PRIVATE / 'restore_backup_files.py', 'tcl_bureau_restore')
    size = sum(path.stat().st_size for path in backup_api.regular_tree(ROOT))
    need(shutil.disk_usage(str(PRIVATE)).free > size * 4 + 100 * 1024 ** 2, 'Preparation disk space')
    backup = backup_api.backup('daje5127', ROOT, PRIVATE, rt.parent, Path('/home2/daje5127/public_html'), PHP)
    print(json.dumps({'step':'fresh_backup_integrity_verified'}), flush=True)
    parent = PRIVATE / 'restorations'; parent.mkdir(mode=0o700, exist_ok=True)
    restored = restore_api.restore_files(Path(backup['directory']), parent, backup['sqlSha256'], backup['filesSha256'])
    recovery = Path(restored['directory']); copy_root = recovery / 'drupal'
    cnf = recovery / 'bureau-client.cnf'; native.mysql_config(rt.parent, cnf, 'daje5127')
    mysql = ['mysql', '--defaults-extra-file=' + str(cnf), '--batch', '--skip-column-names']
    names = native.run(mysql + ['--execute=SHOW TABLES']).decode().splitlines()
    active = sorted(name for name in names if not re.match(r'^sr_[a-f0-9]{12}_', name))
    need(len(active) == 50 and all(re.fullmatch(r'[a-zA-Z0-9_]+', name) for name in active) and not any(name.startswith(PREFIX) for name in names), 'Active tables and recovery namespace')
    dump = ['mysqldump', '--defaults-extra-file=' + str(cnf), '--single-transaction', '--skip-lock-tables', '--no-tablespaces', '--skip-triggers', '--skip-comments', '--skip-extended-insert', 'daje5127_tclpreprod']
    sql = native.run(dump + active).decode(); need(set(re.findall(r'^CREATE TABLE `([a-zA-Z0-9_]+)`', sql, re.M)) == set(active), 'SQL inventory')
    with gzip.open(str(recovery/'active-database.sql.gz'), 'wb') as stream: stream.write(sql.encode())
    rewritten = native.rewrite_sql(sql, set(active), PREFIX)
    native.run(mysql, rewritten.encode())
    rows = dump[:-1] + ['--no-create-info', 'daje5127_tclpreprod']
    need(native.rewrite_sql(native.run(rows+active).decode(), set(active), PREFIX) == native.run(rows+[PREFIX+n for n in active]).decode(), 'Restored rows differ')
    for name in active:
        original = native.run(mysql+['--execute=SHOW CREATE TABLE `'+name+'`']).decode()
        copied = native.run(mysql+['--execute=SHOW CREATE TABLE `'+PREFIX+name+'`']).decode()
        need(original.replace(name+'\t', PREFIX+name+'\t', 1).replace('CREATE TABLE `'+name+'`', 'CREATE TABLE `'+PREFIX+name+'`', 1) == copied, 'Restored schema differs')
    settings = copy_root/'web/sites/default/settings.php'
    write(settings, settings.read_bytes().replace(str(rt).encode(), str(recovery/'runtime/runtime-settings.php').encode()) + ("\nif (PHP_SAPI === 'cli' && getenv('TCL_SUPPORT_RESTORE_PREFIX') === '"+PREFIX+"') { $databases['default']['default']['prefix'] = '"+PREFIX+"'; }\n").encode())
    profile_path = recovery/'runtime/profile.json'; profile = json.loads(profile_path.read_text()); need(profile.get('composerRoot') == str(ROOT), 'Profile root')
    profile['composerRoot'] = str(copy_root); write(profile_path, json.dumps(profile).encode())
    copy_rt = recovery/'runtime/runtime-settings.php'
    replacements = {str(rt.parent):str(copy_rt.parent), str(ROOT):str(copy_root), str(PRIVATE):str(recovery)}
    transformed = re.sub('|'.join(re.escape(key) for key in sorted(replacements, key=len, reverse=True)), lambda match:replacements[match.group(0)], copy_rt.read_text())
    write(copy_rt, transformed.encode() + b"\n$config['system.mail']['interface']['default'] = 'tcl_null_mail';\n$settings['tcl_contact_mail_mode'] = 'capture';\n$config['system.mail']['interface']['tcl_site_contact'] = 'test_mail_collector';\n$settings['tcl_support_mail_mode'] = 'capture';\n$config['system.mail']['interface']['tcl_support_report'] = 'test_mail_collector';\n")
    for name in ['files','temp','config-sync']: (recovery/name).mkdir(mode=0o700, exist_ok=True)
    for name in active:
        if name.startswith('cache_'): native.run(mysql+['--execute=TRUNCATE TABLE `'+PREFIX+name+'`'])
    env = os.environ.copy(); env['TCL_SUPPORT_RESTORE_PREFIX'] = PREFIX
    copy_before = php('inspect', copy_root, env); need(copy_before['editorialDigest'] == before['editorialDigest'] and copy_before['users'] == before['users'], 'Restored Drupal values')
    install_files(copy_root, manifest)
    run(['composer','install','--no-dev','--no-interaction','--prefer-dist','--no-progress'], cwd=str(copy_root), log=STAGE/'composer-rehearsal.log')
    php('install', copy_root, env); rehearsed = php('rehearse', copy_root, env)
    need(rehearsed['editorialDigest'] == before['editorialDigest'] and rehearsed['maintenance'] == before['maintenance'] and digest(rt) == rt_hash, 'Active editorial/runtime preservation')
    need(all(digest(ROOT/name) == value if value is not None else not (ROOT/name).exists() for name,value in originals.items()), 'Active code changed during staging')
    htaccess = (ROOT/'web/.htaccess').read_text()
    need(htaccess.count('Require valid-user') == 1 and 'AuthType Basic' in htaccess and 'Site de test TC Longages' in htaccess, 'Existing Basic protection')
    write(STAGE/'htaccess-after', apache_plan(htaccess))
    cnf.unlink()
    backup['restorationTested'] = True
    receipt = {'operation':'staged_and_rehearsed', 'observedAt':datetime.now(timezone.utc).isoformat(), 'sourceSha':SOURCE, 'artifactSha256':ARTIFACT, 'mergeCommit':'8bd9b8f9c8625a5811e35eab4a8d43da7c4a1306', 'before':before, 'originalFiles':originals, 'runtimeHashBefore':rt_hash, 'htaccessHashBefore':digest(ROOT/'web/.htaccess'), 'backup':backup, 'restoredFiles':restored, 'recoveryRoot':str(copy_root), 'prefix':PREFIX, 'databaseRestoration':{'activeTables':50,'allActiveRowsCompared':True,'allActiveSchemasCompared':True,'originalTablesOverwritten':False,'previousRecoveryTablesPreserved':len(names)-50}, 'rehearsal':rehearsed, 'activeDeploymentPerformed':False, 'credentialsCreatedOnActiveSite':False, 'messagesSent':0, 'productionWritten':False}
    json_write(STAGE/'stage-receipt.json', receipt)
    print(json.dumps({'step':'recovery_and_bureau_rehearsal_passed','pages':7,'activeTables':50,'activeSiteModified':False,'messagesSent':0}), flush=True)

if __name__ == '__main__':
    parser=argparse.ArgumentParser(description=__doc__); parser.add_argument('operation',choices=['stage','plan','apply','resume']); parser.add_argument('--archive',type=Path,required=True); parser.add_argument('--security-approved',action='store_true'); args=parser.parse_args()
    os.umask(0o077)
    if args.operation == 'stage': stage(args.archive)
    elif args.operation == 'plan':
        preflight(args.archive)
        print(json.dumps({'step':'ready_for_action_time_security_approval','admin':'Admin_TC_Longages','role':'Club business and seven-page editing; not Drupal super-admin','provider':'TC only','apiBasicExemption':'server IP or loopback only, POST token / GET userinfo','passwordSetByAgent':False,'productionWritten':False}))
    elif args.operation == 'resume': resume(args.archive, args.security_approved)
    else: apply(args.archive, args.security_approved)
