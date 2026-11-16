"""Public signup entry, fictitious data and loopback only."""
import importlib.util,re,json,secrets
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
spec=importlib.util.spec_from_file_location('oauth',ROOT/'tests/drupal-bureau-oauth-http.py');oauth=importlib.util.module_from_spec(spec);spec.loader.exec_module(oauth)
checks=[]
def check(label,ok):
 checks.append({'label':label,'passed':bool(ok)});assert ok,label
b=oauth.Session();a=oauth.Session();check('OAuth Bureau',b.oauth()[0]==303)
p=b.form('/bureau/communication/ajouter',{'title':'Inscription fictive '+secrets.token_hex(4),'category':'event','body':'Recette inscription','link':'','image_alt':'','tenup_visible':'no','channels[site]':'site','channels[email]':'email'})
post=re.search(r'/communication/(\d+)/revue',p[2]['Location'])[1]
for action in ['validate','publish']:check(action,b.form('/bureau/communication/'+post+'/'+action,{'personal_confirmation':'1'})[0]==303)
e=b.form('/bureau/evenements/ajouter',{'title':'Événement inscription fictive','location':'Lieu fictif','description':'Test','start_date':'2027-11-18','end_date':'2027-11-18','start_time':'19:00','end_time':'22:00','frequency':'once','until':'','excluded':'','category':'meal','poster_post':'0','status':'scheduled','confirm':'1','save':'Enregistrer l’événement'})
path=re.search(r'(/bureau/evenements/[a-f0-9]+)',e[2]['Location'])[1]+'/participants'
b.form(path,{'open':'1','deadline':'2027-11-17','registration_post':post,'configure':'Enregistrer l’ouverture'})
entry='/actualites/'+post+'/inscription';status,html,headers=a.get(entry)
check('Anonymous signup accessible',status==200 and 'Recevoir mon lien personnel' in html)
check('CSRF field present','name="signup_csrf"' in html)
r=a.form(entry,{'name':'Fictif','email':'not-authorized@example.invalid','signup_csrf':'wrong'})
check('Forged CSRF rejected','session a expiré' in r[1])
r=a.form(entry,{'name':'Fictif','email':'not-authorized@example.invalid'})
check('Valid request redirects without disclosing eligibility',r[0]==303)
check('Generic response','Si cette adresse est autorisée' in a.get(entry)[1])
home=a.get('/')[1];check('Homepage signup button','Je m’inscris à cet événement' in home);check('No bottom duplicate','id="actualites-v2"' not in home)
check('Article signup button','Je m’inscris à cet événement' in a.get('/actualites/'+post)[1])
b.form('/bureau/communication/'+post+'/withdraw',{'personal_confirmation':'1'})
check('Withdrawal closes entry',a.get(entry)[0]==404)
(ROOT/'.local/event-signup-http-results.json').write_text(json.dumps({'passed':True,'checks':checks,'realMailSent':False},ensure_ascii=False,indent=2),encoding='utf-8');print(str(len(checks))+' signup HTTP checks passed')

