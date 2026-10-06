import test from 'node:test';
import assert from 'node:assert/strict';
import {mkdtemp,mkdir,writeFile,readFile,rm,readdir} from 'node:fs/promises';
import {tmpdir} from 'node:os';
import {resolve,sep} from 'node:path';
import {request} from 'node:http';
import {startFrameworkServer,projectRoot} from '../scripts/framework-server.mjs';
import {hash,validateInteractiveState,recoverReviewDraft} from '../scripts/framework-store.mjs';
import {safeProjectUrl,reviewGuidance,retainedReviewDraft,approvalInputState,clientScript} from '../scripts/framework-ui.mjs';
import {Script} from 'node:vm';

async function fixture(t,{bootstrap=false,qualified=false,iterations=false,regenerate,getPullRequests,verifyPullRequest=async ({url,sourceCommit})=>({passed:true,status:'merged',url,headCommit:sourceCommit,mergedAt:'2026-10-05T10:00:00Z',checkedAt:new Date().toISOString(),issues:[]})}={}) {
 const root=await mkdtemp(resolve(tmpdir(),'tcl-framework-test-'));
 assert.ok(root.startsWith(resolve(tmpdir())+sep));
 await mkdir(resolve(root,'framework'));await mkdir(resolve(root,'docs/suivi-chantier'),{recursive:true});
 const profile=JSON.parse(await readFile(resolve(projectRoot,'framework/profil-projet.json'),'utf8'));
 const tracker=JSON.parse(await readFile(resolve(projectRoot,'docs/suivi-chantier/suivi-chantier.json'),'utf8'));
 tracker.reviewEvents=[];tracker.reviewCommentResponses=[];tracker.history=[];tracker.decisions=tracker.decisions.filter(d=>d.id===profile.project.initialAuthorizationRef);tracker.evidence=[];tracker.testRuns=[];
 tracker.phases.forEach(p=>{delete p.observedExecution;p.status=p.id===0?'in_progress':'not_started';p.blockers=[];p.deliveredOn=null;p.validatedOn=null;p.validationEvidence=null;p.reviewContext=null;if(p.id>0){p.startEvidence=null;p.startedOn=null;p.authorizedOn=null;}});
 for(const p of tracker.phases) for(const d of p.deliverables) await writeFile(resolve(root,'docs/suivi-chantier',d.path),'---\nowner: jpdandin\n---\n\n# Dossier de test fictif\n\n'+p.title+' : contenu fictif détaillé, isolé du vrai dossier.');
 if(qualified) {
   const p=tracker.phases[0],doc=await readFile(resolve(root,'docs/suivi-chantier',p.deliverables[0].path),'utf8');
   p.pullRequest='https://github.com/jpdandin44/TC_Longages/pull/1';
   p.reviewContext={sourceCommit:'a'.repeat(40),artifactDigest:'b'.repeat(64),criteriaDigest:hash(p.exitCriteria),documentDigest:hash([{path:p.deliverables[0].path,digest:hash(doc)}]),evidenceRefs:['E1'],testRunRefs:['T1']};
   tracker.evidence.push({id:'E1',phaseId:0,status:'passed',environment:'local',sourceCommit:'a'.repeat(40),artifactDigest:'b'.repeat(64),recordedAt:new Date().toISOString(),actor:'Fixture uniquement',artifactRef:'docs/recette-fictive.json',actualResult:'Résultat fictif dans un test isolé'});
   tracker.testRuns.push({...tracker.evidence[0],id:'T1'});
 }
 await writeFile(resolve(root,'framework/profil-projet.json'),JSON.stringify(profile));
 if(iterations)tracker.developmentIterations=[{iterationId:'fixture-local',owner:'jpdandin',stage:'developpement_local',objective:'Lot fictif local',github:{},candidate:null,checks:[],blockers:[]}];
 if(bootstrap){const expiresAt=new Date(Date.now()+60000).toISOString(),authorization={id:'test-authorized',actor:'jpdandin',sourceRef:'test-only',scope:'Installation locale fictive'};tracker.decisions.push({...authorization,type:'installation_authorization',status:'approved',project:'tclongages',environment:'local',expiresAt});await writeFile(resolve(root,'framework/installation.json'),JSON.stringify({project:'tclongages',status:'active',environment:'local',allowedPhaseIds:[0,1],activatedAt:new Date().toISOString(),expiresAt,authorization,protections:{remoteExposureAllowed:false,deployAllowed:false,csrfRequired:true,revisionCheckRequired:true,secretProtectionRequired:true}}));}
 await writeFile(resolve(root,'docs/suivi-chantier/suivi-chantier.json'),JSON.stringify(tracker));
 const app=await startFrameworkServer({root,port:0,regenerate,getPullRequests,verifyPullRequest,iterationRoots:iterations?[root]:[]});
 t.after(async()=>{await app.close();assert.ok(root.startsWith(resolve(tmpdir())+sep));await rm(root,{recursive:true,force:true});});
 const html=await(await fetch(app.origin)).text(),token=html.match(/name="review-token" content="([a-f0-9]+)"/)[1];
 const headers={'X-Review-Token':token};
 const state=async()=>await(await fetch(app.origin+'/api/state',{headers})).json();
 const post=async(body,extra={})=>fetch(app.origin+'/api/action',{method:'POST',headers:{...headers,Origin:app.origin,'Content-Type':'application/json',...extra},body:JSON.stringify(body)});
 const action=async(name,options={})=>post({action:name,revision:(await state()).revision,phaseId:0,actor:'jpdandin',confirmed:true,comment:'Commentaire humain fictif',...options});
 return {app,root,headers,token,state,post,action};
}
test('Le serveur reste local, permet la lecture déclarée et refuse les routes privées',async t=>{
 const f=await fixture(t);assert.equal(f.app.server.address().address,'127.0.0.1');assert.equal((await f.state()).tracker.phases.length,4);
 assert.equal((await fetch(f.app.origin+'/documents/0/0')).status,200);
 for(const path of ['/framework/profil-projet.json','/.local/framework-runtime.lock','/api/deploy','/documents/0/99','/api/state?x=1'])assert.equal((await fetch(f.app.origin+path)).status,404);
 assert.equal((await fetch(f.app.origin+'/api/state')).status,403);
 assert.equal((await fetch(f.app.origin+'/api/state',{headers:{...f.headers,Origin:'https://evil.example'}})).status,403);
});
test('La liste GitHub est en lecture seule, locale et protégée par le jeton',async t=>{
 const requests=[];const f=await fixture(t,{getPullRequests:async options=>{requests.push(options);return {items:[{number:3,url:'https://github.com/jpdandin44/TC_Longages/pull/3',status:'merged'}],checkedAt:'2026-09-29T12:00:00Z',source:'github'};}});
 const before=(await f.state()).revision;
 assert.equal((await fetch(f.app.origin+'/api/pull-requests')).status,403);
 const result=await fetch(f.app.origin+'/api/pull-requests',{headers:f.headers});assert.equal(result.status,200);assert.equal((await result.json()).items[0].number,3);
 assert.equal((await fetch(f.app.origin+'/api/pull-requests?refresh=1',{headers:f.headers})).status,200);
 assert.deepEqual(requests,[{force:false},{force:true}]);
 assert.equal((await fetch(f.app.origin+'/api/pull-requests',{method:'POST',headers:{...f.headers,Origin:f.app.origin}})).status,405);
 assert.equal((await fetch(f.app.origin+'/api/pull-requests',{headers:{...f.headers,Origin:'https://evil.example'}})).status,403);
 assert.equal((await f.state()).revision,before);
});

test('Les lots raccordés utilisent les mêmes protections HTTP et conservent le suivi historique',async t=>{
 const f=await fixture(t,{iterations:true}),url=f.app.origin+'/api/iterations';
 assert.match(await(await fetch(f.app.origin)).text(),/Versions et changements/);
 assert.match(await(await fetch(f.app.origin+'/history')).text(),/review-token/);
 assert.equal((await fetch(url)).status,403);
 assert.equal((await fetch(url,{headers:{...f.headers,Origin:'https://evil.example'}})).status,403);
 assert.equal((await fetch(url,{method:'POST',headers:f.headers})).status,405);
 const state=await(await fetch(url,{headers:f.headers})).json();assert.equal(state.iterations[0].iterationId,'fixture-local');
 const before=JSON.parse(await readFile(resolve(f.root,'docs/suivi-chantier/suivi-chantier.json'),'utf8'));
 const body={revision:state.revision,iterationId:'fixture-local',phaseId:0,sourceSha:null,artifactSha256:null,actor:'jpdandin',confirmed:false,comment:'Recette fictive',checkedCriteria:[],action:'submit'};
 const post=extra=>fetch(f.app.origin+'/api/iteration-action',{method:'POST',headers:{...f.headers,Origin:f.app.origin,'Content-Type':'application/json'},body:JSON.stringify({...body,...extra})});
 assert.equal((await post({})).status,422);
 assert.equal((await post({confirmed:true,action:'deploy'})).status,422);
 assert.equal((await post({confirmed:true})).status,200);
 const after=JSON.parse(await readFile(resolve(f.root,'docs/suivi-chantier/suivi-chantier.json'),'utf8'));
 for(const key of ['phases','decisions','history','publication','release'])assert.deepEqual(after[key],before[key]);
 assert.equal(after.developmentIterations[0].reviewEvents.length,1);
});
test('Host, origine, jeton, type de contenu et méthode sont contrôlés avant mutation',async t=>{
 const f=await fixture(t),before=(await f.state()).revision;
 const host=await new Promise(resolve=>{const req=request(f.app.origin,{headers:{Host:'evil.example'}},res=>{res.resume();resolve(res.statusCode);});req.end();});assert.equal(host,403);
 for(const opts of [{method:'OPTIONS'},{method:'PUT'},{method:'POST',headers:f.headers},{method:'POST',headers:{...f.headers,Origin:'https://evil.example','Content-Type':'application/json'}},{method:'POST',headers:{Origin:f.app.origin,'Content-Type':'application/json'}}])assert.ok([403,405].includes((await fetch(f.app.origin+'/api/action',opts)).status));
 assert.equal((await f.post({}, {'Content-Type':'text/plain'})).status,415);
 assert.equal((await f.state()).revision,before);
});
test('Un clic externe ouvre seulement l’accueil, sans autoriser lecture API, iframe ou écriture externe',async t=>{
 const f=await fixture(t),before=(await f.state()).revision;
 const navigation={'Sec-Fetch-Site':'cross-site','Sec-Fetch-Mode':'navigate','Sec-Fetch-Dest':'document','Sec-Fetch-User':'?1'};
 const probe=(path='/',overrides={},method='GET')=>new Promise((resolve,reject)=>{
   const req=request(f.app.origin+path,{method,headers:{...navigation,...overrides}},res=>{let body='';res.setEncoding('utf8');res.on('data',chunk=>body+=chunk);res.on('end',()=>resolve({status:res.statusCode,headers:res.headers,body}));});req.on('error',reject);req.end();
 });
 const page=await probe();assert.equal(page.status,200);assert.match(page.headers['content-type'],/^text\/html/);assert.match(page.body,/name="review-token"/);assert.equal(page.headers['x-frame-options'],'DENY');
 assert.equal((await probe('/',{'Sec-Fetch-Site':'same-site'})).status,200);
 for(const path of ['/api/state','/api/action','/app.js','/app.css','/documents/0/0'])assert.equal((await probe(path,f.headers)).status,403);
 for(const headers of [{'Sec-Fetch-Mode':'cors'},{'Sec-Fetch-Mode':'no-cors'},{'Sec-Fetch-Dest':'iframe'},{'Sec-Fetch-User':'?0'},{'Sec-Fetch-User':''},{Host:'evil.example'},{Origin:'https://github.com'}])assert.equal((await probe('/',headers)).status,403);
 assert.equal((await probe('/',{},'POST')).status,403);
 assert.equal((await probe('/api/action',{...f.headers,Origin:f.app.origin,'Content-Type':'application/json'},'POST')).status,403);
 assert.equal((await f.state()).revision,before);
});
test('Notes, critères et suivi persistent, sauvegardent le précédent JSON et préservent les événements',async t=>{
 const f=await fixture(t);let r=await f.action('note',{comment:'Une remarque fictive <script>test</script>'});assert.equal(r.status,200);
 let s=await r.json();const id=s.tracker.reviewEvents[0].id;
 r=await f.action('follow_up',{commentId:id,status:'resolved',resolutionEvidence:'tests/fictifs',comment:'Correction vérifiée'});assert.equal(r.status,200);
 r=await f.action('criteria',{checkedCriteria:[0,2]});assert.equal(r.status,200);s=await r.json();assert.deepEqual(s.phaseViews[0].checkedCriteria,[0,2]);
 assert.equal(s.tracker.reviewEvents[0].comment,'Une remarque fictive <script>test</script>');assert.equal(s.tracker.reviewCommentResponses[0].status,'resolved');assert.equal(s.tracker.decisions.length,1);
 assert.equal((await readdir(resolve(f.root,'.local/framework-backups'))).length,3);
 const stored=JSON.parse(await readFile(resolve(f.root,'docs/suivi-chantier/suivi-chantier.json'),'utf8'));assert.equal(stored.history.length,3);
});
test('Révision périmée, documents modifiés, identité et confirmation absentes interdisent l’écriture',async t=>{
 const f=await fixture(t),s=await f.state();await f.action('note');
 assert.equal((await f.action('note',{revision:s.revision})).status,409);
 const s2=await f.state();await writeFile(resolve(f.root,'docs/suivi-chantier/00-phase.md'),'Nouveau document fictif modifié, révision différente');
 assert.equal((await f.action('note',{revision:s2.revision})).status,409);
 for(const params of [{actor:'intrus'},{confirmed:false},{comment:''},{action:'deploy'},{action:'criteria',checkedCriteria:[99]}])assert.equal((await f.action('note',params)).status,422);
});
test('Sans preuves la validation reste impossible ; une note ne valide rien',async t=>{
 const f=await fixture(t);for(const action of ['submit','approve','authorize_next','start'])assert.equal((await f.action(action)).status,422);
 const state=await f.state();assert.equal(state.tracker.phases[0].status,'in_progress');assert.ok(state.phaseViews[0].issues.some(s=>s.includes('commit')));
});
test('Valider passe directement la suivante en cours sans créer d’autorisation',async t=>{
 const f=await fixture(t,{qualified:true}),checkedCriteria=[0,1,2,3];
 await f.action('submit');const before=await f.state();
 assert.equal((await f.action('approve',{checkedCriteria})).status,200);
 const s=await f.state();assert.equal(s.tracker.phases[0].status,'validated');
 assert.equal(s.tracker.phases[1].status,'in_progress');assert.equal(s.tracker.currentPhase,1);
 assert.equal(s.tracker.phases[1].startEvidence,null);assert.ok(s.tracker.phases[1].progressionEvidence);
 assert.equal(s.tracker.decisions.length,before.tracker.decisions.length+1);
 assert.equal(s.tracker.decisions.at(-1).type,'validation');
 for(const action of ['start','authorize_next'])assert.equal((await f.action(action,{phaseId:1})).status,422);
 assert.deepEqual(s.tracker.publication,before.tracker.publication);assert.deepEqual(s.tracker.release,before.tracker.release);
 assert.deepEqual(validateInteractiveState(s.profile,s.tracker),[]);
});
test('Un seul clic humain valide les critères saisis sans sauvegarde séparée préalable',async t=>{
 const f=await fixture(t,{qualified:true}),checkedCriteria=[0,1,2,3];
 await f.action('submit');const before=await f.state(),backupCount=(await readdir(resolve(f.root,'.local/framework-backups'))).length;
 assert.deepEqual(before.phaseViews[0].checkedCriteria,[]);
 assert.equal(approvalInputState(before.tracker.phases[0],before.phaseViews[0],checkedCriteria).enabled,true);
 assert.equal((await f.action('approve',{checkedCriteria})).status,200);
 const after=await f.state(),decision=after.tracker.decisions.at(-1),criteria=after.tracker.reviewEvents.findLast(e=>e.type==='criteria_checked');
 assert.equal(after.phaseViews[0].validationCurrent,true);assert.deepEqual(after.phaseViews[0].checkedCriteria,checkedCriteria);
 assert.equal(criteria.type,'criteria_checked');assert.equal(criteria.recordedAt,decision.recordedAt);
 assert.equal(criteria.actor,decision.actor);assert.equal(criteria.documentDigest,decision.documentDigest);
 assert.equal((await readdir(resolve(f.root,'.local/framework-backups'))).length,backupCount+1);
 assert.equal(after.tracker.decisions.length,before.tracker.decisions.length+1);
 assert.equal(after.tracker.phases[1].startEvidence,null);assert.deepEqual(after.tracker.publication,before.tracker.publication);
});
test('La validation directe refuse des critères incomplets et conserve toutes les protections',async t=>{
 const f=await fixture(t,{qualified:true});await f.action('submit');const before=await f.state();
 for(const checkedCriteria of [undefined,[],[0,1,2],[0,1,2,2],[0,1,2,3,4],['0',1,2,3]]) {
   assert.equal((await f.action('approve',{checkedCriteria})).status,422);
   assert.deepEqual((await f.state()).tracker,before.tracker);
 }
 for(const input of [{confirmed:false},{actor:'intrus'},{comment:''},{revision:'ancienne'}]) {
   assert.equal((await f.action('approve',{checkedCriteria:[0,1,2,3],...input})).status,input.revision?409:422);
   assert.deepEqual((await f.state()).tracker,before.tracker);
 }
 assert.equal(approvalInputState(before.tracker.phases[0],before.phaseViews[0],[0,1,2]).enabled,false);
 assert.equal(approvalInputState(before.tracker.phases[0],{...before.phaseViews[0],actions:{approve:false}},[0,1,2,3]).enabled,false);
});
test('Plusieurs brouillons conservés se rechargent sans reconfirmer une décision humaine',async t=>{
 const f=await fixture(t),state=await f.state();
 await mkdir(resolve(f.root,'.local'),{recursive:true});
 const drafts=[0,1].map(phaseId=>({phaseId,actor:'jpdandin',comment:'Brouillon fictif '+phaseId,confirmed:true,criteria:state.tracker.phases[phaseId].exitCriteria.map(label=>({label,checked:true})),followups:[]}));
 const digest=hash({decisions:state.tracker.decisions,history:state.tracker.history,reviewEvents:state.tracker.reviewEvents,reviewCommentResponses:state.tracker.reviewCommentResponses});
 await writeFile(resolve(f.root,'.local/framework-review-draft.json'),JSON.stringify({phaseId:0,capturedAt:new Date().toISOString(),humanStateDigest:digest,drafts}));
 let recovered=await f.state();assert.equal(recovered.recoveredDraft.phaseId,0);assert.equal(recovered.recoveredDrafts.length,2);
 assert.ok(recovered.recoveredDrafts.every(d=>d.confirmed===false));assert.deepEqual(recovered.tracker,state.tracker);
 await f.action('note');recovered=await f.state();assert.deepEqual(recovered.recoveredDrafts,[]);
});
test('Une modification des documents retire la validité courante sans effacer la décision historique',async t=>{
 const f=await fixture(t,{qualified:true});await f.action('criteria',{checkedCriteria:[0,1,2,3]});await f.action('submit');await f.action('approve',{checkedCriteria:[0,1,2,3]});
 await writeFile(resolve(f.root,'docs/suivi-chantier/00-phase.md'),'Document fictif modifié après décision, qui doit invalider la portée de la preuve.');
 const s=await f.state();assert.equal(s.phaseViews[0].validationCurrent,false);assert.equal(s.tracker.decisions.length,2);assert.equal((await f.action('authorize_next')).status,422);
});

test('Une validation historique peut être reprise explicitement, sans devenir un accord actuel',async t=>{
 const f=await fixture(t,{qualified:true}),checkedCriteria=[0,1,2,3];
 await f.action('criteria',{checkedCriteria});await f.action('submit');await f.action('approve',{checkedCriteria});
 let s=await f.state();const oldDecision=structuredClone(s.tracker.decisions.at(-1));
 assert.equal(s.phaseViews[0].actions.request_changes,true);
 const target=resolve(f.root,'docs/suivi-chantier/suivi-chantier.json'),tracker=JSON.parse(await readFile(target,'utf8'));
 tracker.scope+=' Nouveau périmètre local fictif, à revoir.';await writeFile(target,JSON.stringify(tracker));
 s=await f.state();assert.equal(s.phaseViews[0].validationCurrent,false);assert.equal(s.phaseViews[0].actions.request_changes,true);
 assert.equal((await f.action('approve',{checkedCriteria})).status,422);
 assert.equal((await f.action('request_changes',{confirmed:false})).status,422);
 assert.equal((await f.action('request_changes')).status,200);s=await f.state();
 assert.equal(s.tracker.phases[0].status,'in_progress');assert.deepEqual(s.tracker.decisions.at(-1),oldDecision);
 assert.equal(s.tracker.reviewEvents.at(-1).previousValidationId,oldDecision.id);
 assert.equal((await f.action('submit')).status,200);assert.equal((await f.action('approve',{checkedCriteria})).status,200);
 s=await f.state();assert.equal(s.phaseViews[0].validationCurrent,true);assert.equal(s.tracker.decisions.length,3);
 assert.deepEqual(s.tracker.decisions[1],oldDecision);assert.notEqual(s.tracker.decisions[2].scope,oldDecision.scope);
});

test('Reprendre la revue conserve les contrôles de candidat et explique la dépendance bloquante',async t=>{
 const f=await fixture(t,{qualified:true}),checkedCriteria=[0,1,2,3];
 await f.action('criteria',{checkedCriteria});await f.action('submit');await f.action('approve',{checkedCriteria});
 const path=resolve(f.root,'docs/suivi-chantier/00-phase.md');await writeFile(path,'Un dossier de cadrage changé depuis la validation, sans nouvelle qualification technique.');
 let s=await f.state(),view=s.phaseViews[0];assert.match(reviewGuidance(s.tracker.phases[0],view,s.tracker.phases[1]).submit,/Revue/);
 assert.ok(s.phaseViews[1].issues.some(i=>i.includes('phase 0')&&i.includes('Revue')));
 await f.action('request_changes');assert.equal((await f.action('submit')).status,422);s=await f.state();
 assert.equal(s.phaseViews[0].actions.approve,false);assert.equal(s.phaseViews[0].validationCurrent,false);
 assert.match(reviewGuidance(s.tracker.phases[0],s.phaseViews[0],s.tracker.phases[1]).submit,/critères et documents actuels/);
});
test('Même sous ancienne exception les actions de démarrage et d’autorisation sont retirées',async t=>{
 const f=await fixture(t,{bootstrap:true}),before=(await f.state()).tracker;
 for(const phaseId of [0,1,2,3])for(const action of ['start','authorize_next'])assert.equal((await f.action(action,{phaseId})).status,422);
 assert.deepEqual((await f.state()).tracker,before);
 assert.deepEqual(Object.keys((await f.state()).phaseViews[0].actions),['submit','approve','request_changes']);
 assert.ok(!clientScript.includes('data-action="authorize_next"'));assert.ok(!clientScript.includes("['authorize_next'"));
 assert.ok(clientScript.includes("[['submit','Revue'],['approve','Valider'],['request_changes','Demander des corrections']]"));
});
test('Un seul serveur écrivain est admis et une erreur de génération ne rejoue pas la décision',async t=>{
 const f=await fixture(t,{regenerate:async()=>{throw new Error('Fixture');}});await assert.rejects(()=>startFrameworkServer({root:f.root,port:0}),/verrou local/);
 const r=await f.action('note');assert.equal(r.status,200);const s=await r.json();assert.match(s.viewWarning,/Enregistrement réussi/);assert.equal(s.tracker.reviewEvents.length,1);
});
test('Une preuve devenue négative interdit la progression et ne supprime pas la décision',async t=>{
 const f=await fixture(t,{qualified:true});await f.action('criteria',{checkedCriteria:[0,1,2,3]});await f.action('submit');await f.action('approve',{checkedCriteria:[0,1,2,3]});
 const file=resolve(f.root,'docs/suivi-chantier/suivi-chantier.json'),data=JSON.parse(await readFile(file,'utf8'));data.testRuns[0].status='failed';await writeFile(file,JSON.stringify(data));
 const s=await f.state();assert.equal(s.phaseViews[0].validationCurrent,false);assert.equal(s.tracker.decisions.length,2);assert.equal((await f.action('authorize_next')).status,422);
});
test('Une exception d’installation sans sa vraie autorisation ou étendue arbitrairement ne débloque rien',async t=>{
 const f=await fixture(t,{bootstrap:true}),file=resolve(f.root,'framework/installation.json'),initial=JSON.parse(await readFile(file,'utf8'));
 for(const change of [{authorization:{...initial.authorization,id:'fake'}},{authorization:{...initial.authorization,actor:'intrus'}},{authorization:{...initial.authorization,scope:'Autre portée'}},{allowedPhaseIds:[0,1,2,3,4]},{expiresAt:'2099-01-01T00:00:00Z'}]){await writeFile(file,JSON.stringify({...initial,...change}));assert.equal((await f.action('start',{phaseId:1})).status,422);}
});
test('Le contrat commun refuse les preuves incomplètes et les accords de publication ajoutés',async t=>{
 const f=await fixture(t,{qualified:true}),s=await f.state();
 for(const key of ['recordedAt','actor','artifactRef','actualResult']){const data=structuredClone(s.tracker);delete data.evidence[0][key];assert.ok(validateInteractiveState(s.profile,data).some(e=>e.includes('Preuve')));}
 s.tracker.decisions.push({id:'remote',type:'public_opening',status:'approved'});assert.ok(validateInteractiveState(s.profile,s.tracker).some(e=>e.includes('distante')));
});
test('Les aides distinguent saisie, critères enregistrés et décision de validation sans assouplir les actions',async t=>{
 const f=await fixture(t),s=await f.state(),p=s.tracker.phases[0],view=s.phaseViews[0],before=structuredClone(view.actions);
 const help=reviewGuidance(p,view,s.tracker.phases[1]);assert.match(help.approve,/d’abord être remis/);assert.deepEqual(view.actions,before);
 const review=reviewGuidance({...p,status:'awaiting_review'},{...view,issues:[],checkedCriteria:[0]},s.tracker.phases[1]);assert.match(review.approve,/1 sur 4/);
 assert.doesNotThrow(()=>new Script(clientScript));
});
test('L’enregistrement des critères conserve le commentaire et la confirmation doit être renouvelée',()=>{
 const draft={comment:'Ma revue personnelle',actor:'jpdandin',checked:[0,2],confirmed:true,followups:[{id:'C1',comment:'Ma réponse',evidence:'Recette locale'}]};
 const afterCriteria=retainedReviewDraft(draft,'criteria');assert.equal(afterCriteria.comment,draft.comment);assert.deepEqual(afterCriteria.checked,[0,2]);assert.equal(afterCriteria.confirmed,false);
 const afterNote=retainedReviewDraft(draft,'note');assert.equal(afterNote.comment,'');assert.deepEqual(afterNote.checked,[0,2]);assert.equal(afterNote.followups[0].comment,'Ma réponse');
 const afterFollowup=retainedReviewDraft(draft,'follow_up',{commentId:'C1'});assert.equal(afterFollowup.followups[0].comment,'');assert.equal(afterFollowup.comment,draft.comment);assert.equal(draft.confirmed,true);
});
test('Les liens cliquables de revue visent exclusivement une PR ou un commit du dépôt du club',()=>{
 for(const path of ['pull/23','commit/'+'a'.repeat(40)])assert.equal(safeProjectUrl('https://github.com/jpdandin44/TC_Longages/'+path),'https://github.com/jpdandin44/TC_Longages/'+path);
 for(const value of ['javascript:alert(1)','https://github.com/evil/project/pull/1','https://github.com/jpdandin44/TC_Longages/pull/1?x=1','https://user:password@github.com/jpdandin44/TC_Longages/pull/1'])assert.equal(safeProjectUrl(value),null);
});

test('Le regroupement conserve le brouillon et refuse d’en faire une décision ou une confirmation',async t=>{
 const f=await fixture(t),s=await f.state(),phase=s.tracker.phases[1],before=structuredClone(s.tracker);
 const draft={phaseId:1,actor:'jpdandin',comment:'Ma saisie conservée',confirmed:true,criteria:phase.exitCriteria.slice(0,4).map(label=>({label,checked:true})),followups:[]};
 const recovered=recoverReviewDraft(draft,s.tracker,s.profile);
 assert.deepEqual(recovered.checked,[0,1,2,3]);assert.equal(recovered.confirmed,false);assert.equal(recovered.comment,draft.comment);assert.deepEqual(s.tracker,before);
 assert.equal(recoverReviewDraft({...draft,criteria:[{label:'Autre périmètre',checked:true}]},s.tracker,s.profile),null);
 assert.equal(recoverReviewDraft({...draft,actor:'intrus'},s.tracker,s.profile),null);
});

test('Les phases de préproduction et production restent sans action distante, même sous exception locale',async t=>{
 const f=await fixture(t,{bootstrap:true}),before=(await f.state()).tracker;
 for(const phaseId of [2,3]) for(const action of ['start','submit','approve','authorize_next','request_changes']) assert.equal((await f.action(action,{phaseId,checkedCriteria:[0,1,2,3]})).status,422);
 assert.deepEqual((await f.state()).tracker,before);
 const observed=structuredClone(before);observed.phases[2].status='blocked';
 assert.ok(validateInteractiveState((await f.state()).profile,observed).some(e=>e.includes('observations distinctes')));
 observed.phases[2].observedExecution={source:'hosting_observation',environment:'preproduction',status:'blocked',recordedAt:new Date().toISOString(),evidence:'Preuve fictive',authorizationRef:'Accord fictif'};
 assert.deepEqual(validateInteractiveState((await f.state()).profile,observed),[]);
 observed.phases[2].status='validated';assert.ok(validateInteractiveState((await f.state()).profile,observed).length);
});

test('Une PR inconnue, ouverte, en brouillon ou fermée sans fusion bloque la validation côté serveur',async t=>{
 let status='unknown';const f=await fixture(t,{qualified:true,verifyPullRequest:async ({url})=>({passed:false,status,url,issues:['PR candidate '+status]})});
 assert.equal((await f.action('submit')).status,200);
 for(status of ['unknown','draft','open','closed','unavailable']){
   const before=await f.state();assert.equal(before.phaseViews[0].actions.approve,false);
   assert.equal((await f.action('approve',{checkedCriteria:[0,1,2,3],pullRequestStatus:'merged'})).status,422);
   assert.deepEqual((await f.state()).tracker,before.tracker);
 }
 assert.equal((await f.action('criteria',{checkedCriteria:[0,1,2,3]})).status,200);
});
test('Une approbation revérifie GitHub sans faire confiance au cache affiché et conserve le dossier',async t=>{
 const calls=[];let fail=false;const f=await fixture(t,{qualified:true,verifyPullRequest:async ({url,sourceCommit,force})=>{
   calls.push(force);return fail&&force?{passed:false,status:'unavailable',url,issues:['GitHub indisponible']}:{passed:true,status:'merged',url,headCommit:sourceCommit,issues:[],checkedAt:new Date().toISOString()};
 }});
 await f.action('submit');const before=await f.state();assert.equal(before.phaseViews[0].actions.approve,true);
 fail=true;assert.equal((await f.action('approve',{checkedCriteria:[0,1,2,3]})).status,422);
 assert.ok(calls.includes(true));assert.deepEqual((await f.state()).tracker,before.tracker);
});
test('Une ancienne validation sans preuve de fusion reste historique sans être effacée',async t=>{
 const f=await fixture(t,{qualified:true});await f.action('submit');await f.action('approve',{checkedCriteria:[0,1,2,3]});
 const path=resolve(f.root,'docs/suivi-chantier/suivi-chantier.json'),tracker=JSON.parse(await readFile(path,'utf8'));
 delete tracker.decisions.at(-1).pullRequestVerification;await writeFile(path,JSON.stringify(tracker));
 const before=await f.state();assert.equal(before.phaseViews[0].validationCurrent,false);assert.equal(before.phaseViews[0].actions.submit,true);
 assert.equal((await f.action('submit')).status,200);const after=await f.state();
 assert.deepEqual(after.tracker.decisions,before.tracker.decisions);assert.equal(after.tracker.phases[0].status,'awaiting_review');
 assert.equal(after.tracker.reviewEvents.at(-1).previousValidationId,before.tracker.phases[0].validationEvidence.decisionId);
});
