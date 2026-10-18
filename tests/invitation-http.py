import importlib.util,json,subprocess
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
spec=importlib.util.spec_from_file_location('tc',ROOT/'tests/drupal-tc-identity-http.py');tc=importlib.util.module_from_spec(spec);spec.loader.exec_module(tc)
checks=[]
def check(label,ok):
 assert ok,label
 checks.append(label)
def fixture(op):
 subprocess.run(['php','-c',str(ROOT/'.local/drupal-tools/php.ini'),str(ROOT/'tests/invitation-http-fixture.php'),op],check=True,stdout=subprocess.DEVNULL)
fixture('prepare')
try:
 target=json.loads((ROOT/'.local/invitation-http.json').read_text())
 admin=tc.Session();check('Admin completes real local OAuth',admin.get(admin.code(json.loads((ROOT/'.local/tc-identity-local.json').read_text())))[0]==303)
 path=f"/bureau/comptes/{target['uid']}/inviter"
 anon=tc.Session();check('Anonymous send denied',anon.get(path,{'op':'send'})[0]==403)
 before=tc.Session();check('Unknown TC account remains denied before invitation',before.get(before.code(target))[0]==403)
 page=admin.get(path);check('Invitation page shows registered recipient',page[0]==200 and 'invitation-http@example.invalid' in page[1])
 check('Invalid CSRF denied',admin.form(path,{'form_token':'forged','op':'Envoyer le lien de création du mot de passe'},page[1])[0]!=303)
 check('Invitation submits through protected form',admin.form(path,{'op':'Envoyer le lien de création du mot de passe'})[0]==303)
 fixture('verify');check('One reset invitation captured without actual send',True)
 user=tc.Session();check('Invited user completes real TC OAuth',user.get(user.code(target))[0]==303)
 check('Invited captain reaches Bureau',user.get('/bureau')[0]==200)
 check('Captain cannot manage other accounts',user.get('/bureau/comptes')[0]==403)
 check('Repeated invitation rate limited','cinq minutes' in admin.form(path,{'op':'Envoyer le lien de création du mot de passe'})[1])
 (ROOT/'.local/invitation-http-tests.json').write_text(json.dumps({'checks':checks,'passed':True,'realMailSent':False},indent=2))
 print(f'{len(checks)} invitation HTTP checks passed; real local TC OAuth, no actual email.')
finally:fixture('cleanup')
