import { readFile, writeFile, rename, mkdir, realpath, readdir } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { createHash, randomUUID } from 'node:crypto';
import { spawnSync } from 'node:child_process';
import { dirname, resolve, isAbsolute, sep } from 'node:path';
import { fileURLToPath } from 'node:url';
import { loadReviewState, validateInteractiveState } from './framework-store.mjs';

export const root = fileURLToPath(new URL('../', import.meta.url));
const reference = 'docs/references/framework-developpement-pilote';
export const digest = value => createHash('sha256').update(value).digest('hex');
// Toute évolution d’un contrôle actif doit être revue avec son empreinte.
// Ce garde-fou local ne remplace pas la protection de branche ni la revue humaine.
const approvedWorkflows = Object.freeze({
  'ci.yml': 'de05726107a4da4a068f4deb32c2f072d71f516fcd90e303a5cd7bf13817572f',
  'pr-policy.yml': '6b4e5856e70a06348187f1344893bafda532861eb446ae24387e3e87f6022c4d',
  'preparer-deploiement.yml': '917ff7b17bfc85ba37b8a874a9134739a0569506ad69c6ea81eccc6097ab298e'
});
export function validateActiveWorkflow(name, content) {
  if (!Object.hasOwn(approvedWorkflows, name)) throw new Error('Workflow actif non autorisé : ' + name);
  if (digest(content.replace(/\r\n/g, '\n')) !== approvedWorkflows[name]) throw new Error('Contenu du workflow à requalifier : ' + name);
}
export async function checkActiveWorkflows(directory = resolve(root, '.github/workflows')) {
  const entries = await readdir(directory, {withFileTypes:true});
  const active = entries.filter(entry => /\.ya?ml$/i.test(entry.name));
  for (const entry of active) {
    if (!entry.isFile()) throw new Error('Le workflow doit être un fichier régulier : ' + entry.name);
    validateActiveWorkflow(entry.name, await readFile(resolve(directory, entry.name), 'utf8'));
  }
  for (const name of Object.keys(approvedWorkflows)) if (!active.some(entry => entry.name === name)) throw new Error('Contrôle requis absent : ' + name);
  return active.map(entry => entry.name).sort();
}
const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
const cell = value => escape(value ?? 'À déterminer').replace(/[\\`*_[\]{}()|!]/g, c => '\\' + c).replace(/[\r\n]+/g, ' ');

export function boundedPath(base, path) {
  if (typeof path !== 'string' || !path || /[\\:#?\u0000-\u001f]/.test(path) || isAbsolute(path) || path.split('/').includes('..')) throw new Error('Chemin hors périmètre : ' + path);
  const target = resolve(base, path);
  if (!target.startsWith(resolve(base) + sep)) throw new Error('Chemin hors périmètre : ' + path);
  return target;
}

// Générateur de vues uniquement. Les transitions et décisions relèvent du store commun.
export function validateLocalState(profile, tracker) {
  const errors = validateInteractiveState(profile, tracker);
  if (errors.length) return errors;
  if (!/^https:\/\/github\.com\/jpdandin44\/TC_Longages$/.test(profile.project.repository)) errors.push('Seule l’URL HTTPS du dépôt communiqué est admise dans ce lot.');
  if (profile.implementationStatus !== 'configured_unqualified') errors.push('Le suivi local ne qualifie pas la chaîne de livraison.');
  if (!Number.isInteger(tracker.currentPhase) || !tracker.phases.some(phase => phase.id === tracker.currentPhase)) errors.push('Phase courante inconnue.');
  if (!/^\d{4}-\d{2}-\d{2}$/.test(tracker.updated)) errors.push('Date du suivi invalide.');
  for (const phase of tracker.phases) {
    for (const deliverable of phase.deliverables) {
      try { boundedPath(resolve(root, 'docs/suivi-chantier'), deliverable.path); } catch (error) { errors.push(error.message); }
    }
  }
  return errors;
}

export async function readState() {
  const projectRoot = await realpath(root);
  const trackingRoot = await realpath(resolve(root, 'docs/suivi-chantier'));
  if (!trackingRoot.startsWith(projectRoot + sep)) throw new Error('Le dossier du suivi sort du projet.');
  const state = await loadReviewState(root);
  const { profile, tracker } = state;
  const errors = validateLocalState(profile, tracker);
  if (errors.length) throw new Error(errors.join('\n'));
  for (const phase of tracker.phases) for (const deliverable of phase.deliverables) {
    const base = resolve(root, 'docs/suivi-chantier');
    const file = boundedPath(base, deliverable.path);
    const canonical = await realpath(file);
    if (!canonical.startsWith(await realpath(base) + sep)) throw new Error('Lien de fichier hors périmètre.');
    const contents = await readFile(file, 'utf8');
    if (!/^---\r?\n/.test(contents) || !contents.includes('owner: jpdandin')) throw new Error('Métadonnées absentes : ' + deliverable.path);
  }
  return state;
}

export function renderViews({profile, tracker, installation = null, phaseViews = []}) {
  const current = tracker.phases.find(p => p.id === tracker.currentPhase);
  const recordedValidations = tracker.phases.filter(p => p.status === 'validated').length;
  const currentValidations = phaseViews.filter(p => p.validationCurrent).length;
  const label = p => p.status === 'validated' && phaseViews.find(v => v.phaseId === p.id)?.validationCurrent === false
    ? 'Validée historiquement — à requalifier' : tracker.statusLabels[p.status];
  const latestRepository = [...(tracker.observations || [])].reverse().find(o => o.kind === 'repository');
  const repositoryObservation = latestRepository
    ? `${latestRepository.statement} ${latestRepository.limits || ''}` : 'État du dépôt à vérifier par le canal d’accès concerné.';
  const architecture = [...tracker.decisions].reverse().find(d => d.type === 'architecture' && d.status === 'approved');
  const architectureObservation = architecture?.scope || 'Architecture applicative à déterminer.';
  const installationObservation = installation?.status === 'secured'
    ? 'Installation clôturée : les autorisations normales de progression sont réactivées.'
    : installation?.status === 'active'
      ? `Installation locale encadrée : exception des phases 0 à 3, échéance ${installation.expiresAt}. Cette exception ne valide aucune phase et n’autorise aucune opération distante.`
      : 'Aucune exception d’installation active ; les autorisations normales de progression s’appliquent.';
  const phaseRows = tracker.phases.map(p => `| ${p.id} — ${cell(tracker.phaseModel?.labels?.[p.id]||p.title)} | ${cell(label(p))} | [Dossier](${encodeURI(p.deliverables[0].path).replace(/[()]/g, c => '%' + c.charCodeAt(0).toString(16))}) | ${cell(p.nextAction)} |`).join('\n');
  const openRows = profile.openItems.map(item => `| ${cell(item.id)} | ${cell(item.topic)} | ${cell(item.impact)} | ${cell(item.nextAction)} |`).join('\n');
  const fingerprint = digest(JSON.stringify({profile, tracker, installation, phaseViews}));
  const summary = `Phase actuelle : ${current?.id ?? '—'} — ${current?.title ?? 'À déterminer'}. ${recordedValidations} validation(s) consignée(s), ${currentValidations} encore recevable(s) selon le moteur. Aucune livraison attestée par ce suivi local.`;
  const markdown = `---
project: TC_Longages
document_type: generated-project-dashboard
title: Tableau de bord du développement piloté
status: active
version: git
created: 2026-09-29
updated: ${tracker.updated}
owner: jpdandin
tags: [framework, suivi, vue-generee]
---

# TC Longages — suivi du projet

<!-- Vue générée par scripts/framework.mjs ; les décisions passent par le moteur interactif. -->

**${cell(summary)}**

[Ouvrir le suivi interactif local](http://127.0.0.1:4181/) · [Ouvrir Drupal local](http://127.0.0.1:4182/)

Ces adresses nécessitent le démarrage des services locaux ; cette vue ne certifie pas leur disponibilité. La revue interactive permet notes, critères et décisions locales distinctes. Elle ne déploie ni n’ouvre le site au public.

Domaine : **${cell(profile.project.domain)}**, obtenu selon confirmation utilisateur ; DNS/HTTPS non vérifiés par ce générateur. Dépôt : [TC_Longages](${profile.project.repository}).

${cell(repositoryObservation)}

**Installation :** ${cell(installationObservation)}

**Architecture retenue ou à décider :** ${cell(architectureObservation)} L’état d’installation et la qualification sont documentés séparément ; une décision d’architecture ne constitue pas une preuve d’authentification effective.

## Phases

| Phase | État | Livrable | Prochaine action |
|---|---|---|---|
${phaseRows}

## Points à résoudre

| Référence | Sujet | Conséquence | Action |
|---|---|---|---|
${openRows}

## Lire ce suivi

Les quatre phases regroupent le cadrage, le développement local, la préproduction et la mise en production ; G0–G8 restent les lots métier de la V1. Les recettes historiques ne créent pas de validation automatique. Une validation peut rester consignée tout en devenant à requalifier après changement de documents ou de preuves. Le suivi ne calcule aucun pourcentage d’effort.

Cette vue statique ne modifie aucune décision. Le moteur interactif en boucle locale distingue validation et autorisation suivante ; l’identité y reste déclarée, sans authentification distante. Le mode d’installation n’accorde aucun déploiement, aucune ouverture publique et aucune permission de modifier les accès distants. Les adaptations de préproduction, livraison et restauration restent à qualifier.

[Guide du framework](../framework-developpement.md) · [Point de session](../point-session.md) · [Suivi canonique](suivi-chantier.json) · [Profil](../../framework/profil-projet.json)

Empreinte du profil, du suivi, de l’état d’installation et de la revue courante : \`${fingerprint}\`.
`;
  const htmlRows = tracker.phases.map(p=>`<tr><th scope="row">${p.id}. ${escape(tracker.phaseModel?.labels?.[p.id]||p.title)}</th><td><span class="state">${escape(label(p))}</span></td><td>${escape(p.objective)}</td><td>${escape(p.nextAction)}</td></tr>`).join('');
  const cards = profile.openItems.map(i=>`<article><h3>${escape(i.topic)}</h3><p>${escape(i.impact)}</p><p><strong>Suite :</strong> ${escape(i.nextAction)}</p></article>`).join('');
  const html = `<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'"><title>TC Longages — suivi du projet</title><style>*{box-sizing:border-box}body{margin:0;background:#f7f7f7;color:#202124;font:16px/1.6 system-ui,sans-serif}main{max-width:1160px;margin:auto;padding:32px 20px}header{border-top:6px solid #a6192e;padding:24px;background:white;border-radius:12px}h1{font-size:clamp(1.7rem,4vw,2.7rem);margin:.2em 0}h2{margin-top:2em}h3{margin-top:0}.eyebrow{color:#a6192e;font-weight:700}.note{padding:16px;border-left:4px solid #a6192e;background:#f7e7ea}.table{overflow:auto}table{border-collapse:collapse;background:white;min-width:650px;width:100%}th,td{text-align:left;vertical-align:top;padding:14px;border-bottom:1px solid #ddd}thead{background:#741323;color:white}.state{font-weight:650}.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,270px),1fr));gap:16px}article{padding:20px;background:white;border:1px solid #e3e5e8;border-radius:10px}a{color:#741323}.links{display:flex;flex-wrap:wrap;gap:16px}.links a{padding:10px;border:1px solid #bba7aa;border-radius:6px}footer{margin-top:32px;overflow-wrap:anywhere;font-size:.85rem}</style></head><body><main><header><span class="eyebrow">TENNIS CLUB DE LONGAGES · ${escape(tracker.updated)}</span><h1>Le projet, étape par étape</h1><p>${escape(summary)}</p><p class="links"><a href="http://127.0.0.1:4181/">Ouvrir le suivi interactif local</a><a href="http://127.0.0.1:4182/">Ouvrir Drupal local</a></p><p>Ces adresses nécessitent des services locaux démarrés ; leur présence ne prouve pas leur disponibilité.</p><p><strong>${escape(profile.project.domain)}</strong> — obtenu selon votre confirmation. DNS et HTTPS non vérifiés par ce générateur.</p><p>Dépôt : <a href="${escape(profile.project.repository)}">TC_Longages</a>.</p><p>${escape(repositoryObservation)}</p></header><p class="note">${escape(installationObservation)}</p><h2>Les quatre phases</h2><div class="table" tabindex="0" role="region" aria-label="Phases du projet"><table><thead><tr><th>Phase</th><th>État</th><th>Objectif</th><th>Prochaine action</th></tr></thead><tbody>${htmlRows}</tbody></table></div><h2>Les points à résoudre</h2><div class="cards">${cards}</div><h2>Architecture et portée</h2><p>${escape(architectureObservation)}</p><p>Le choix d’architecture, son installation et les droits réellement qualifiés sont des états distincts. G0–G8 restent les lots métier de la V1 ; cette méthode n’efface pas leurs acquis.</p><p>Une validation historique peut demander une nouvelle qualification après modification. Le moteur interactif local conserve les notes et distingue les décisions ; l’identité reste déclarée sur ce poste. Aucune opération distante n’est déclenchée par ces vues.</p><footer>Vue statique générée depuis le profil, le suivi et l’état d’installation. Le moteur interactif local gère les écritures et leurs contrôles. Empreinte des entrées : ${fingerprint}.</footer></main></body></html>\n`;
  return {'tableau-de-bord.md': markdown, 'tableau-de-bord.html': html};
}

async function checkReference() {
  const lines = (await readFile(resolve(root, reference, 'MANIFEST.sha256'), 'utf8')).trim().split(/\r?\n/);
  for (const line of lines) {
    const match = /^([a-f0-9]{64})  (.+)$/.exec(line);
    if (!match || digest(await readFile(boundedPath(resolve(root, reference), match[2]))) !== match[1]) throw new Error('Référence du framework modifiée : ' + line);
  }
  return lines.length;
}

function checkSchema() {
  const python = process.env.TCL_FRAMEWORK_PYTHON || (existsSync(resolve(root,'.local/framework-venv/Scripts/python.exe')) ? resolve(root,'.local/framework-venv/Scripts/python.exe') : process.platform === 'win32' ? 'python' : 'python3');
  // Le schéma reçu porte sur le profil. Le suivi interactif utilise le contrat du
  // store : ses validations ne sont pas les « approval » du modèle vierge fourni.
  const code = [
    'import json, sys',
    'from pathlib import Path',
    'from jsonschema import Draft202012Validator, FormatChecker',
    'profile, schema = [json.loads(Path(p).read_text(encoding="utf-8-sig")) for p in sys.argv[1:]]',
    'Draft202012Validator.check_schema(schema)',
    'errors = list(Draft202012Validator(schema, format_checker=FormatChecker()).iter_errors(profile))',
    'print(json.dumps({"valid": not errors, "errors": [e.message for e in errors]}, ensure_ascii=False))',
    'sys.exit(1 if errors else 0)'
  ].join('\n');
  const result = spawnSync(python, ['-c', code, resolve(root,'framework/profil-projet.json'), resolve(root,reference,'schema-projet.json')], {cwd:root,encoding:'utf8',env:{...process.env,PYTHONUTF8:'1',PYTHONDONTWRITEBYTECODE:'1'}});
  if (result.status !== 0) throw new Error('Validation du schéma impossible. Voir docs/framework-developpement.md.\n' + (result.stderr || result.stdout || result.error));
}

async function main() {
  const command = process.argv[2];
  if (!['build','check'].includes(command)) throw new Error('Utilisation : node scripts/framework.mjs build|check');
  const referenceFiles = await checkReference();
  checkSchema();
  const activeWorkflows = await checkActiveWorkflows();
  const views = renderViews(await readState());
  for (const [name, content] of Object.entries(views)) {
    const target = resolve(root, 'docs/suivi-chantier', name);
    if (command === 'check') {
      if (await readFile(target, 'utf8') !== content) throw new Error('Vue périmée : ' + name + ' ; lancer npm run framework:build.');
    } else {
      await mkdir(dirname(target), {recursive:true});
      const temporary = target + '.' + randomUUID() + '.tmp';
      await writeFile(temporary, content, 'utf8');
      await rename(temporary, target);
    }
  }
  console.log(JSON.stringify({status:'passed',referenceFiles,activeWorkflows,views:Object.keys(views),mode:'local-read-only',remoteDeployment:false}));
}
if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) main().catch(error => { console.error(error.message); process.exitCode = 1; });
