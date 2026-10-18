from pathlib import Path
import importlib.util,json,os,re,gzip,hashlib,shutil
from datetime import datetime,timezone
P=Path('/home2/daje5127/tcl-preproduction/private'); R=P/'restorations/restore-_vypl584'; ROOT=P.parent/'drupal'; PREFIX='sr_f11fe659616a_'
assert R.resolve()==R and R.is_dir() and not (R/'v2-sql-receipt.json').exists()
os.umask(0o077)
spec=importlib.util.spec_from_file_location('native',str(P/'support-update-hosting.py')); n=importlib.util.module_from_spec(spec);spec.loader.exec_module(n)
rt=P/'first-install-svfc2tg9'; cnf=R/'v2-client.cnf'; 
if not cnf.exists(): n.mysql_config(rt,cnf,'daje5127')
mysql=['mysql','--defaults-extra-file='+str(cnf),'--batch','--skip-column-names']
names=n.run(mysql+['--execute=SHOW TABLES']).decode().splitlines(); active=sorted(x for x in names if not re.match(r'^(sr_[a-f0-9]{12}_|lq[dr]_[0-9]{8}_|lqh?_[a-f0-9]{12}_)',x))
assert len(active)==62 and all(re.fullmatch('[a-zA-Z0-9_]+',x) for x in active) and not any(x.startswith(PREFIX) for x in names)
dump=['mysqldump','--defaults-extra-file='+str(cnf),'--single-transaction','--skip-lock-tables','--no-tablespaces','--skip-triggers','--skip-comments','--skip-extended-insert','daje5127_tclpreprod']
sql=n.run(dump+active).decode(); assert set(re.findall(r'^CREATE TABLE `([a-zA-Z0-9_]+)`',sql,re.M))==set(active)
with gzip.open(str(R/'v2-active-database.sql.gz'),'wb') as f:f.write(sql.encode())
n.run(mysql,n.rewrite_sql(sql,set(active),PREFIX).encode())
rows=dump[:-1]+['--no-create-info','daje5127_tclpreprod']
assert n.rewrite_sql(n.run(rows+active).decode(),set(active),PREFIX)==n.run(rows+[PREFIX+x for x in active]).decode()
for name in active:
 a=n.run(mysql+['--execute=SHOW CREATE TABLE `'+name+'`']).decode(); b=n.run(mysql+['--execute=SHOW CREATE TABLE `'+PREFIX+name+'`']).decode()
 assert a.replace(name+'\t',PREFIX+name+'\t',1).replace('CREATE TABLE `'+name+'`','CREATE TABLE `'+PREFIX+name+'`',1)==b
# Preserve the identity settings/keys privately, never in a web root.
assert not (P/'tc-identity').is_symlink() and all(not x.is_symlink() for x in (P/'tc-identity').rglob('*'))
shutil.copytree(str(P/'tc-identity'),str(R/'tc-identity'))
identity={str(x.relative_to(P/'tc-identity')):hashlib.sha256(x.read_bytes()).hexdigest() for x in (P/'tc-identity').rglob('*') if x.is_file()}
assert all(hashlib.sha256((R/'tc-identity'/x).read_bytes()).hexdigest()==h for x,h in identity.items())
copyroot=R/'drupal'; replacements={str(rt):str(R/'runtime'),str(ROOT):str(copyroot),str(P):str(R)}
for f in [copyroot/'web/sites/default/settings.php',R/'runtime/runtime-settings.php',R/'tc-identity/settings.php']:
 t=f.read_text(); t=re.sub('|'.join(re.escape(k) for k in sorted(replacements,key=len,reverse=True)),lambda m:replacements[m.group(0)],t); f.write_text(t)
s=copyroot/'web/sites/default/settings.php'
with s.open('a') as f:f.write("\nif (PHP_SAPI !== 'cli') { throw new \\RuntimeException('Private restore only'); }\n$databases['default']['default']['prefix'] = '"+PREFIX+"';\n$settings['tcl_registration_recipe_enabled'] = FALSE;\n$config['system.mail']['interface']['default'] = 'test_mail_collector';\n$settings['tcl_contact_mail_mode'] = 'capture';\n$settings['tcl_support_mail_mode'] = 'capture';\n$config['system.mail']['interface']['tcl_site_contact'] = 'test_mail_collector';\n$config['system.mail']['interface']['tcl_support_report'] = 'test_mail_collector';\n")
p=R/'runtime/profile.json';profile=json.loads(p.read_text());assert profile['composerRoot']==str(ROOT);profile['composerRoot']=str(copyroot);p.write_text(json.dumps(profile))
for x in ['files','temp','config-sync']:(R/x).mkdir(mode=0o700,exist_ok=True)
for name in active:
 if name.startswith('cache_'):n.run(mysql+['--execute=TRUNCATE TABLE `'+PREFIX+name+'`'])
cnf.unlink()
receipt={'checkedAt':datetime.now(timezone.utc).isoformat(),'activeTables':len(active),'namespace':PREFIX,'allActiveRowsCompared':True,'allActiveSchemasCompared':True,'identityFilesCompared':len(identity),'originalTablesOverwritten':False,'productionWritten':False,'databaseSnapshotSha256':hashlib.sha256((R/'v2-active-database.sql.gz').read_bytes()).hexdigest(),'drupalBootstrapped':False}
(R/'v2-sql-receipt.json').write_text(json.dumps(receipt,indent=2)); print(json.dumps(receipt))
