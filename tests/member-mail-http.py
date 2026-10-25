"""Loopback form flow; no real mail, contacts or hosted changes."""
from pathlib import Path
import importlib.util,json,re,secrets
ROOT=Path(__file__).resolve().parents[1]
spec=importlib.util.spec_from_file_location('oauth',ROOT/'tests/drupal-bureau-oauth-http.py');oauth=importlib.util.module_from_spec(spec);spec.loader.exec_module(oauth)
checks=[]
def check(label,value):
 checks.append({'label':label,'passed':bool(value)})
 assert value,label
def run():
 b=oauth.Session();a=oauth.Session();check('Bureau connects',b.oauth()[0]==303)
 values={'title':'Courriel fictif '+secrets.token_hex(4),'category':'event','body':'Message fictif','link':'','image_alt':'','tenup_visible':'no','channels[email]':'email'}
 response=b.form('/bureau/communication/ajouter',values);check('Email channel saves',response[0]==303)
 base='/bureau/communication/'+re.search(r'/communication/(\d+)/revue',response[2]['Location'])[1];path=base+'/courriel'
 check('Draft preview blocked',b.get(path)[0]==409)
 check('Anonymous preview blocked',a.get(path)[0] in (302,303,403))
 b.form(base+'/validate',{'personal_confirmation':'1'});check('Review offers email preparation','Préparer le courriel' in b.get(base+'/revue')[1])
 check('Not connected is explicit','Google Contacts non connecté' in b.get(path)[1])
 preview=b.form(path,{'source':'recipe','prepare':'Préparer l’aperçu'});check('Preview shows selected recipe recipients','one@example.invalid' in preview[1] and 'Message fictif' in preview[1])
 no=b.form(path,{'send':'Envoyer les courriels de recette'},preview[1]);check('Confirmation required','Choisissez les destinataires et confirmez' in no[1])
 sent=b.form(path,{'send':'Envoyer les courriels de recette','confirm':'1','recipients[one@example.invalid]':'one@example.invalid'},no[1]);check('Selected email accepted by collector','Acceptés par le serveur : 1' in sent[1])
 preview=b.form(path,{'source':'recipe','prepare':'Préparer l’aperçu'})
 sent=b.form(path,{'send':'Envoyer les courriels de recette','confirm':'1','recipients[one@example.invalid]':'one@example.invalid'},preview[1]);check('Repeat not resent','Non relancés : 1' in sent[1])
 google=b.form(path,{'source':'google','prepare':'Préparer l’aperçu'});check('Missing Google configuration fails closed','restent à configurer' in google[1])
 preview=b.form(path,{'source':'recipe','prepare':'Préparer l’aperçu'});values['body']='Modifié';b.form(base+'/modifier',values)
 check('Modified draft blocks stale send',b.form(path,{'send':'Envoyer les courriels de recette','confirm':'1','recipients[one@example.invalid]':'one@example.invalid'},preview[1])[0]==409)
 b.form(base+'/archive',{'personal_confirmation':'1'})
if __name__=='__main__':
 fixture=ROOT/'.local/bureau-oauth-settings.php';previous=fixture.read_bytes()
 report={'checks':checks,'scope':'loopback only; TestMailCollector','realMailSent':False}
 try:
  fixture.write_bytes(previous+b"\n$settings['tcl_bureau_recipe_mail']=['enabled'=>TRUE,'allowed_recipients'=>['one@example.invalid']];\n$config['system.mail']['interface']['tcl_bureau_member_communication']='test_mail_collector';\n")
  run();report['passed']=True
 except Exception as e:report['passed']=False;report['error']=str(e) if isinstance(e,AssertionError) else type(e).__name__
 finally:fixture.write_bytes(previous)
 (ROOT/'.local/member-mail-http-results.json').write_text(json.dumps(report,indent=2),encoding='utf-8');print(json.dumps({'passed':report['passed'],'checks':len(checks),'error':report.get('error')}));raise SystemExit(0 if report['passed'] else 1)
