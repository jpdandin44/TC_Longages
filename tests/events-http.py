"""Loopback-only event CRUD, recurrence and access integration."""
import importlib.util, json, re, secrets, sqlite3
from pathlib import Path
ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('oauth_recipe', ROOT / 'tests/drupal-bureau-oauth-http.py')
oauth = importlib.util.module_from_spec(spec); spec.loader.exec_module(oauth)
checks=[]
def check(label, ok):
    checks.append({'label':label,'passed':bool(ok)})
    assert ok, label

def run():
    bureau, anonymous = oauth.Session(), oauth.Session()
    path='/bureau/evenements/ajouter'
    check('Anonymous write denied', anonymous.get(path, {'title':'bad'})[0] == 403)
    check('Bureau OAuth works', bureau.oauth()[0] == 303)
    check('Bureau navigation exposes recipe agenda', 'Agenda de recette' in bureau.get('/bureau')[1])
    values={'title':'Recette agenda '+secrets.token_hex(4),'location':'Court fictif','description':'Données fictives','start_date':'2027-03-21','start_time':'10:00','end_date':'2027-03-21','end_time':'11:30','frequency':'weekly','until':'2027-04-04','excluded':'2027-03-28','status':'scheduled','confirm':'1','save':'Enregistrer l’événement'}
    preview=dict(values); preview.pop('save'); preview['preview']='Vérifier les dates'
    response=bureau.form(path,preview)
    check('Preview shows two dates without saving', response[0]==200 and '2 date(s)' in response[1])
    count_before=len(re.findall('/bureau/evenements/[a-f0-9]{32}/modifier',bureau.get('/bureau/evenements')[1]))
    check('Preview does not create a record', values['title'] not in bureau.get('/bureau/evenements')[1])
    response=bureau.form(path,values)
    check('Recurring event saves',response[0]==303)
    edit=oauth.urllib.parse.urlsplit(response[2]['Location']).path
    body=bureau.get(edit)[1]
    check('Stored recurrence and exception appear', '2027-03-28' in body and '2 date(s)' in body)
    check('No public event route', anonymous.get(edit)[0] in [302,303,403])
    old=body
    changed=dict(values,title=values['title']+' modifié',excluded='',status='cancelled')
    check('Whole series edit and cancel saved',bureau.form(edit,changed,old)[0]==303)
    check('Stale editor cannot overwrite', 'a changé' in bureau.form(edit,values,old)[1])
    body=bureau.get(edit)[1]
    check('Cancellation retains three calculated dates', '3 date(s)' in body and 'value="cancelled" selected' in body)
    bad=dict(changed,form_token='invalid',title='CSRF overwrite')
    bureau.form(edit,bad)
    check('CSRF cannot overwrite', 'CSRF overwrite' not in bureau.get(edit)[1])
    bad=dict(changed,end_time='09:00')
    check('Invalid interval rejected', 'La fin doit suivre' in bureau.form(edit,bad)[1])
    captain=oauth.Session(); check('Captain OAuth works',captain.oauth('captain-fixture')[0]==303)
    check('Captain cannot list events',captain.get('/bureau/evenements')[0]==403)
    check('Captain cannot modify events',captain.get(edit,values)[0]==403)
    check('Unknown event returns 404',bureau.get('/bureau/evenements/'+'0'*32+'/modifier')[0]==404)
    check('Cancelled title safely listed', changed['title'] in bureau.get('/bureau/evenements')[1])
    single=dict(values,title='Recette ponctuelle',frequency='once',until='',excluded='')
    create_body=bureau.get(path)[1]
    response=bureau.form(path,single,create_body)
    check('Single event saves',response[0]==303)
    single_edit=oauth.urllib.parse.urlsplit(response[2]['Location']).path
    check('Single event has one occurrence','1 date(s)' in bureau.get(single_edit)[1])
    check('Replayed creation cannot duplicate','a changé' in bureau.form(path,single,create_body)[1])
    single['status']='cancelled'; bureau.form(single_edit,single)
    return edit
if __name__=='__main__':
    try:
        edit=run(); passed=True
    finally:
        (ROOT/'.local/events-http-results.json').write_text(json.dumps({'checks':checks},ensure_ascii=False,indent=2),encoding='utf-8')
    print(str(len(checks))+' event HTTP checks passed.')

