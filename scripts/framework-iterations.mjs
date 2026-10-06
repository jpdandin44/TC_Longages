import {readFile, writeFile, rename, realpath, lstat, mkdir, unlink} from 'node:fs/promises';
import {resolve, sep} from 'node:path';
import {randomUUID} from 'node:crypto';
import {pathToFileURL} from 'node:url';
import {hash, ReviewError} from './framework-store.mjs';
import {readCandidateVerification, CANDIDATE_EXCLUSIONS} from './framework-candidate.mjs';

export const ITERATION_PHASES=['Cadrage','Développement local','Préproduction','Mise en production'];
const need=(ok,message,status=422)=>{if(!ok)throw new ReviewError(message,status);};
const candidateKey=i=>hash({candidate:i.candidate||null,criteria:i.reviewCriteria||null});
const exact=(e,i)=>e?.sourceSha===i.candidate?.sourceSha&&e?.artifactSha256===i.candidate?.artifactSha256;
const knownStage=i=>Math.max(0,['cadrage','developpement_local','preproduction','production'].indexOf(i.stage));
const dated=e=>typeof(e?.observedAt||e?.recordedAt)==='string'&&Number.isFinite(Date.parse(e.observedAt||e.recordedAt))&&!!e.evidence;
const currentEvents=i=>(i.reviewEvents||[]).filter(e=>e.candidateKey===candidateKey(i));
export function reviewIssues(i,phase,pr,source) {
  const errors=[];
  if(!/^[a-f0-9]{40}$/.test(i.candidate?.sourceSha||'')||!/^[a-f0-9]{64}$/.test(i.candidate?.artifactSha256||'')) errors.push('Le candidat doit être figé avant validation.');
  if(!source?.passed) errors.push('Les sources de cette version doivent être vérifiées.');
  if(!pr?.passed) errors.push(...(pr?.issues||['La PR candidate doit être vérifiée et fusionnée avant validation.']));
  for(const kind of ['local','ci','documentation']) if(!i.checks?.some(e=>e.kind===kind&&e.status==='passed'&&exact(e,i)&&dated(e))) errors.push('Contrôle actuel manquant : '+kind+'.');
  if((i.blockers||[]).length) errors.push(...i.blockers);
  const events=currentEvents(i);
  for(let prior=0;prior<phase;prior++){
    const previous=events.filter(e=>e.phaseId===prior).at(-1),dependency=events.filter(e=>e.phaseId===prior-1).at(-1);
    if(!(previous?.action==='approve'&&previous.acceptedHead===pr?.headCommit&&(prior===0||previous.previousValidationRef===dependency?.id))) errors.push('Validez d’abord la phase précédente de ce même candidat (phase '+prior+').');
  }
  const current=events.filter(e=>e.phaseId===phase).at(-1),previous=events.filter(e=>e.phaseId===phase-1).at(-1);
  if(phase>0&&current?.action==='approve'&&current.previousValidationRef!==previous?.id)errors.push('La validation de la phase précédente a changé ; reprenez la revue.');
  if(phase>=2&&!i.checks?.some(e=>e.kind==='preproduction'&&e.status==='passed'&&exact(e,i)&&e.target===i.targets?.preproduction?.id&&dated(e))) errors.push('La recette technique du candidat en préproduction reste à terminer.');
  if(phase===3){
    const target=i.targets?.production?.id;
    if(!target||!i.approvals?.some(e=>e.scope==='production'&&e.status==='approved'&&exact(e,i)&&e.target===target&&dated(e)))errors.push('L’accord explicite de production sur ce candidat doit être consigné séparément.');
    if(!target||i.delivery?.status!=='delivered'||!exact(i.delivery,i)||i.delivery.target!==target||!dated(i.delivery))errors.push('La livraison effective en production doit être attestée avant validation de cette phase.');
    if(!i.checks?.some(e=>e.kind==='production'&&e.status==='passed'&&exact(e,i)&&e.target===target&&dated(e)))errors.push('Les contrôles après livraison en production restent à terminer.');
  }
  return [...new Set(errors)];
}
export async function createIterationStore({roots=[],verifyPullRequest,verifySource}) {
  const sources=[];
  for(const input of roots){
    const root=await realpath(input),file=resolve(root,'docs/suivi-chantier/suivi-chantier.json');
    need(!(await lstat(file)).isSymbolicLink()&&(await realpath(file)).startsWith(root+sep),'Source du cockpit redirigée.');
    sources.push({root,file});
  }
  let writing=false;
  const nativeReaders=new Map();
  async function reader(source){
    if(verifySource)return {verify:verifySource,exclusions:CANDIDATE_EXCLUSIONS};
    if(!nativeReaders.has(source.root)){
      const file=resolve(source.root,'scripts/framework-candidate.mjs');
      need((await lstat(file)).isFile()&&!(await lstat(file)).isSymbolicLink()&&(await realpath(file)).startsWith(source.root+sep),'Vérificateur natif hors checkout.');
      const module=await import(pathToFileURL(file).href);
      need(typeof module.readCandidateVerification==='function'&&Array.isArray(module.CANDIDATE_EXCLUSIONS),'Vérificateur natif incompatible.');
      nativeReaders.set(source.root,{verify:module.readCandidateVerification,exclusions:module.CANDIDATE_EXCLUSIONS});
    }
    return nativeReaders.get(source.root);
  }
  async function load(force=false){
    const rows=[],snapshots=[];
    for(const source of sources){
      const raw=await readFile(source.file,'utf8'),tracker=JSON.parse(raw);
      need(tracker.project==='tclongages'&&tracker.phases?.length===4,'Source TC incorrecte.');
      snapshots.push({source,raw,tracker});
      for(const i of tracker.developmentIterations||[]){
        need(/^[a-z0-9-]+$/.test(i.iterationId||'')&&!rows.some(r=>r.iterationId===i.iterationId),'Identifiant de lot incorrect ou dupliqué.');
        const criteria=i.reviewCriteria||tracker.phases.map(p=>p.exitCriteria);
        need(criteria.length===4&&criteria.every(c=>Array.isArray(c)&&c.length&&c.every(t=>typeof t==='string')),'Critères obligatoires incomplets.');
        const item={...i,reviewCriteria:criteria};
        const git=i.candidate?.gitSourceDigest;
        const native=git?await reader(source):{verify:readCandidateVerification,exclusions:CANDIDATE_EXCLUSIONS};
        const pr=/^[a-f0-9]{40}$/.test(i.candidate?.sourceSha||'')?await verifyPullRequest({url:i.github?.prUrl,sourceCommit:i.candidate.sourceSha,excludedPaths:native.exclusions,force}):{passed:false,status:'not_ready',url:i.github?.prUrl,issues:['La version locale doit être figée avant vérification de la PR.']};
        const check=git?await native.verify(source.root,{sourceCommit:i.candidate.sourceSha,artifactDigest:git,candidateManifest:'data/framework-candidate.json'}):{passed:false,issues:['Candidat local non figé.']};
        const events=currentEvents(item);
        rows.push({...item,phaseIndex:knownStage(i),phaseLabels:ITERATION_PHASES,sourceCheck:check,pullRequestCheck:pr,
          phaseViews:criteria.map((labels,id)=>{
            const last=events.filter(e=>e.phaseId===id).at(-1);
            const issues=reviewIssues(item,id,pr,check);
            const accepted=last?.action==='approve'&&last.acceptedHead===pr?.headCommit&&pr?.passed&&check.passed&&!issues.length;
            return {id,criteria:labels,status:accepted?'validated':last?.action==='submit'?'awaiting_review':last?.action==='request_changes'?'in_progress':'not_reviewed',
              checkedCriteria:last?.checkedCriteria||[],comment:last?.comment||'',issues,
              actions:{submit:id<=knownStage(i)&&last?.action!=='submit',approve:last?.action==='submit'&&!issues.length,request_changes:id<=knownStage(i)}};
          })});
      }
    }
    const revision=hash({files:snapshots.map(s=>hash(s.raw)),versions:rows.map(i=>({id:i.iterationId,sourceCheck:i.sourceCheck,pr:{passed:i.pullRequestCheck.passed,headCommit:i.pullRequestCheck.headCommit,status:i.pullRequestCheck.status}}))});
    return {public:{revision,iterations:rows},snapshots};
  }
  async function read({force=false}={}){return (await load(force)).public;}
  async function mutate(request){
    need(!writing,'Une autre décision est en cours.',409);writing=true;
    try{
      need(['submit','approve','request_changes'].includes(request.action),'Action du cockpit inconnue.');
      need(request.confirmed===true,'La confirmation personnelle est obligatoire.');
      need(typeof request.comment==='string'&&request.comment.trim().length>=3&&request.comment.length<=6000,'Un commentaire de 3 à 6 000 caractères est requis.');
      const state=await load(request.action==='approve');
      need(request.revision===state.public.revision,'Le dossier a changé. Actualisez en conservant votre saisie puis confirmez de nouveau.',409);
      const row=state.public.iterations.find(i=>i.iterationId===request.iterationId);
      need(row,'Lot inconnu.');need(request.actor===row.owner,'Responsable déclaré incorrect.');
      need(request.sourceSha===(row.candidate?.sourceSha||null)&&request.artifactSha256===(row.candidate?.artifactSha256||null),'La décision porte sur un autre candidat.',409);
      const phase=row.phaseViews[request.phaseId];need(phase&&Number.isInteger(request.phaseId),'Phase inconnue.');
      const checked=request.checkedCriteria;
      need(Array.isArray(checked)&&new Set(checked).size===checked.length&&checked.every(x=>Number.isInteger(x)&&x>=0&&x<phase.criteria.length),'Critères incorrects.');
      need(phase.actions[request.action],request.action==='approve'?(phase.issues.join(' ')||'Passez d’abord le dossier en Revue.'):'Cette action ne concerne pas une phase en cours.');
      if(request.action==='approve')need(checked.length===phase.criteria.length,'Tous les critères obligatoires doivent être cochés.');
      const snapshot=state.snapshots.find(s=>s.tracker.developmentIterations?.some(i=>i.iterationId===row.iterationId)),record=snapshot.tracker.developmentIterations.find(i=>i.iterationId===row.iterationId);
      record.reviewCriteria=row.reviewCriteria;
      record.reviewEvents||=[];
      record.reviewEvents.push({id:randomUUID(),action:request.action,phaseId:request.phaseId,actor:request.actor,comment:request.comment.trim(),checkedCriteria:checked,
        confirmed:true,recordedAt:new Date().toISOString(),identityMode:'local_declared_not_authenticated',candidateKey:candidateKey(row),sourceSha:row.candidate?.sourceSha||null,
        artifactSha256:row.candidate?.artifactSha256||null,acceptedHead:request.action==='approve'?row.pullRequestCheck.headCommit:null,
        previousValidationRef:request.phaseId>0?currentEvents(row).filter(e=>e.phaseId===request.phaseId-1).at(-1)?.id||null:null,scope:'iteration_review_only_no_remote_authorization'});
      need(await readFile(snapshot.source.file,'utf8')===snapshot.raw,'Le suivi a changé pendant la décision ; aucune écriture.',409);
      const tmp=snapshot.source.file+'.iteration.tmp';
      const backup=resolve(snapshot.source.root,'.local/framework-iteration-backups');
      await mkdir(backup,{recursive:true});
      need((await realpath(backup)).startsWith(snapshot.source.root+sep),'Sauvegarde du suivi redirigée.');
      await writeFile(resolve(backup,randomUUID()+'.json'),snapshot.raw,{encoding:'utf8',flag:'wx',mode:0o600});
      await writeFile(tmp,JSON.stringify(snapshot.tracker,null,2)+'\n',{encoding:'utf8',flag:'wx',mode:0o600});
      try{await rename(tmp,snapshot.source.file);}catch(error){await unlink(tmp).catch(()=>{});throw error;}
      return read();
    }finally{writing=false;}
  }
  return {read,mutate};
}
