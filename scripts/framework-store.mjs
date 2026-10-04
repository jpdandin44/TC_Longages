import {readFile, writeFile, mkdir, rename, realpath, open, unlink, stat} from 'node:fs/promises';
import {resolve, sep, dirname} from 'node:path';
import {createHash, randomUUID} from 'node:crypto';
import {readCandidateVerification} from './framework-candidate.mjs';

export const hash = value => createHash('sha256').update(typeof value === 'string' || Buffer.isBuffer(value) ? value : JSON.stringify(value)).digest('hex');
export class ReviewError extends Error { constructor(message, status=422) { super(message); this.status=status; } }
const required = (condition, message, status) => { if (!condition) throw new ReviewError(message,status); };
const json = async path => JSON.parse(await readFile(path,'utf8'));
const sha = value => typeof value==='string' && /^[a-f0-9]{40}$/.test(value);
const fingerprint = value => typeof value==='string' && /^[a-f0-9]{64}$/.test(value);
export const hasEvidenceContext = evidence => sha(evidence?.sourceCommit)&&Number.isFinite(Date.parse(evidence?.recordedAt))&&['actor','artifactRef','actualResult'].every(key=>typeof evidence[key]==='string'&&evidence[key].trim().length>0);
export function bounded(root, path) {
  required(typeof path==='string' && path && !/[\\:#?\u0000-\u001f]/.test(path) && !path.startsWith('/') && !path.split('/').includes('..'), 'Chemin de document interdit.');
  const target=resolve(root,path); required(target.startsWith(resolve(root)+sep),'Chemin de document interdit.'); return target;
}
async function contained(root,path) {
  const canonical=await realpath(path); required(canonical.startsWith(await realpath(root)+sep),'Le chemin résolu sort du projet.'); return canonical;
}
export function validateInteractiveState(profile,tracker) {
  const errors=[];
  if(profile?.project?.id!=='tclongages'||tracker?.project!=='tclongages'||tracker?.template!==false||profile?.template!==false) errors.push('Projet incorrect.');
  if(profile?.profile?.trackerEngine?.remoteExposureAllowed!==false||profile?.governance?.deployOnPush!==false||profile?.governance?.deployOnMerge!==false||profile?.delivery?.openPublicAutomatically!==false) errors.push('Exposition ou livraison automatique interdite.');
  if(Object.values(tracker.publication||{}).some(Boolean)||Object.values(tracker.release||{}).some(Boolean)) errors.push('Ce moteur local ne peut pas attester une livraison.');
  if(tracker.phases?.length!==4||tracker.phases.some((p,i)=>p.id!==i||!Array.isArray(p.exitCriteria)||!p.exitCriteria.length||!['not_started','in_progress','awaiting_review','validated','blocked'].includes(p.status))) errors.push('Quatre phases ordonnées avec critères sont requises.');
  const ids=tracker.decisions?.map(d=>d.id)||[]; if(new Set(ids).size!==ids.length) errors.push('Décision dupliquée.');
  if(tracker.decisions?.some(d=>d.status==='approved'&&['production_deployment','public_opening'].includes(d.type))) errors.push('Une autorisation distante ne peut pas être attestée par ce moteur local.');
  if([...(tracker.evidence||[]),...(tracker.testRuns||[])].some(e=>e.status==='passed'&&!hasEvidenceContext(e))) errors.push('Preuve réussie sans contexte suffisant : commit, date, auteur, référence et résultat effectif requis.');
  for(const phase of tracker.phases||[]) {
    if(phase.dependsOn.some(id=>!Number.isInteger(id)||id<0||id>=phase.id)) errors.push('Dépendance invalide.');
    if(phase.id>=2) {
      const observation=phase.observedExecution;
      const observed=['in_progress','blocked'].includes(phase.status)&&observation?.source==='hosting_observation'&&observation.environment===(phase.id===2?'preproduction':'production')&&observation.status===phase.status&&Number.isFinite(Date.parse(observation.recordedAt))&&typeof observation.evidence==='string'&&observation.evidence.trim()&&typeof observation.authorizationRef==='string'&&observation.authorizationRef.trim();
      if((phase.status!=='not_started'&&!observed)||phase.startEvidence||phase.validationEvidence||phase.startedOn||phase.validatedOn) errors.push('Les phases distantes exigent des observations distinctes ; ce moteur ne les autorise ni ne les valide.');
    }
    if(phase.status==='validated' && !tracker.decisions.some(d=>d.id===phase.validationEvidence?.decisionId&&d.type==='validation'&&d.phaseId===phase.id&&d.status==='approved'&&d.environment==='local'&&profile.project.humanApprovers.includes(d.actor)&&d.identityMode==='local_declared_not_authenticated'&&d.comment&&d.documentDigest&&d.criteriaDigest&&sha(d.sourceCommit)&&fingerprint(d.artifactDigest))) errors.push('Validation sans décision locale complète.');
    if(phase.id>0 && phase.startedOn && !tracker.decisions.some(d=>d.id===phase.startEvidence?.decisionId&&d.type==='authorization'&&d.phaseId===phase.id&&d.status==='approved')) errors.push('Démarrage sans autorisation.');
  }
  if(!tracker.decisions?.some(d=>d.id===profile.project.initialAuthorizationRef&&d.type==='authorization'&&d.status==='approved'&&d.phaseId===0&&d.environment==='local'&&profile.project.humanApprovers.includes(d.actor))) errors.push('Autorisation initiale absente.');
  return errors;
}
export async function loadReviewState(root) {
  await contained(root,resolve(root,'docs/suivi-chantier'));
  const trackerPath=await contained(root,resolve(root,'docs/suivi-chantier/suivi-chantier.json'));
  const profilePath=await contained(root,resolve(root,'framework/profil-projet.json'));
  const [tracker,profile]=await Promise.all([json(trackerPath),json(profilePath)]);
  required(validateInteractiveState(profile,tracker).length===0,validateInteractiveState(profile,tracker).join(' '));
  let installation=null;
  try { installation=await json(await contained(root,resolve(root,'framework/installation.json'))); } catch(error) { if(error.code!=='ENOENT') throw error; }
  let reviewPolicy=null;
  try {reviewPolicy=await json(await contained(root,resolve(root,'framework/review-policy.json')));}
  catch(error) {if(error.code!=='ENOENT') throw error;}
  if(reviewPolicy) required(reviewPolicy.project==='tclongages'&&typeof reviewPolicy.candidateBindingRequired==='boolean','Politique du candidat invalide.');
  const documents=[];
  for(const p of tracker.phases) for(const [index,d] of p.deliverables.entries()) {
    required(d.kind==='file' && /\.md$/i.test(d.path),'Seuls les livrables Markdown déclarés sont consultables.');
    const file=await contained(resolve(root,'docs/suivi-chantier'),bounded(resolve(root,'docs/suivi-chantier'),d.path));
    required((await stat(file)).size<=300000,'Document trop volumineux.');
    const content=await readFile(file,'utf8');
    documents.push({phaseId:p.id,index,path:d.path,title:d.title,available:d.availability==='available'&&content.trim().length>80,digest:hash(content),content});
  }
  const candidateChecks={};
  const candidateCache=new Map();
  for(const phase of tracker.phases) {
    if(reviewPolicy?.candidateBindingRequired || phase.reviewContext?.candidateManifest) {
      const key=JSON.stringify(phase.reviewContext||{});
      if(!candidateCache.has(key)) candidateCache.set(key,await readCandidateVerification(root,phase.reviewContext));
      candidateChecks[phase.id]=candidateCache.get(key);
    }
  }
  const revision=hash({tracker,profile,installation,reviewPolicy,candidateChecks,documents:documents.map(({content,...rest})=>rest)});
  let recoveredDraft=null;
  try {
    const draft=await json(await contained(root,resolve(root,'.local/framework-review-draft.json')));
    const humanStateDigest=hash({decisions:tracker.decisions,history:tracker.history,reviewEvents:tracker.reviewEvents,reviewCommentResponses:tracker.reviewCommentResponses});
    if(draft.humanStateDigest===humanStateDigest&&Date.parse(draft.capturedAt)>Date.now()-86400000) recoveredDraft=recoverReviewDraft(draft,tracker,profile);
  } catch(error) {if(error.code!=='ENOENT'&&!(error instanceof SyntaxError)) throw error;}
  const state={tracker,profile,installation,reviewPolicy,candidateChecks,documents,revision,recoveredDraft};
  return {...state,phaseViews:tracker.phases.map(p=>phaseView(state,p))};
}
function binding(state,phase) {
  return {project:state.tracker.project,phaseId:phase.id,environment:'local',scope:state.tracker.scope,criteriaDigest:hash(phase.exitCriteria),documentDigest:hash(state.documents.filter(d=>d.phaseId===phase.id).map(d=>({path:d.path,digest:d.digest}))),sourceCommit:phase.reviewContext?.sourceCommit||null,artifactDigest:phase.reviewContext?.artifactDigest||null};
}
function currentValidation(state,phase) {
  const decision=state.tracker.decisions.find(d=>d.id===phase.validationEvidence?.decisionId);
  return phase.status==='validated'&&decision?.status==='approved'&&Object.entries(binding(state,phase)).every(([key,value])=>decision[key]===value)&&proofIssues(state,phase).length===0;
}
function proofIssues(state,p) {
  const issues=[], context=p.reviewContext, b=binding(state,p);
  if(state.reviewPolicy?.candidateBindingRequired || context?.candidateManifest) {
    const check=state.candidateChecks?.[p.id];
    if(!check?.passed) issues.push(...(check?.issues||['La version Git du candidat n’a pas été vérifiée.']));
  }
  if(!state.documents.filter(d=>d.phaseId===p.id).every(d=>d.available)) issues.push('Les livrables doivent être disponibles et non vides.');
  if(!context||!sha(context.sourceCommit)) issues.push('Un commit source de 40 caractères doit identifier la version relue.');
  if(!context||!fingerprint(context.artifactDigest)) issues.push('L’empreinte du candidat local est à renseigner dans le contexte technique.');
  if(context?.criteriaDigest!==b.criteriaDigest||context?.documentDigest!==b.documentDigest) issues.push('Les preuves techniques doivent porter sur les critères et documents actuels.');
  const pr=typeof p.pullRequest==='string'?p.pullRequest:p.pullRequest?.url;
  if(!/^https:\/\/github\.com\/jpdandin44\/TC_Longages\/pull\/\d+$/.test(pr||'')) issues.push('La PR du lot doit être référencée.');
  for(const [key,source] of [['evidenceRefs','evidence'],['testRunRefs','testRuns']]) {
    const refs=context?.[key];
    if(!Array.isArray(refs)||!refs.length||!refs.every(id=>state.tracker[source].some(e=>e.id===id&&e.phaseId===p.id&&e.status==='passed'&&hasEvidenceContext(e)&&e.environment==='local'&&e.sourceCommit===context.sourceCommit&&e.artifactDigest===context.artifactDigest))) issues.push(key==='evidenceRefs'?'Les preuves locales correspondant à ce candidat sont manquantes.':'Les résultats de tests correspondant à ce candidat sont manquants.');
  }
  if(p.blockers.length) issues.push('Des blocages de cette phase restent ouverts.');
  if(!p.dependsOn.every(id=>currentValidation(state,state.tracker.phases[id]))) issues.push('Les phases précédentes doivent être validées sur leurs versions actuelles.');
  return issues;
}
export function recoverReviewDraft(draft,tracker,profile) {
  const phase=tracker.phases.find(p=>p.id===draft.phaseId);
  if(!phase||!profile.project.humanApprovers.includes(draft.actor)||typeof draft.comment!=='string'||draft.comment.length>6000||!Array.isArray(draft.criteria)||!draft.criteria.length||draft.criteria.some((c,i)=>c.label!==phase.exitCriteria[i]||typeof c.checked!=='boolean')) return null;
  const followups=Array.isArray(draft.followups)?draft.followups.filter(f=>tracker.reviewEvents.some(e=>e.id===f.id&&e.type==='comment'&&e.phaseId===phase.id)&&['read','in_progress','needs_decision','resolved'].includes(f.status)&&typeof f.comment==='string'&&f.comment.length<=6000&&typeof f.evidence==='string'&&f.evidence.length<=2000):[];
  return {phaseId:phase.id,comment:draft.comment,actor:draft.actor,checked:draft.criteria.flatMap((c,i)=>c.checked?[i]:[]),confirmed:false,followups};
}
export function phaseView(state,p) {
  const issues=proofIssues(state,p), local=p.id<=1;
  const initial=p.id===0&&p.startEvidence?.decisionId===state.profile.project.initialAuthorizationRef;
  const authorization=state.tracker.decisions.find(d=>d.id===p.startEvidence?.decisionId&&d.type==='authorization'&&d.status==='approved');
  const authorized=initial||(authorization&&Object.entries(binding(state,p)).every(([k,v])=>authorization[k]===v));
  const checked=state.tracker.reviewEvents.filter(e=>e.type==='criteria_checked'&&e.phaseId===p.id&&e.criteriaDigest===hash(p.exitCriteria)&&e.documentDigest===binding(state,p).documentDigest).at(-1)?.checkedCriteria||[];
  const approvable=local&&p.status==='awaiting_review'&&!issues.length&&checked.length===p.exitCriteria.length;
  const i=state.installation, installationDecision=state.tracker.decisions.find(d=>d.id===i?.authorization?.id&&d.type==='installation_authorization'&&d.status==='approved'&&d.environment==='local'&&d.project===state.tracker.project);
  const bootstrap=local&&i?.project==='tclongages'&&i.status==='active'&&i.environment==='local'&&i.allowedPhaseIds?.includes(p.id)&&i.allowedPhaseIds.every(id=>Number.isInteger(id)&&id>=0&&id<=1)&&Date.parse(i.activatedAt)<=Date.now()&&Date.parse(i.expiresAt)>Date.now()&&Date.parse(i.expiresAt)<=Date.parse(installationDecision?.expiresAt)&&state.profile.project.humanApprovers.includes(i.authorization?.actor)&&installationDecision?.actor===i.authorization.actor&&installationDecision?.sourceRef===i.authorization.sourceRef&&installationDecision?.scope===i.authorization.scope&&i.protections?.remoteExposureAllowed===false&&i.protections?.deployAllowed===false&&i.protections?.csrfRequired===true&&i.protections?.revisionCheckRequired===true&&i.protections?.secretProtectionRequired===true;
  return {phaseId:p.id,binding:binding(state,p),checkedCriteria:checked,issues,bootstrapStart:!!bootstrap,validationCurrent:currentValidation(state,p),actions:{submit:local&&p.status==='in_progress'&&!issues.length,approve:approvable,authorize_next:local&&p.id<1&&currentValidation(state,p)&&!state.tracker.phases[p.id+1].startEvidence,start:local&&p.status==='not_started'&&(!!bootstrap||(!!authorized&&p.dependsOn.every(id=>currentValidation(state,state.tracker.phases[id])))),request_changes:local&&p.status==='awaiting_review'},external:!local};
}
export async function createReviewStore(root,{regenerate}={}) {
  root=await realpath(root);
  await mkdir(resolve(root,'.local'),{recursive:true}); await contained(root,resolve(root,'.local'));
  const lockPath=resolve(root,'.local/framework-runtime.lock');
  let lock;
  try { lock=await open(lockPath,'wx',0o600); await lock.writeFile(JSON.stringify({pid:process.pid,createdAt:new Date().toISOString()})); }
  catch(error) { if(error.code==='EEXIST') throw new Error('Un moteur de suivi possède déjà le verrou local. Vérifier son processus avant de retirer un verrou ancien.'); throw error; }
  let writing=false;
  async function mutate(request) {
    required(!writing,'Une autre écriture est en cours. Rechargez le suivi.',409); writing=true;
    try {
      const state=await loadReviewState(root);
      required(request.revision===state.revision,'Le suivi ou ses documents ont changé. Votre brouillon est conservé ; rechargez les données avant de confirmer.',409);
      const p=state.tracker.phases.find(p=>p.id===request.phaseId);
      required(p,'Phase inconnue.');
      required(state.profile.project.humanApprovers.includes(request.actor),'Choisissez le responsable déclaré du projet.');
      required(request.confirmed===true,'La confirmation humaine explicite est requise.');
      required(typeof request.comment==='string'&&request.comment.trim().length>=3&&request.comment.length<=6000,'Un commentaire de 3 à 6 000 caractères est requis.');
      const actor=request.actor, now=new Date().toISOString(), id='TCL-UI-'+randomUUID(), comment=request.comment.trim(), v=phaseView(state,p);
      const event={id,type:request.action,phaseId:p.id,actor,recordedAt:now,comment,identityMode:'local_declared_not_authenticated',sourceRef:'interface-locale-4181'};
      const t=state.tracker;
      if(request.action==='note') t.reviewEvents.push({...event,type:'comment',status:'recorded'});
      else if(request.action==='follow_up') {
        required(t.reviewEvents.some(e=>e.id===request.commentId&&e.type==='comment'&&e.phaseId===p.id),'Commentaire initial introuvable.');
        required(['read','in_progress','resolved','needs_decision'].includes(request.status),'État de suivi invalide.');
        required(request.status!=='resolved'||(typeof request.resolutionEvidence==='string'&&request.resolutionEvidence.trim().length>=3),'Une preuve ou référence de résolution est requise.');
        t.reviewCommentResponses.push({...event,commentId:request.commentId,status:request.status,resolutionEvidence:request.resolutionEvidence||null});
      } else if(request.action==='criteria') {
        required(Array.isArray(request.checkedCriteria)&&request.checkedCriteria.every(n=>Number.isInteger(n)&&n>=0&&n<p.exitCriteria.length)&&new Set(request.checkedCriteria).size===request.checkedCriteria.length,'Critères incorrects.');
        t.reviewEvents.push({...event,type:'criteria_checked',checkedCriteria:request.checkedCriteria,criteriaDigest:hash(p.exitCriteria),documentDigest:binding(state,p).documentDigest});
      } else {
        required(Object.hasOwn(v.actions,request.action)&&v.actions[request.action],'Action indisponible : '+(v.external?'les opérations distantes restent hors de ce moteur.':v.issues.join(' ')||'vérifiez l’état de la phase et les critères.'));
        if(request.action==='submit') {p.status='awaiting_review';p.deliveredOn=now.slice(0,10); t.reviewEvents.push({...event,...binding(state,p),type:'submitted'});}
        if(request.action==='request_changes') {p.status='in_progress';t.reviewEvents.push({...event,type:'changes_requested'});}
        if(request.action==='approve') {
          required(Array.isArray(request.checkedCriteria)&&request.checkedCriteria.length===p.exitCriteria.length&&p.exitCriteria.every((_,i)=>request.checkedCriteria.includes(i)),'Confirmez chaque critère de cette revue.');
          t.decisions.push({...event,...binding(state,p),type:'validation',status:'approved',checkedCriteria:request.checkedCriteria});
          p.status='validated';p.validatedOn=now.slice(0,10);p.validationEvidence={decisionId:id};
        }
        if(request.action==='authorize_next') {
          const next=t.phases[p.id+1];
          t.decisions.push({...event,...binding(state,next),type:'authorization',authorizationKind:'next_phase',status:'approved',previousValidationId:p.validationEvidence.decisionId});
          next.authorizedOn=now.slice(0,10);next.startEvidence={decisionId:id};
        }
        if(request.action==='start') {
          if(v.bootstrapStart&&!p.startEvidence) {t.decisions.push({...event,...binding(state,p),type:'authorization',authorizationKind:'installation_local',status:'approved',bootstrapAuthorizationRef:state.installation.authorization.id});p.startEvidence={decisionId:id};p.authorizedOn=now.slice(0,10);}
          p.status='in_progress';p.startedOn=now.slice(0,10);t.currentPhase=p.id;t.state='in_progress';
        }
      }
      t.history.push({...event,type:'ui_'+request.action,description:comment});t.updated=now.slice(0,10);
      required(validateInteractiveState(state.profile,t).length===0,validateInteractiveState(state.profile,t).join(' '));
      const backupDir=resolve(root,'.local/framework-backups'); await mkdir(backupDir,{recursive:true});await contained(root,backupDir);
      const target=resolve(root,'docs/suivi-chantier/suivi-chantier.json');
      const original=await readFile(target,'utf8');
      // Recheck the complete snapshot immediately before replacement, including edits outside this process.
      required((await loadReviewState(root)).revision===request.revision,'Le suivi a changé pendant la sauvegarde. Aucune décision enregistrée.',409);
      await writeFile(resolve(backupDir,now.replace(/[:.]/g,'-')+'-'+randomUUID()+'.json'),original,{flag:'wx',mode:0o600});
      const temp=target+'.'+randomUUID()+'.tmp';
      try {const file=await open(temp,'wx',0o600);try{await file.writeFile(JSON.stringify(t,null,2)+'\n');await file.sync();}finally{await file.close();}await rename(temp,target);} finally {await unlink(temp).catch(e=>{if(e.code!=='ENOENT')throw e;});}
      let viewWarning=null;
      if(regenerate) try {await regenerate();}catch {viewWarning='Enregistrement réussi ; les vues statiques doivent être régénérées. Ne confirmez pas une seconde fois.';}
      return {...await loadReviewState(root),viewWarning};
    } finally {writing=false;}
  }
  return {read:()=>loadReviewState(root),mutate,close:async()=>{await lock.close();await unlink(lockPath);}};
}
