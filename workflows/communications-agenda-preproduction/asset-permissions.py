"""Repair observed public asset traversal only; preserve Basic and private files."""
from pathlib import Path
import os, json, hashlib
root=Path('/home2/daje5127/tcl-preproduction/drupal/web')
private=Path('/home2/daje5127/tcl-preproduction/private/communications-agenda-9cd0b5a47d51')
targets=[root/'modules/custom/tcl_bureau',root/'modules/custom/tcl_bureau/css']
rules=root/'.htaccess'
before=hashlib.sha256(rules.read_bytes()).hexdigest()
assert 'AuthType Basic' in rules.read_text()
assert private.resolve()==private and not (private/'asset-permissions.json').exists()
for p in targets:
    assert p.resolve()==p and p.is_dir() and p.stat().st_mode&0o777==0o700
record={'paths':{str(p):{'before':'0700','after':'0755'} for p in targets},'htaccessSha256':before,'productionWritten':False}
with (private/'asset-permissions.json').open('x') as f:json.dump(record,f,indent=2)
os.chmod(str(private/'asset-permissions.json'),0o600)
for p in targets:os.chmod(str(p),0o755)
assert hashlib.sha256(rules.read_bytes()).hexdigest()==before
assert all(p.stat().st_mode&0o777==0o755 for p in targets)
print(json.dumps({'publicAssetDirectoriesCorrected':2,'basicProtectionUnchanged':True,'privateDirectoriesUnchanged':True}))
