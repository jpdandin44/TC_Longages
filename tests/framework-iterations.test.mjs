import test from 'node:test';
import assert from 'node:assert/strict';
import {mkdtemp,mkdir,writeFile,readFile,rm} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {createIterationStore,ITERATION_PHASES} from '../scripts/framework-iterations.mjs';
import {iterationsClient,renderIterations} from '../scripts/framework-iterations-ui.mjs';

const base=resolve('.local');
async function fixture(t,{merged=true}={}){
  await mkdir(base,{recursive:true});const root=await mkdtemp(resolve(base,'iteration-test-'));
  t.after(async()=>{assert.equal(dirname(resolve(root)),base);await rm(root,{recursive:true,force:true});});
  await mkdir(resolve(root,'docs/suivi-chantier'),{recursive:true});
  const file=resolve(root,'docs/suivi-chantier/suivi-chantier.json'),sourceSha='a'.repeat(40),artifactSha256='b'.repeat(64),head='c'.repeat(40);
  const candidate={sourceSha,artifactSha256,gitSourceDigest:'d'.repeat(64)};
  const checks=['local','ci','documentation','preproduction'].map(kind=>({kind,status:'passed',sourceSha,artifactSha256,target:'https://preprod.tclongages.fr/',evidence:'fixture',observedAt:'2026-10-06T10:00:00Z'}));
  const historical={phases:ITERATION_PHASES.map((title,id)=>({id,title,exitCriteria:['Critère obligatoire '+id]})),decisions:[{id:'historical'}],history:[{id:'historical'}]};
  const tracker={project:'tclongages',...historical,developmentIterations:[{iterationId:'fixture-v1',owner:'jpdandin',stage:'preproduction',candidate,checks,blockers:[],targets:{preproduction:{id:'https://preprod.tclongages.fr/'}},approvals:[{scope:'preproduction',status:'approved'}],github:{prUrl:'https://github.com/jpdandin44/TC_Longages/pull/15'}}]};
  await writeFile(file,JSON.stringify(tracker));let forceCount=0;
  const store=await createIterationStore({roots:[root],verifySource:async()=>({passed:true}),verifyPullRequest:async({force})=>{if(force)forceCount++;return {passed:merged,headCommit:head,status:merged?'merged':'open',issues:merged?[]:['PR ouverte à examiner.']};}});
  async function action(action,phaseId=0,extra={}){
    const state=await store.read();return store.mutate({revision:state.revision,iterationId:'fixture-v1',phaseId,sourceSha,artifactSha256,actor:'jpdandin',confirmed:true,comment:'Recette fictive',checkedCriteria:[0],action,...extra});
  }
  return {root,file,store,tracker,historical,action,forces:()=>forceCount};
}
test('Le lot, les quatre phases et les critères sont exposés sans validation créée',async t=>{
  const f=await fixture(t),s=await f.store.read();assert.deepEqual(s.iterations[0].phaseLabels,ITERATION_PHASES);assert.deepEqual(s.iterations[0].phaseViews.map(p=>p.criteria),f.historical.phases.map(p=>p.exitCriteria));assert.ok(s.iterations[0].phaseViews.every(p=>p.status==='not_reviewed'));assert.deepEqual(JSON.parse(await readFile(f.file)).decisions,f.historical.decisions);
});
test('Critères, commentaire, confirmation et candidat exact sont imposés côté serveur',async t=>{
  const f=await fixture(t);await f.action('submit');const before=await readFile(f.file,'utf8');
  for(const extra of [{confirmed:false},{comment:' '},{checkedCriteria:[]},{sourceSha:'e'.repeat(40)},{artifactSha256:'e'.repeat(64)},{actor:'intrus'}])await assert.rejects(f.action('approve',0,extra));
  assert.equal(await readFile(f.file,'utf8'),before);
});
test('La PR ouverte bloque la validation mais permet revue et demande de corrections',async t=>{
  const f=await fixture(t,{merged:false});await f.action('submit');await assert.rejects(f.action('approve'),/PR ouverte/);await f.action('request_changes');assert.equal((await f.store.read()).iterations[0].phaseViews[0].status,'in_progress');assert.ok(f.forces()>0);
});
test('Une validation personnelle ne modifie ni décisions historiques ni accords de déploiement',async t=>{
  const f=await fixture(t);for(const phase of [0,1,2]){await f.action('submit',phase);await f.action('approve',phase);}
  const saved=JSON.parse(await readFile(f.file));for(const key of ['phases','decisions','history'])assert.deepEqual(saved[key],f.historical[key]);assert.deepEqual(saved.developmentIterations[0].approvals,f.tracker.developmentIterations[0].approvals);assert.ok(saved.developmentIterations[0].reviewEvents.every(e=>e.scope==='iteration_review_only_no_remote_authorization'));assert.equal((await f.store.read()).iterations[0].phaseViews[2].status,'validated');
});
test('La modification du candidat conserve et périme les validations anciennes',async t=>{
  const f=await fixture(t);await f.action('submit');await f.action('approve');const saved=JSON.parse(await readFile(f.file));saved.developmentIterations[0].candidate.sourceSha='e'.repeat(40);await writeFile(f.file,JSON.stringify(saved));const s=await f.store.read();assert.equal(s.iterations[0].reviewEvents.length,2);assert.equal(s.iterations[0].phaseViews[0].status,'not_reviewed');
});
test('Une correction de phase précédente invalide la progression du même candidat',async t=>{
  const f=await fixture(t);for(const phase of [0,1]){await f.action('submit',phase);await f.action('approve',phase);}await f.action('request_changes',0);const s=await f.store.read();assert.notEqual(s.iterations[0].phaseViews[1].status,'validated');await f.action('submit',2);await assert.rejects(f.action('approve',2),/phase précédente/);
});
test('Une écriture concurrente, une action distante et une recette manquante sont refusées',async t=>{
  const f=await fixture(t);let s=await f.store.read();const saved=JSON.parse(await readFile(f.file));saved.history.push({id:'new-human-note'});await writeFile(f.file,JSON.stringify(saved));await assert.rejects(f.store.mutate({revision:s.revision,iterationId:'fixture-v1',action:'submit',confirmed:true,comment:'Test concurrent'}),/dossier a changé/);await assert.rejects(f.action('deploy'),/inconnue/);
  for(const phase of [0,1]){await f.action('submit',phase);await f.action('approve',phase);}const changed=JSON.parse(await readFile(f.file));changed.developmentIterations[0].checks=changed.developmentIterations[0].checks.filter(c=>c.kind!=='preproduction');await writeFile(f.file,JSON.stringify(changed));await f.action('submit',2);await assert.rejects(f.action('approve',2),/recette technique/);
});

test('La dernière phase exige accord, livraison et contrôles réels sans les créer',async t=>{
 const f=await fixture(t);for(const phase of [0,1,2]){await f.action('submit',phase);await f.action('approve',phase);}
 const data=JSON.parse(await readFile(f.file));const i=data.developmentIterations[0];i.stage='production';await writeFile(f.file,JSON.stringify(data));
 await f.action('submit',3);await assert.rejects(f.action('approve',3),/accord explicite/);
 const actual=JSON.parse(await readFile(f.file)),version=actual.developmentIterations[0],target='https://tclongages.fr/',evidence={sourceSha:version.candidate.sourceSha,artifactSha256:version.candidate.artifactSha256,target,evidence:'fixture-only',observedAt:'2026-10-06T10:01:00Z'};
 version.targets.production={id:target};version.approvals.push({...evidence,scope:'production',status:'approved'});version.delivery={...evidence,status:'delivered'};version.checks.push({...evidence,kind:'production',status:'passed'});await writeFile(f.file,JSON.stringify(actual));
 const expected=JSON.parse(await readFile(f.file));await f.action('approve',3);const saved=JSON.parse(await readFile(f.file));
 assert.equal((await f.store.read()).iterations[0].phaseViews[3].status,'validated');assert.deepEqual(saved.developmentIterations[0].approvals,expected.developmentIterations[0].approvals);assert.deepEqual(saved.developmentIterations[0].delivery,expected.developmentIterations[0].delivery);
});
test('L’interface conserve les brouillons, trois décisions et liens de recette ; aucune action de publication',()=>{
  assert.match(renderIterations('safe'),/Versions et changements du site/);assert.match(iterationsClient,/confirmed:false/);assert.match(iterationsClient,/Demander des corrections/);assert.match(iterationsClient,/Tester le site/);assert.doesNotMatch(iterationsClient,/data-action="(?:deploy|start|authorize)"/);new Function(iterationsClient);
});
