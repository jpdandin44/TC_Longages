from pathlib import Path
import os,json,hashlib,tarfile,subprocess,sys
from datetime import datetime,timezone
P=Path('/home2/daje5127/tcl-preproduction/private');ROOT=P.parent/'drupal';S=P/'v2-pdf-72cafd03fed7';R=P/'restorations/restore-b4_west1';B=P/'backups/20261018T211324Z';RT=P/'first-install-svfc2tg9/runtime-settings.php'
SOURCE='2c137071e2e43ec38a70885ae34ca5a51b5b989c';DIGEST='72cafd03fed7966c5a70aa8a5884502dd69600b6bb906161d026365a3c1b82b8'
os.umask(0o077)
def need(ok,msg):
 if not ok:raise ValueError(msg)
def sha(p):return hashlib.sha256(p.read_bytes()).hexdigest()
def write(p,b,mode=0o600):
 need(not p.is_symlink() and all(not q.is_symlink() for q in p.parents),'Redirected path')
 p.parent.mkdir(mode=0o700,parents=True,exist_ok=True);temp=p.with_name(p.name+'.v2-new')
 with temp.open('xb') as f:f.write(b)
 os.chmod(str(temp),mode);os.replace(str(temp),str(p))
def drush(args):
 proc=subprocess.run(['/usr/local/bin/php','-d','zend.exception_ignore_args=1',str(ROOT/'vendor/drush/drush/drush.php'),'--root='+str(ROOT/'web'),'--uri=https://preprod.tclongages.fr']+args,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
 if proc.returncode:(S/'private-error.log').write_bytes(proc.stdout+proc.stderr);raise ValueError('Private command failed; inspect private log without publishing credentials')
 return proc.stdout
PHP_INSPECT="""if(Drupal::database()->getConnectionOptions()['database']!=='daje5127_tclpreprod'||Drupal::database()->getConnectionOptions()['prefix']!==''||Drupal::VERSION!=='11.4.8'){throw new RuntimeException('Scope');}$pages=[];foreach(Drupal\\tcl_site\\PublicPageEditor::PAGES as $name){$n=Drupal\\tcl_site\\PublicPageEditor::node($name);$pages[$name]=$n->toArray();}$posts=Drupal::database()->select('tcl_bureau_post','p')->fields('p')->orderBy('id')->execute()->fetchAll(PDO::FETCH_ASSOC);foreach($posts as &$post){unset($post['channels']);}echo json_encode(['postsDigest'=>hash('sha256',serialize($posts)),'catalogueDigest'=>hash('sha256',serialize(Drupal::keyValue('tcl_bureau.seasons')->getAll())),'pages'=>count($pages),'editorialDigest'=>hash('sha256',serialize($pages)),'users'=>(int)Drupal::entityQuery('user')->accessCheck(FALSE)->count()->execute(),'registrations'=>(int)Drupal::database()->select('tcl_bureau_registration','r')->countQuery()->execute()->fetchField(),'maintenance'=>(bool)Drupal::state()->get('system.maintenance_mode')]);"""
def inspect():return json.loads(drush(['php:eval',PHP_INSPECT]).decode())
def validate():
 need(ROOT.resolve()==ROOT and P.resolve()==P and S.resolve()==S,'Target scope')
 need(sha(P/'tcl-v2-pdf-final.zip')==DIGEST,'Artifact drift')
 m=json.loads((S/'manifest.json').read_text());need(m['sourceSha']==SOURCE and m['productionAllowed'] is False,'Manifest scope')
 need(all(sha(S/n)==e['sha256'] for n,e in m['files'].items()),'Stage drift')
 q=json.loads((R/'v2-rehearsal.json').read_text());need(q['passed'] and q['coreVersion']=='11.4.8','Rehearsal absent')
 q=json.loads((R/'v2-sql-receipt.json').read_text());need(q['allActiveRowsCompared'] and q['allActiveSchemasCompared'] and q['activeTables']==62,'SQL recovery absent')
 need('AuthType Basic' in (ROOT/'web/.htaccess').read_text(),'Basic absent')
 return m
m=validate();plan=S/'delivery-plan.json';beforeDir=S/'before-active'
if sys.argv[-1]=='prepare':
 need(not plan.exists() and not beforeDir.exists(),'Plan already exists');beforeDir.mkdir(mode=0o700)
 original={};backup=json.loads((B/'receipt.json').read_text());need(sha(B/'files.tar.gz')==backup['filesSha256'],'Backup drift')
 with tarfile.open(str(B/'files.tar.gz'),'r:gz') as t:
  for n,e in m['files'].items():
   dest=ROOT/n;key='drupal/'+n;old=backup['inventory'].get(key)
   need((old is None and not dest.exists()) or (old is not None and dest.is_file() and sha(dest)==old['sha256']),'Active file changed since backup')
   original[n]=old['sha256'] if old else None
   if old:write(beforeDir/n,t.extractfile(key).read())
 write(beforeDir/'runtime-settings.php',RT.read_bytes())
 p={'sourceSha':SOURCE,'artifactSha256':DIGEST,'originalFiles':original,'runtimeSha256':sha(RT),'htaccessSha256':sha(ROOT/'web/.htaccess'),'before':inspect(),'preparedAt':datetime.now(timezone.utc).isoformat(),'productionWritten':False}
 write(plan,json.dumps(p,indent=2).encode());print(json.dumps({'prepared':True,'pages':p['before']['pages'],'files':len(original),'coreUpgrade':False,'productionWritten':False}))
elif sys.argv[-1]=='apply':
 p=json.loads(plan.read_text());need(not (S/'delivery-receipt.json').exists(),'Already delivered')
 need(sha(RT)==p['runtimeSha256'] and sha(ROOT/'web/.htaccess')==p['htaccessSha256'],'Settings drift')
 need(inspect()==p['before'],'Content drift')
 need(all(sha(ROOT/n)==h if h else not (ROOT/n).exists() for n,h in p['originalFiles'].items()),'Code drift')
 need(b'tcl_registration_recipe_enabled' in RT.read_bytes(),'Existing recipe flag absent')
 drush(['state:set','system.maintenance_mode','1','--input-format=integer'])
 try:
  for n,e in m['files'].items():write(ROOT/n,(S/n).read_bytes(),0o644)
  drush(['php:eval',"Drupal::moduleHandler()->loadInclude('tcl_bureau','install');tcl_bureau_update_11003();Drupal::service('update.update_hook_registry')->setInstalledVersion('tcl_bureau',11003);"])
  drush(['cache:rebuild']);drush(['state:set','system.maintenance_mode','1' if p['before']['maintenance'] else '0','--input-format=integer'])
  after=inspect();need(after==p['before'],'Existing content changed')
  need(all(sha(ROOT/n)==e['sha256'] for n,e in m['files'].items()),'Installed file mismatch')
  need(sha(ROOT/'web/.htaccess')==p['htaccessSha256'],'Protection changed')
  status=subprocess.check_output(['curl','--silent','--max-time','20','--output','/dev/null','--write-out','%{http_code}','https://preprod.tclongages.fr/adherer']).decode();need(status=='401','Basic protection HTTP check')
  q={'deliveredAt':datetime.now(timezone.utc).isoformat(),'sourceSha':SOURCE,'artifactSha256':DIGEST,'installedFilesVerified':len(m['files']),'preserved':after,'basicHttpStatus':401,'coreVersion':'11.4.8','coreUpgradeIncluded':False,'communicationUpdate':11003,'pdfImportOnly':True,'hostedOAuthLoginVerified':False,'realDataImported':False,'productionWritten':False}
  write(S/'delivery-receipt.json',json.dumps(q,indent=2).encode());print(json.dumps(q))
 except Exception:
  for n,h in p['originalFiles'].items():
   if h:write(ROOT/n,(beforeDir/n).read_bytes(),0o644)
   elif (ROOT/n).is_file():(ROOT/n).unlink()
  write(RT,(beforeDir/'runtime-settings.php').read_bytes());drush(['cache:rebuild']);drush(['state:set','system.maintenance_mode','1' if p['before']['maintenance'] else '0','--input-format=integer']);raise
else:raise ValueError('prepare or apply required')
