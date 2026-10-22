"""Private event participation integration, loopback and fictitious data only."""
from pathlib import Path
import json, re, html, secrets, urllib.request, urllib.error, urllib.parse
import importlib.util
ROOT=Path(__file__).resolve().parents[1]
spec=importlib.util.spec_from_file_location('oauth',ROOT/'tests/drupal-bureau-oauth-http.py')
oauth=importlib.util.module_from_spec(spec);spec.loader.exec_module(oauth)
checks=[]
def check(label, ok):
 checks.append({'label':label,'passed':bool(ok)})
 assert ok,label
def api(session,action,values,origin=oauth.ORIGIN):
 req=urllib.request.Request(oauth.ORIGIN+'/evenements/reponse/'+action,data=json.dumps(values).encode(),headers={'Content-Type':'application/json','Origin':origin})
 try:r=session.client.open(req,timeout=20)
 except urllib.error.HTTPError as e:r=e
 return r.status,json.loads(r.read()),dict(r.headers)
def run():
 bureau=oauth.Session(); anon=oauth.Session()
 check('Bureau OAuth',bureau.oauth()[0]==303)
 values={'title':'Beaujolais RECETTE '+secrets.token_hex(3),'location':'Lieu fictif','description':'Fictif','start_date':'2027-11-18','end_date':'2027-11-18','start_time':'19:00','end_time':'22:00','frequency':'once','until':'','excluded':'','category':'meal','poster_post':'0','status':'scheduled','confirm':'1','save':'Enregistrer l’événement'}
 result=bureau.form('/bureau/evenements/ajouter',values)
 check('Event saved',result[0]==303)
 edit=urllib.parse.urlsplit(result[2]['Location']).path
 path=edit.replace('/modifier','/participants')
 check('Anonymous management refused',anon.get(path)[0] in (302,303,403))
 check('Response page neutral',anon.get('/evenements/reponse')[0]==200 and values['title'] not in anon.get('/evenements/reponse')[1])
 result=bureau.form(path,{'open':'1','deadline':'2027-11-17','configure':'Enregistrer l’ouverture'})
 check('Responses opened','Ouverture des réponses enregistrée' in result[1])
 invite={'name':'Participant Fictif','email':'event-fixture@example.invalid','invite':'Créer ou remplacer le lien individuel'}
 result=bureau.form(path,invite)
 match=re.search(r'/evenements/reponse#([a-f0-9]{64})',html.unescape(result[1]))
 check('Private link created',bool(match));token=match[1]
 status,b,headers=api(anon,'context',{'token':token})
 check('Private initial context',status==200 and b['context']['answer']=='unknown' and 'email' not in b['context'] and 'no-store' in headers.get('Cache-Control',''))
 base={'token':token,'csrf':b['csrf'],'revision':b['context']['revision'],'eventRevision':b['context']['eventRevision'],'answer':'yes','count':4}
 check('Foreign origin rejected',api(anon,'save',base,'https://example.invalid')[0]==403)
 check('Invalid csrf rejected',api(anon,'save',dict(base,csrf='bad'))[0]==403)
 for n in [0,-1,100,1.5,'4']:
  check('Bad count rejected '+str(n),api(anon,'save',dict(base,count=n))[0]==400)
 status,b,_=api(anon,'save',base);check('Four people confirmed',status==200 and b['context']['count']==4)
 check('Total visible in Bureau','4 personne(s) confirmée(s)' in bureau.get(path)[1])
 check('Stale answer rejected',api(anon,'save',base)[0]==409)
 base.update(revision=b['context']['revision'],answer='maybe',count=0)
 status,b,_=api(anon,'save',base);check('Maybe removes headcount',status==200 and '0 personne(s) confirmée(s)' in bureau.get(path)[1])
 base.update(revision=b['context']['revision'],answer='no',count=0)
 status,b,_=api(anon,'save',base);check('No accepted',status==200)
 base.update(revision=b['context']['revision'],answer='yes',count=2)
 status,b,_=api(anon,'save',base);check('Response can change',status==200)
 result=bureau.form(path,invite);newtoken=re.search(r'/evenements/reponse#([a-f0-9]{64})',html.unescape(result[1]))[1]
 check('Old link revoked',api(anon,'context',{'token':token})[0]==403)
 status,b,_=api(anon,'context',{'token':newtoken});check('Replacement preserves answer',status==200 and b['context']['count']==2)
 changed=dict(values,location='Autre lieu fictif');bureau.form(edit,changed)
 status,b,_=api(anon,'context',{'token':newtoken});check('Event change asks reconfirmation',b['context']['needsConfirmation'] and '0 personne(s) confirmée(s)' in bureau.get(path)[1])
 base={'token':newtoken,'csrf':b['csrf'],'revision':b['context']['revision'],'eventRevision':b['context']['eventRevision'],'answer':'yes','count':3}
 check('Reconfirmation accepted',api(anon,'save',base)[0]==200)
 bureau.form(path,{'deadline':'2027-11-17','configure':'Enregistrer l’ouverture'})
 status,b,_=api(anon,'context',{'token':newtoken});base.update(revision=b['context']['revision'])
 check('Closure displayed',not b['context']['open'])
 check('Closed reply refused',api(anon,'save',base)[0]==403)
 changed['status']='cancelled';bureau.form(edit,changed)
 check('Cancelled event refused',api(anon,'context',{'token':newtoken})[0]==403)
 check('Cancelled followup preserved','Participant Fictif' in bureau.get(path)[1])
 captain=oauth.Session();captain.oauth('captain-fixture');check('Captain denied',captain.get(path)[0]==403)
if __name__=='__main__':
 report={'scope':'loopback only','checks':checks,'realMailSent':False}
 try:run();report['passed']=True
 except Exception as e:report['passed']=False;report['error']=str(e) if isinstance(e,AssertionError) else type(e).__name__
 (ROOT/'.local/event-participation-results.json').write_text(json.dumps(report,indent=2),encoding='utf-8')
 print(json.dumps({'passed':report['passed'],'checks':len(checks),'error':report.get('error')}))
 raise SystemExit(0 if report['passed'] else 1)
