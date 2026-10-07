"""Pinned edition/contact patch; private preproduction only, no real test mail."""
import argparse,gzip,hashlib,importlib.util,json,os,re,shutil,stat,subprocess,zipfile
from pathlib import Path
from datetime import datetime,timezone

PRIVATE=Path('/home2/daje5127/tcl-preproduction/private')
ROOT=Path('/home2/daje5127/tcl-preproduction/drupal')
PHP=Path('/usr/local/bin/php')
ALLOWED_CHANGES={
 'web/modules/custom/tcl_site/src/Controller/ClubPageController.php',
 'web/modules/custom/tcl_site/tcl_site.routing.yml',
 'web/modules/custom/tcl_site/src/ClubContactPage.php',
 'web/modules/custom/tcl_site/src/Form/ClubContactForm.php',
 'web/modules/custom/tcl_site/tcl_site.module',
}
def need(value,message):
 if not value: raise ValueError(message)
def digest(path):
 h=hashlib.sha256()
 with path.open('rb') as stream:
  for chunk in iter(lambda:stream.read(1024*1024),b''): h.update(chunk)
 return h.hexdigest()
def load(path,name):
 spec=importlib.util.spec_from_file_location(name,str(path)); result=importlib.util.module_from_spec(spec);spec.loader.exec_module(result);return result
def write_json(path,value):
 with path.open('x',encoding='utf-8') as stream:json.dump(value,stream,ensure_ascii=False,indent=2)
 os.chmod(str(path),0o600)
def atomic(path,data,mode=0o644):
 path.parent.mkdir(parents=True,exist_ok=True)
 temporary=path.with_name(path.name+'.edition-contact-tmp')
 need(not temporary.exists(),'Temporary file already exists')
 with temporary.open('xb') as stream:stream.write(data)
 os.chmod(str(temporary),mode);os.replace(str(temporary),str(path))
def runtime(root,recovery=None):
 matches=re.findall(r"require\s+'([^']+/runtime-settings\.php)'\s*;",(root/'web/sites/default/settings.php').read_text())
 need(len(matches)==1,'Single runtime expected'); path=Path(matches[0])
 need(path.resolve()==path and not path.is_symlink(),'Runtime path')
 need(path.parent.parent==PRIVATE if recovery is None else str(path).startswith(str(recovery)+'/'),'Runtime scope')
 return path
def contact_settings(path,capture):
 original=path.read_bytes(); text=original.decode('utf-8')
 need(text.startswith('<?php') and not text.rstrip().endswith('?>') and 'tcl_contact_' not in text,'Unexpected contact settings')
 extra="\n// TC contact form: independent from support notifications.\n$settings['tcl_contact_enabled'] = TRUE;\n$settings['tcl_contact_mail_mode'] = '%s';\n$settings['tcl_contact_transport_qualified'] = %s;\n$config['system.mail']['interface']['tcl_site_contact'] = '%s';\n"%('capture' if capture else 'transport','FALSE' if capture else 'TRUE','test_mail_collector' if capture else 'php_mail')
 temporary=path.with_name('contact-settings-syntax.php')
 need(not temporary.exists(),'Settings syntax file exists')
 temporary.write_bytes(original+extra.encode());os.chmod(str(temporary),0o600)
 process=subprocess.run([str(PHP),'-l',str(temporary)],stdout=subprocess.PIPE,stderr=subprocess.PIPE)
 need(process.returncode==0,'Settings syntax failed');os.replace(str(temporary),str(path))
 return original
def main():
 parser=argparse.ArgumentParser(description=__doc__)
 parser.add_argument('operation',choices=['stage','apply']);parser.add_argument('--source',required=True);parser.add_argument('--sha256',required=True);parser.add_argument('--archive',type=Path)
 args=parser.parse_args()
 need(re.fullmatch('[a-f0-9]{40}',args.source) and re.fullmatch('[a-f0-9]{64}',args.sha256),'Pinned identifiers')
 need(PRIVATE.resolve()==PRIVATE and ROOT.resolve()==ROOT and stat.S_IMODE(PRIVATE.stat().st_mode)==0o700,'Private target')
 candidate=PRIVATE/('site-fix-'+args.source[:12]+'-'+args.sha256[:12]);prefix='sr_'+args.sha256[:12]+'_'
 if args.operation=='stage':
  need(args.archive and args.archive.parent==PRIVATE and digest(args.archive)==args.sha256 and not candidate.exists(),'Archive scope')
  with zipfile.ZipFile(str(args.archive)) as archive:
   need(archive.testzip() is None,'Archive CRC');manifest=json.loads(archive.read('manifest.json').decode())
   names=[i.filename for i in archive.infolist()]
   need(len(names)==len(set(names)) and set(names)==set(manifest['files'])|{'manifest.json'},'Archive inventory')
   need(manifest['sourceSha']==args.source and manifest['targetHost']=='preprod.tclongages.fr' and manifest['productionAllowed'] is False,'Archive target')
   candidate.mkdir(mode=0o700)
   for item in archive.infolist():
    path=Path(item.filename)
    need(not path.is_absolute() and '..' not in path.parts and not item.is_dir()
     and (item.filename=='manifest.json' or item.filename.startswith(('web/modules/custom/tcl_site/','web/modules/custom/tcl_support/','site-pages/','tools/'))),'Archive path')
    data=archive.read(item)
    if item.filename!='manifest.json':
     entry=manifest['files'][item.filename];need(len(data)==entry['bytes'] and hashlib.sha256(data).hexdigest()==entry['sha256'],'Archive member')
    atomic(candidate/path,data,0o600)
  write_json(candidate/'candidate-pins.json',{'sourceSha':args.source,'artifactSha256':args.sha256})
 native=load(candidate/'tools/support-update-hosting.py','edition_contact_native')
 manifest=native.verify_candidate(candidate,args.source,args.sha256)
 def php(operation,root,env=None):
  result=json.loads(native.run([str(PHP),'-d','zend.exception_ignore_args=1',str(PRIVATE/'edition-contact-hosting.php'),operation,str(root),str(candidate)],env=env).decode())
  need(result.get('success') is True,'PHP verification failed');return result
 before=php('before',ROOT);rt=runtime(ROOT);runtime_hash=digest(rt)
 originals={}
 for name,entry in manifest['files'].items():
  path=ROOT/name;current=digest(path) if path.is_file() else None
  need(current==entry['sha256'] or name in ALLOWED_CHANGES,'Unexpected active template/support change')
  originals[name]=current
 if args.operation=='stage':
  backup_api=load(candidate/'tools/backup_site.py','edition_contact_backup')
  size=sum(p.stat().st_size for p in backup_api.regular_tree(ROOT))
  need(shutil.disk_usage(str(PRIVATE)).free>size*3+50*1024**2,'Recovery free space')
  backup=backup_api.backup('daje5127',ROOT,PRIVATE,rt.parent,Path('/home2/daje5127/public_html'),PHP)
  print(json.dumps({'step':'fresh_backup_integrity_verified'}),flush=True)
  restore_api=load(candidate/'tools/restore_backup_files.py','edition_contact_restore')
  parent=PRIVATE/'restorations';parent.mkdir(mode=0o700,exist_ok=True)
  restored=restore_api.restore_files(Path(backup['directory']),parent,backup['sqlSha256'],backup['filesSha256'])
  recovery=Path(restored['directory']);copy_root=recovery/'drupal';cnf=recovery/'contact-client.cnf';native.mysql_config(rt.parent,cnf,'daje5127')
  mysql=['mysql','--defaults-extra-file='+str(cnf),'--batch','--skip-column-names']
  names=native.run(mysql+['--execute=SHOW TABLES']).decode().splitlines()
  active=sorted(n for n in names if not re.match(r'^sr_[a-f0-9]{12}_',n))
  need(len(active)==50 and not any(n.startswith(prefix) for n in names) and all(re.fullmatch('[a-zA-Z0-9_]+',n) for n in active),'Isolated recovery namespace')
  dump=['mysqldump','--defaults-extra-file='+str(cnf),'--single-transaction','--skip-lock-tables','--no-tablespaces','--skip-triggers','--skip-comments','--skip-extended-insert','daje5127_tclpreprod']
  sql=native.run(dump+active).decode();need(set(re.findall(r'^CREATE TABLE `([a-zA-Z0-9_]+)`',sql,re.M))==set(active),'Active snapshot')
  with gzip.open(str(recovery/'active-database.sql.gz'),'wb') as stream:stream.write(sql.encode())
  rewritten=native.rewrite_sql(sql,set(active),prefix);need(set(re.findall(r'^DROP TABLE IF EXISTS `([a-zA-Z0-9_]+)`',rewritten,re.M))=={prefix+n for n in active},'SQL recovery scope')
  native.run(mysql,rewritten.encode())
  rows=dump[:-1]+['--no-create-info','daje5127_tclpreprod']
  need(native.rewrite_sql(native.run(rows+active).decode(),set(active),prefix)==native.run(rows+[prefix+n for n in active]).decode(),'Restored rows')
  for name in active:
   original=native.run(mysql+['--execute=SHOW CREATE TABLE `'+name+'`']).decode()
   copied=native.run(mysql+['--execute=SHOW CREATE TABLE `'+prefix+name+'`']).decode()
   need(original.replace(name+'\t',prefix+name+'\t',1).replace('CREATE TABLE `'+name+'`','CREATE TABLE `'+prefix+name+'`',1)==copied,'Restored schema')
  settings=copy_root/'web/sites/default/settings.php'
  with settings.open('a') as stream:stream.write("\nif (PHP_SAPI === 'cli' && getenv('TCL_SUPPORT_RESTORE_PREFIX') === '"+prefix+"') { $databases['default']['default']['prefix'] = '"+prefix+"'; }\n")
  for name in active:
   if name.startswith('cache_'):native.run(mysql+['--execute=TRUNCATE TABLE `'+prefix+name+'`'])
  for name in manifest['files']:atomic(copy_root/name,(candidate/name).read_bytes())
  copy_rt=runtime(copy_root,recovery);contact_settings(copy_rt,True)
  env=os.environ.copy();env['TCL_SUPPORT_RESTORE_PREFIX']=prefix
  php('rebuild',copy_root,env);rehearsal=php('rehearse',copy_root,env)
  need(rehearsal['editorialDigest']==before['editorialDigest'] and rehearsal['maintenanceEnabled']==before['maintenanceEnabled'],'Rehearsal editorial/state conservation')
  need(digest(rt)==runtime_hash,'Active runtime changed during rehearsal')
  cnf.unlink();backup['restorationTested']=True
  receipt={'observedAt':datetime.now(timezone.utc).isoformat(),'sourceSha':args.source,'artifactSha256':args.sha256,
   'before':before,'originalFiles':originals,'runtimeHashBefore':runtime_hash,'backup':{k:v for k,v in backup.items() if k!='inventory'},
   'restoredFiles':restored,'databaseRestoration':{'activeTables':50,'previousRecoveryTablesPreserved':len(names)-50,'allActiveRowsCompared':True,'allActiveSchemasCompared':True,'namespace':prefix,'originalTablesOverwritten':False},
   'rehearsal':rehearsal,'deploymentExecuted':False,'productionWritten':False,'messagesSent':0}
  write_json(candidate/'edition-contact-stage-receipt.json',receipt)
  print(json.dumps({'step':'restoration_and_rehearsal_passed','pages':7,'messagesSent':0}),flush=True)
 else:
  receipt=json.loads((candidate/'edition-contact-stage-receipt.json').read_text())
  need(receipt['sourceSha']==args.source and receipt['artifactSha256']==args.sha256 and receipt['backup']['restorationTested'],'Qualified rehearsal')
  need(runtime_hash==receipt['runtimeHashBefore'] and originals==receipt['originalFiles'] and before['editorialDigest']==receipt['before']['editorialDigest'] and before['maintenanceEnabled']==receipt['before']['maintenanceEnabled'],'Active state changed after rehearsal')
  backup_dir=candidate/'before-files';backup_dir.mkdir(mode=0o700)
  for name,old in originals.items():
   if old is not None:atomic(backup_dir/name,(ROOT/name).read_bytes(),0o600)
  runtime_original=rt.read_bytes();atomic(candidate/'before-runtime-settings.php',runtime_original,0o600)
  try:
   for name in manifest['files']:atomic(ROOT/name,(candidate/name).read_bytes())
   contact_settings(rt,False);php('rebuild',ROOT);verified=php('verify',ROOT)
   need(verified['editorialDigest']==before['editorialDigest'] and verified['maintenanceEnabled']==before['maintenanceEnabled'],'Active editorial/state conservation')
  except Exception:
   for name,old in originals.items():
    if old is not None:atomic(ROOT/name,(backup_dir/name).read_bytes())
    elif (ROOT/name).is_file() and digest(ROOT/name)==manifest['files'][name]['sha256']:(ROOT/name).unlink()
   atomic(rt,runtime_original,0o600);php('rebuild',ROOT);raise
  write_json(candidate/'edition-contact-apply-receipt.json',{'observedAt':datetime.now(timezone.utc).isoformat(),
   'sourceSha':args.source,'artifactSha256':args.sha256,'target':'https://preprod.tclongages.fr/',
   'verified':verified,'runtimeHashAfter':digest(rt),'editorialValuesPreserved':True,'backupReceipt':'edition-contact-stage-receipt.json',
   'deploymentExecuted':True,'productionWritten':False,'messagesSent':0,'gmailInboxReceptionVerified':False})
  print(json.dumps({'step':'installed_verified','pages':7,'contactRecipient':'tclongages@gmail.com','messagesSent':0,'productionWritten':False}),flush=True)

if __name__=='__main__':
 try:main()
 except Exception as error:
  print(json.dumps({'success':False,'errorType':type(error).__name__,'reason':str(error)}));raise SystemExit(1)
