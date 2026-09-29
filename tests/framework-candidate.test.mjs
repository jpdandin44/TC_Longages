import test from 'node:test';
import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
import {mkdtemp,mkdir,writeFile,readFile,rm} from 'node:fs/promises';
import {tmpdir} from 'node:os';
import {resolve,sep,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {createHash} from 'node:crypto';
import {createCandidateManifest,verifyCandidateManifest,CANDIDATE_EXCLUSIONS,CANDIDATE_MANIFEST} from '../scripts/framework-candidate.mjs';
import {loadReviewState,hash} from '../scripts/framework-store.mjs';

const project=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const git=(root,...args)=>execFileSync('git',args,{cwd:root,encoding:'utf8',stdio:['pipe','pipe','pipe'],windowsHide:true}).trim();
const put=async(root,path,value)=>{await mkdir(dirname(resolve(root,path)),{recursive:true});await writeFile(resolve(root,path),value);};
const commit=root=>{git(root,'add','.');git(root,'commit','-qm','Fixture technique isolée');return git(root,'rev-parse','HEAD');};
async function fixture(t) {
  const root=await mkdtemp(resolve(tmpdir(),'tcl-candidate-'));
  t.after(async()=>{assert.ok(root.startsWith(resolve(tmpdir())+sep));await rm(root,{recursive:true,force:true});});
  git(root,'init','-q');git(root,'config','user.name','Recette locale');git(root,'config','user.email','fixture@example.invalid');git(root,'config','core.autocrlf','false');
  await put(root,'.gitattributes','*.mjs text eol=lf\n');await put(root,'.gitignore','.local/\n');await put(root,'source.mjs','export const value=1;\n');
  return root;
}

test('Le manifeste décrit les vrais blobs du commit et ne valide aucune phase',async t=>{
  const root=await fixture(t),sha=commit(root),manifest=await createCandidateManifest(root,sha);
  assert.deepEqual(manifest,await createCandidateManifest(root,sha));
  assert.equal(manifest.files.find(f=>f.path==='source.mjs').sha256,createHash('sha256').update('export const value=1;\n').digest('hex'));
  assert.deepEqual(manifest.excludedPaths,CANDIDATE_EXCLUSIONS);
  assert.equal((await verifyCandidateManifest(root,manifest,{sourceCommit:sha,artifactDigest:manifest.artifactDigest})).passed,true);
  assert.equal((await verifyCandidateManifest(root,manifest,{sourceCommit:'0'.repeat(40)})).passed,false);
  assert.equal(Object.hasOwn(manifest,'decisions'),false);
  const forged=structuredClone(manifest);forged.files=[];
  assert.equal((await verifyCandidateManifest(root,forged)).passed,false);
});

test('Modifications réelles, suppressions, ajouts indexés et non indexés invalident les preuves',async t=>{
  const root=await fixture(t),manifest=await createCandidateManifest(root,commit(root));
  git(root,'update-index','--assume-unchanged','source.mjs');
  await put(root,'source.mjs','export const value=2;\n');
  let result=await verifyCandidateManifest(root,manifest);assert.equal(result.passed,false);assert.ok(result.issues.some(x=>x.includes('contenu réel')));
  git(root,'update-index','--no-assume-unchanged','source.mjs');git(root,'checkout','--','source.mjs');
  await put(root,'source.mjs','export const value=2;\n');git(root,'add','source.mjs');await put(root,'source.mjs','export const value=1;\n');
  result=await verifyCandidateManifest(root,manifest);assert.equal(result.passed,false);assert.ok(result.issues.some(x=>x.includes('indexées')));git(root,'reset','-q','HEAD','--','source.mjs');
  await rm(resolve(root,'source.mjs'));assert.equal((await verifyCandidateManifest(root,manifest)).passed,false);git(root,'checkout','--','source.mjs');
  await put(root,'nouveau.mjs','export const extra=true;\n');assert.equal((await verifyCandidateManifest(root,manifest)).passed,false);
  git(root,'add','nouveau.mjs');result=await verifyCandidateManifest(root,manifest);assert.equal(result.passed,false);assert.ok(result.issues.some(x=>x.includes('nouveau.mjs')));
});

test('Normalisation CRLF et commit documentaire limité aux cinq exclusions conservent le candidat',async t=>{
  const root=await fixture(t),sha=commit(root),manifest=await createCandidateManifest(root,sha);
  await put(root,'source.mjs','export const value=1;\r\n');
  assert.equal((await verifyCandidateManifest(root,manifest)).passed,true);
  for(const path of CANDIDATE_EXCLUSIONS) await put(root,path,path===CANDIDATE_MANIFEST?JSON.stringify(manifest):'Métadonnées de revue uniquement\n');
  commit(root);
  assert.equal((await verifyCandidateManifest(root,manifest)).passed,true);
  await put(root,'data/framework-revue-verification.json','{"newReceipt":true}\n');
  assert.equal((await verifyCandidateManifest(root,manifest)).passed,true);
  await put(root,'source.mjs','export const value=3;\n');commit(root);await put(root,'source.mjs','export const value=1;\n');
  const result=await verifyCandidateManifest(root,manifest);assert.equal(result.passed,false);assert.ok(result.issues.some(x=>x.includes('dans Git')));
});

test('La politique Git bloque la revue sans manifeste puis invalide sa révision si le code change',async t=>{
  const root=await fixture(t);
  const profile=JSON.parse(await readFile(resolve(project,'framework/profil-projet.json'),'utf8'));
  const tracker=JSON.parse(await readFile(resolve(project,'docs/suivi-chantier/suivi-chantier.json'),'utf8'));
  tracker.reviewEvents=[];tracker.reviewCommentResponses=[];tracker.history=[];tracker.evidence=[];tracker.testRuns=[];
  tracker.decisions=tracker.decisions.filter(d=>d.id===profile.project.initialAuthorizationRef);
  for(const p of tracker.phases) {
    p.status=p.id===0?'in_progress':'not_started';p.blockers=[];p.reviewContext=null;p.validationEvidence=null;p.validatedOn=null;p.deliveredOn=null;
    if(p.id>0){p.startEvidence=null;p.startedOn=null;p.authorizedOn=null;}
    for(const d of p.deliverables) await put(root,'docs/suivi-chantier/'+d.path,'# Recette fictive\n\nDocument suffisamment long pour simuler un dossier local disponible, sans validation humaine.\n');
  }
  await put(root,'framework/profil-projet.json',JSON.stringify(profile));
  await put(root,'framework/review-policy.json',JSON.stringify({project:'tclongages',candidateBindingRequired:true}));
  await put(root,'docs/suivi-chantier/suivi-chantier.json',JSON.stringify(tracker));
  const sha=commit(root),manifest=await createCandidateManifest(root,sha);
  let state=await loadReviewState(root);assert.equal(state.phaseViews[0].actions.submit,false);assert.ok(state.phaseViews[0].issues.some(x=>x.includes('manifeste')));
  await put(root,CANDIDATE_MANIFEST,JSON.stringify(manifest));
  const p=tracker.phases[0];p.pullRequest='https://github.com/jpdandin44/TC_Longages/pull/1';
  p.reviewContext={sourceCommit:sha,artifactDigest:manifest.artifactDigest,candidateManifest:CANDIDATE_MANIFEST,criteriaDigest:hash(p.exitCriteria),documentDigest:hash(state.documents.filter(d=>d.phaseId===0).map(d=>({path:d.path,digest:d.digest}))),evidenceRefs:['E'],testRunRefs:['T']};
  const proof={phaseId:0,status:'passed',environment:'local',sourceCommit:sha,artifactDigest:manifest.artifactDigest,recordedAt:new Date().toISOString(),actor:'Recette automatique fictive',artifactRef:'data/framework-revue-verification.json',actualResult:'Contrôle fictif isolé'};
  tracker.evidence=[{...proof,id:'E'}];tracker.testRuns=[{...proof,id:'T'}];
  await put(root,'docs/suivi-chantier/suivi-chantier.json',JSON.stringify(tracker));
  state=await loadReviewState(root);assert.equal(state.phaseViews[0].actions.submit,true);assert.equal(state.phaseViews[0].actions.approve,false);assert.equal(state.tracker.decisions.length,1);
  await put(root,'source.mjs','export const value=9;\n');
  const changed=await loadReviewState(root);assert.notEqual(changed.revision,state.revision);assert.equal(changed.phaseViews[0].actions.submit,false);assert.equal(changed.phaseViews[0].validationCurrent,false);assert.equal(changed.tracker.decisions.length,1);
});
