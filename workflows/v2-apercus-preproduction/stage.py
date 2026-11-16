from pathlib import Path,PurePosixPath
import zipfile,json,hashlib,os,subprocess
P=Path('/home2/daje5127/tcl-preproduction/private');R=P/'restorations/restore-13dh72qp';Z=P/'tcl-v2-update-8b7ab6f78a8d.zip';S=P/'v2-update-95540db934f9';root=R/'drupal'
os.umask(0o077)
assert Z.is_file() and hashlib.sha256(Z.read_bytes()).hexdigest()=='95540db934f92867d01a7301e428bb91712bf0adc1a0a67a439896c225f4f48f' and not S.exists()
with zipfile.ZipFile(str(Z)) as z:
 m=json.loads(z.read('manifest.json').decode());assert m['sourceSha']=='8b7ab6f78a8d9de6b3e8b60484cd126b8ddf59e8' and m['productionAllowed'] is False and m['targetHost']=='preprod.tclongages.fr'
 assert len(z.namelist())==len(set(z.namelist())) and set(z.namelist())==set(m['files'])|{'manifest.json'}
 S.mkdir(mode=0o700)
 for name,pin in m['files'].items():
  path=PurePosixPath(name);assert not path.is_absolute() and '..' not in path.parts and name.startswith(('web/modules/custom/tcl_bureau/','web/modules/custom/tcl_bureau_oauth/'))
  b=z.read(name);assert len(b)==pin['bytes'] and hashlib.sha256(b).hexdigest()==pin['sha256']
  for dest in [S/name,root/name]:
   assert not dest.is_symlink() and all(not p.is_symlink() for p in dest.parents)
   dest.parent.mkdir(mode=0o700,parents=True,exist_ok=True);dest.write_bytes(b)
 (S/'manifest.json').write_text(json.dumps(m,indent=2))
settings=root/'web/sites/default/settings.php'
with settings.open('a') as f:f.write("\n$settings['tcl_registration_recipe_enabled'] = TRUE;\n")
p=subprocess.run(['/usr/local/bin/php',str(root/'vendor/drush/drush/drush.php'),'--root='+str(root/'web'),'--uri=https://preprod.tclongages.fr','cache:rebuild'],stdout=subprocess.PIPE,stderr=subprocess.PIPE);(S/'cache-rebuild.log').write_bytes(p.stdout+p.stderr);assert p.returncode==0
print(json.dumps({'stagedFiles':len(m['files']),'privateCopyOnly':True,'coreVersion':'11.4.8','cacheRebuildPassed':True}))
