import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve} from 'node:path';
import {root, boundedPath, readState, renderViews, validateLocalState} from '../scripts/framework.mjs';

test('La génération accepte les décisions du store sans modifier le suivi ni le profil', async()=>{
  const path=resolve(root,'docs/suivi-chantier/suivi-chantier.json');
  const profilePath=resolve(root,'framework/profil-projet.json');
  const before=await readFile(path,'utf8'), profileBefore=await readFile(profilePath,'utf8'), state=await readState();
  assert.deepEqual(validateLocalState(state.profile,state.tracker),[]);
  assert.deepEqual(renderViews(state),renderViews(state));
  assert.equal(await readFile(path,'utf8'),before);
  assert.equal(await readFile(profilePath,'utf8'),profileBefore);
});
test('Une fausse validation et un démarrage sans accord sont refusés',async()=>{
  const state=await readState();
  state.tracker.phases[1].status='validated';
  state.tracker.phases[1].startedOn='2026-09-29';
  assert.ok(validateLocalState(state.profile,state.tracker).length>0);
});
test('La vue ne peut présenter un accord de publication ajouté comme acquis',async()=>{
  const state=await readState();
  state.tracker.decisions.push({id:'fake',status:'approved',type:'production_deployment'});
  state.tracker.publication.publicOpeningExecuted=true;
  assert.ok(validateLocalState(state.profile,state.tracker).length>=2);
});
test('Les chemins absolus, traversal Windows et URL sortantes sont refusés',()=>{
  for(const path of ['../secret.md','C:\\secret.md','https://example.org/a','a/../b.md','/secret.md','a\\b.md']) assert.throws(()=>boundedPath(root,path));
});
test('Les textes du suivi sont échappés dans la vue HTML',async()=>{
  const state=await readState();
  state.tracker.phases[0].objective='<img src=x onerror=alert(1)>';
  const html=renderViews(state)['tableau-de-bord.html'];
  assert.ok(html.includes('&lt;img'));
  assert.doesNotMatch(html,/<img|<script|<form/);
  state.profile.openItems[0].topic='<img src=x>[clic](javascript:alert(1))';
  const markdown=renderViews(state)['tableau-de-bord.md'];
  assert.doesNotMatch(markdown,/<img|\[clic\]\(javascript:/);
});
test('Le lien du dépôt ne peut devenir un protocole exécutable ou une autre destination',async()=>{
  for(const url of ['javascript:alert(1)','https://example.org','https://user:pass@github.com/jpdandin44/TC_Longages']) {
    const state=await readState(); state.profile.project.repository=url;
    assert.ok(validateLocalState(state.profile,state.tracker).length>0);
  }
});
test('Les contrôles locaux sans Git ne deviennent pas des preuves qualifiées',async()=>{
  const state=await readState();
  state.tracker.testRuns.push({id:'fake-run',status:'passed',sourceCommit:null});
  assert.ok(validateLocalState(state.profile,state.tracker).some(e=>e.includes('commit')));
});
test('L’autorisation d’installation et le choix Drupal ne deviennent pas une validation de phase',async()=>{
  const state=await readState();
  const decisions=state.tracker.decisions;
  assert.equal(decisions.find(d=>d.id==='TCL-D02')?.type,'installation_authorization');
  assert.equal(decisions.find(d=>d.id==='TCL-D03')?.type,'architecture');
  const phasesBefore=JSON.stringify(state.tracker.phases);
  assert.deepEqual(validateLocalState(state.profile,state.tracker),[]);
  renderViews(state);
  assert.equal(JSON.stringify(state.tracker.phases),phasesBefore);
});
test('Les vues proposent les deux services locaux et utilisent la dernière observation du dépôt',async()=>{
  const state=await readState();
  state.tracker.observations.push({kind:'repository',statement:'Connecteur accessible ; dépôt privé, branche main.',limits:'CLI encore non qualifiée.'});
  const views=renderViews(state);
  for(const contents of Object.values(views)) {
    assert.match(contents,/http:\/\/127\.0\.0\.1:4181\//);
    assert.match(contents,/http:\/\/127\.0\.0\.1:4182\//);
    assert.match(contents,/Connecteur accessible/);
    assert.doesNotMatch(contents,/L'accès GitHub de ce poste reste à rétablir|Le moteur interactif et les adaptateurs/);
  }
});
test('Une validation périmée reste historique et la fermeture d’installation est visible',async()=>{
  const state=await readState();
  state.tracker.currentPhase=1;
  state.tracker.phases[0].status='validated';
  state.phaseViews[0].validationCurrent=false;
  state.installation={...state.installation,status:'secured'};
  const views=renderViews(state);
  for(const contents of Object.values(views)) {
    assert.match(contents,/Phase actuelle : 1/);
    assert.match(contents,/1 validation/);
    assert.match(contents,/Validée historiquement/);
    assert.match(contents,/Installation clôturée/);
    assert.doesNotMatch(contents,/Aucune phase validée/);
  }
});
