"""Exact thirteen-file agenda preproduction patch; prepare recovery before explicit apply."""
from pathlib import Path
import hashlib, json, os, subprocess, sys, zipfile
from datetime import datetime, timezone
P = Path('/home2/daje5127/tcl-preproduction/private')
ROOT = P.parent / 'drupal'
S = P / 'agenda-events-02b4112dd95f'
Z = P / 'agenda-events-02b4112dd95f.zip'
SOURCE = '02b4112dd95f1d11cf3ea1a23d1142316c15470f'
DIGEST = '4b03648eec02e9c8ce1def3bef5351b4f5b3a1be6d89dbf3757607f0d9539d68'
NAMES = {'web/modules/custom/tcl_bureau/src/Form/EventForm.php', 'web/modules/custom/tcl_bureau/src/Controller/RegistrationController.php', 'web/modules/custom/tcl_bureau/css/agenda.css', 'web/modules/custom/tcl_bureau/tcl_bureau.services.yml', 'web/modules/custom/tcl_bureau/tcl_bureau.routing.yml', 'web/modules/custom/tcl_bureau/js/agenda-tools.js', 'web/modules/custom/tcl_bureau/src/Controller/EventController.php', 'web/modules/custom/tcl_bureau/src/EventSchedule.php', 'web/modules/custom/tcl_bureau/src/EventRepository.php', 'web/modules/custom/tcl_bureau/tcl_bureau.libraries.yml', 'web/modules/custom/tcl_bureau/src/Controller/BureauController.php', 'web/modules/custom/tcl_bureau/css/events.css', 'web/modules/custom/tcl_bureau/src/AgendaPresentation.php'}
os.umask(0o077)
def need(ok, message):
    if not ok: raise ValueError(message)
def sha(b): return hashlib.sha256(b).hexdigest()
def safe(p):
    need(not p.is_symlink() and all(not q.is_symlink() for q in p.parents), 'Redirected path')
def drush(args):
    r = subprocess.run(['/usr/local/bin/php', '-d', 'zend.exception_ignore_args=1', str(ROOT/'vendor/drush/drush/drush.php'), '--root='+str(ROOT/'web'), '--uri=https://preprod.tclongages.fr']+args, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    if r.returncode:
        (S/'private-error.log').write_bytes(r.stdout+r.stderr)
        raise ValueError('Private command failed; output retained privately')
    return r.stdout
PHP_INSPECT = """if(Drupal::database()->getConnectionOptions()['database']!=='daje5127_tclpreprod'||Drupal::database()->getConnectionOptions()['prefix']!==''||Drupal::VERSION!=='11.4.8'){throw new RuntimeException('Scope');}$data=[];foreach(['tcl_bureau_post','tcl_bureau_registration','users_field_data','node_field_data','node__field_tcl_intro','node__field_tcl_textes'] as $t){$rows=Drupal::database()->select($t,'x')->fields('x')->execute()->fetchAll(PDO::FETCH_ASSOC);foreach($rows as &$row){foreach($row as &$v){if(is_resource($v)){$v=stream_get_contents($v);}}unset($v);}unset($row);$data[$t]=hash('sha256',serialize($rows));}$data['catalogue']=hash('sha256',serialize(Drupal::keyValue('tcl_bureau.seasons')->getAll()));$data['events']=hash('sha256',serialize(Drupal::keyValue('tcl_bureau.recipe_events')->getAll()));$data['mail']=hash('sha256',serialize(Drupal::config('system.site')->get('mail')));$data['maintenance']=(bool)Drupal::state()->get('system.maintenance_mode');echo json_encode($data);"""
def inspect(): return json.loads(drush(['php:eval',PHP_INSPECT]).decode())
def protections():
    paths=[ROOT/'web/.htaccess',ROOT/'web/sites/default/settings.php',P/'first-install-svfc2tg9/runtime-settings.php']
    need('AuthType Basic' in paths[0].read_text(), 'Basic protection missing')
    return {str(p):sha(p.read_bytes()) for p in paths}
def write(p,b,mode):
    safe(p); need(p.parent.is_dir(),'Missing parent')
    tmp=p.with_name(p.name+'.agenda-new'); need(not tmp.exists(),'Temporary path exists')
    with tmp.open('xb') as f:f.write(b)
    os.chmod(str(tmp),mode);os.replace(str(tmp),str(p))
safe(ROOT);safe(P);safe(S);safe(Z)
need(ROOT.resolve()==ROOT and P.resolve()==P,'Target scope')
need(sha(Z.read_bytes())==DIGEST,'Artifact mismatch')
with zipfile.ZipFile(str(Z)) as z:
    manifest=json.loads(z.read('manifest.json').decode())
    need(set(z.namelist())==NAMES|{'manifest.json'} and len(z.namelist())==14,'Archive scope')
    need(manifest['sourceSha']==SOURCE and manifest['productionAllowed'] is False and manifest['databaseMigration'] is False and set(manifest['files'])==NAMES,'Manifest scope')
    payload={n:z.read(n) for n in NAMES}
    need(all(sha(b)==manifest['files'][n]['sha256'] for n,b in payload.items()),'Payload mismatch')
mode=sys.argv[1]
if mode=='prepare':
    need(not S.exists(),'Preparation already exists');S.mkdir(mode=0o700)
    plan={'observedAt':datetime.now(timezone.utc).isoformat(),'sourceSha':SOURCE,'artifactSha256':DIGEST,'before':{},'protections':protections(),'state':inspect()}
    for n,b in payload.items():
        live=ROOT/n;safe(live); prior=live.read_bytes() if live.exists() else None
        expected=manifest['files'][n]['previousSha256']
        need((prior is None and expected is None) or (prior is not None and sha(prior.replace(b'\r\n',b'\n')) in [expected,sha(b)]),'Active source differs: '+n)
        plan['before'][n]={'sha256':sha(prior) if prior is not None else None,'mode':live.stat().st_mode&0o777 if prior is not None else 0o644}
        for area,content in [('candidate',b),('backup',prior),('restored',prior)]:
            if content is None:continue
            dest=S/area/n;dest.parent.mkdir(parents=True,exist_ok=True);dest.write_bytes(content)
        if prior is not None:need((S/'restored'/n).read_bytes()==prior,'Restore comparison failed')
        if n.endswith('.php'):
            r=subprocess.run(['/usr/local/bin/php','-l',str(S/'candidate'/n)],stdout=subprocess.PIPE,stderr=subprocess.PIPE)
            need(r.returncode==0,'PHP lint failed: '+n)
    plan['restoreStatus']='passed'
    (S/'plan.json').write_text(json.dumps(plan,indent=2))
    print(json.dumps({'prepared':True,'files':len(payload),'restoreStatus':'passed','activeFilesWritten':False,'databaseMigration':False}))
elif mode=='apply':
    plan=json.loads((S/'plan.json').read_text());need(not (S/'receipt.json').exists(),'Already applied')
    need(plan['sourceSha']==SOURCE and plan['artifactSha256']==DIGEST and plan['restoreStatus']=='passed','Recovery missing')
    need(plan['protections']==protections() and plan['state']==inspect(),'State changed after preparation')
    for n,old in plan['before'].items():
        p=ROOT/n;safe(p);need((sha(p.read_bytes()) if p.exists() else None)==old['sha256'],'Active file drift')
    changed=[]
    try:
        # Install dependencies before callers. No database update or configuration write.
        for n in sorted(payload,key=lambda n:('Controller/' in n,n)):
            write(ROOT/n,payload[n],plan['before'][n]['mode']);changed.append(n)
        drush(['cache:rebuild'])
        need(plan['protections']==protections() and plan['state']==inspect(),'Post-install state changed')
        need(all(sha((ROOT/n).read_bytes())==sha(b) for n,b in payload.items()),'Installed bytes mismatch')
        receipt={'deliveredAt':datetime.now(timezone.utc).isoformat(),'sourceSha':SOURCE,'artifactSha256':DIGEST,'filesVerified':len(payload),'contentAndProtectionPreserved':True,'databaseMigration':False,'productionWritten':False}
        (S/'receipt.json').write_text(json.dumps(receipt,indent=2));print(json.dumps(receipt))
    except Exception:
        for n in reversed(changed):
            old=plan['before'][n];p=ROOT/n;safe(p)
            if old['sha256'] is None:p.unlink()
            else:write(p,(S/'backup'/n).read_bytes(),old['mode'])
        drush(['cache:rebuild']);raise
else:raise ValueError('prepare or apply required')
