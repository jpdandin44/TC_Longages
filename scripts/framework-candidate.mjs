import {spawn} from 'node:child_process';
import {createHash} from 'node:crypto';
import {readFile, writeFile, mkdir, realpath, lstat} from 'node:fs/promises';
import {resolve, dirname, sep} from 'node:path';
import {fileURLToPath} from 'node:url';

export const CANDIDATE_MANIFEST = 'data/framework-candidate.json';
export const CANDIDATE_EXCLUSIONS = Object.freeze([
  'data/framework-candidate.json',
  'data/framework-revue-verification.json',
  'data/support-v1-verification.json',
  'docs/suivi-chantier/suivi-chantier.json',
  'docs/suivi-chantier/tableau-de-bord.html',
  'docs/suivi-chantier/tableau-de-bord.md',
]);
const excluded = new Set(CANDIDATE_EXCLUSIONS);
const sha256 = value => createHash('sha256').update(value).digest('hex');
const canonical = value => JSON.stringify(value);
const validSha = value => typeof value === 'string' && /^[a-f0-9]{40}$/.test(value);
const splitZero = buffer => buffer.toString('utf8').split('\0').filter(Boolean);

function git(root, args, input) {
  return new Promise((resolveResult, reject) => {
    const child = spawn('git', ['-c', 'core.quotepath=false', ...args], {cwd:root, windowsHide:true, stdio:['pipe','pipe','pipe']});
    const output=[]; let length=0;
    child.on('error', () => reject(new Error('Git est indisponible pour vérifier le candidat.')));
    child.stdout.on('data', chunk => {
      length += chunk.length;
      if (length > 128*1024*1024) {child.kill(); reject(new Error('Candidat trop volumineux pour la vérification locale.'));}
      else output.push(chunk);
    });
    // Never expose raw Git stderr or blob content in the UI.
    child.stderr.resume();
    child.on('close', code => code === 0 ? resolveResult(Buffer.concat(output)) : reject(new Error(`Vérification Git impossible (${args[0]}).`)));
    child.stdin.on('error', () => {});
    child.stdin.end(input);
  });
}

async function repositoryRoot(root) {
  const actual = await realpath(root);
  const top = (await git(actual, ['rev-parse','--show-toplevel'])).toString().trim();
  if (await realpath(top) !== actual) throw new Error('Le candidat doit utiliser la racine exacte du dépôt du projet.');
  return actual;
}

async function resolveCommit(root, ref) {
  if (ref !== 'HEAD' && !validSha(ref)) throw new Error('Un commit Git complet ou HEAD est requis.');
  const commit = (await git(root, ['rev-parse','--verify',`${ref}^{commit}`])).toString().trim();
  if (!validSha(commit)) throw new Error('Commit Git invalide.');
  return commit;
}

async function committedFiles(root, commit) {
  const tree = splitZero(await git(root, ['ls-tree','-r','-z','--full-tree',commit]));
  const records = tree.map(entry => {
    const tab = entry.indexOf('\t');
    const [mode, type, oid] = entry.slice(0,tab).split(' ');
    return {path:entry.slice(tab+1),mode,type,oid};
  }).filter(file => !excluded.has(file.path)).sort((a,b) => a.path < b.path ? -1 : a.path > b.path ? 1 : 0);
  for (const file of records) {
    if (!['100644','100755'].includes(file.mode) || file.type !== 'blob') throw new Error('Lien symbolique ou sous-module non pris en charge dans le candidat.');
    if (!file.path || /[\u0000-\u001f\\]/.test(file.path) || file.path.startsWith('/') || file.path.split('/').includes('..')) throw new Error('Chemin source Git invalide.');
  }
  const ids = [...new Set(records.map(file => file.oid))];
  if (!ids.length) throw new Error('Aucun fichier source dans le candidat.');
  const batch = await git(root, ['cat-file','--batch'], ids.join('\n')+'\n');
  let offset=0; const blobs=new Map();
  for (const oid of ids) {
    const end = batch.indexOf(10,offset);
    const [actual,type,sizeText] = batch.subarray(offset,end).toString().split(' ');
    const size = Number(sizeText); offset=end+1;
    if (actual!==oid || type!=='blob' || !Number.isSafeInteger(size) || size<0 || offset+size>=batch.length) throw new Error('Lecture des objets Git incohérente.');
    blobs.set(oid,{size,sha256:sha256(batch.subarray(offset,offset+size))}); offset+=size+1;
  }
  return records.map(({path,mode,oid}) => ({path,mode,gitBlob:oid,...blobs.get(oid)}));
}

export async function createCandidateManifest(root, sourceCommit='HEAD') {
  root=await repositoryRoot(root);
  sourceCommit=await resolveCommit(root,sourceCommit);
  const files=await committedFiles(root,sourceCommit);
  const payload={format:'tcl-git-candidate-v1',project:'tclongages',sourceCommit,excludedPaths:[...CANDIDATE_EXCLUSIONS],files};
  return {...payload,artifactDigest:sha256(canonical(payload))};
}

export async function verifyCandidateManifest(root, manifest, context={}) {
  const issues=[]; let head=null;
  try {
    root=await repositoryRoot(root);
    if (!manifest || !validSha(manifest.sourceCommit)) throw new Error('Manifeste du candidat absent ou commit invalide.');
    const expected=await createCandidateManifest(root,manifest.sourceCommit);
    if (canonical(manifest)!==canonical(expected)) issues.push('Le manifeste ne correspond pas aux fichiers du commit source.');
    if (context.sourceCommit!==undefined && context.sourceCommit!==expected.sourceCommit) issues.push('Le commit du contexte et celui du manifeste diffèrent.');
    if (context.artifactDigest!==undefined && context.artifactDigest!==expected.artifactDigest) issues.push('L’empreinte du contexte et celle du manifeste diffèrent.');
    head=await resolveCommit(root,'HEAD');
    try {await git(root,['merge-base','--is-ancestor',expected.sourceCommit,head]);}
    catch {issues.push('Le commit testé n’appartient pas à l’historique courant.');}
    const paths=['--','.',...CANDIDATE_EXCLUSIONS.map(path=>`:(top,exclude)${path}`)];
    const [headDiff,indexDiff,workingDiff,untracked] = await Promise.all([
      git(root,['diff','--name-only','-z','--no-ext-diff','--no-textconv',expected.sourceCommit,head,...paths]),
      git(root,['diff','--cached','--name-only','-z','--no-ext-diff','--no-textconv',expected.sourceCommit,...paths]),
      git(root,['diff','--name-only','-z','--no-ext-diff','--no-textconv',expected.sourceCommit,...paths]),
      git(root,['ls-files','--others','--exclude-standard','-z']),
    ]);
    const committedChanges=splitZero(headDiff), stagedChanges=splitZero(indexDiff), workingChanges=splitZero(workingDiff);
    const newSources=splitZero(untracked).filter(path=>!excluded.has(path));
    if (committedChanges.length) issues.push('Des sources ont changé dans Git après le commit testé : '+committedChanges.join(', '));
    if (stagedChanges.length) issues.push('Les sources indexées diffèrent du commit testé : '+stagedChanges.join(', '));
    if (workingChanges.length) issues.push('Les sources de travail diffèrent du commit testé : '+workingChanges.join(', '));
    if (newSources.length) issues.push('Des fichiers non ignorés ne sont pas inclus dans le candidat : '+newSources.join(', '));
    // Git normalization handles CRLF. Explicitly reject paths that now escape
    // through symlinks, even when a clean filter would hide their differences.
    const readable=[];
    for (const file of expected.files) {
      const target=resolve(root,file.path);
      try {
        if (!(await lstat(target)).isFile() || !(await realpath(target)).startsWith(root+sep)) issues.push('Fichier source absent, lié ou hors projet : '+file.path);
        else readable.push(file);
      } catch {if(!workingChanges.includes(file.path)) issues.push('Fichier source inaccessible : '+file.path);}
    }
    // Read actual working files as well: diff alone can trust cached stat data
    // or index flags such as assume-unchanged. Git applies the declared CRLF
    // normalization to the bytes without writing objects or changing the index.
    if(readable.length) {
      const hashes=(await git(root,['hash-object','--stdin-paths'],readable.map(file=>JSON.stringify(file.path)).join('\n')+'\n')).toString().trim().split(/\r?\n/);
      if(hashes.length!==readable.length) throw new Error('Lecture des fichiers de travail incomplète.');
      const byteChanges=readable.filter((file,index)=>hashes[index]!==file.gitBlob).map(file=>file.path);
      if(byteChanges.length) issues.push('Le contenu réel des sources a changé : '+byteChanges.join(', '));
    }
  } catch(error) {issues.push(error.message);}
  return {passed:issues.length===0,issues,head,sourceCommit:manifest?.sourceCommit||null,artifactDigest:manifest?.artifactDigest||null,manifestDigest:manifest?sha256(canonical(manifest)):null};
}

export async function readCandidateVerification(root, context) {
  if (context?.candidateManifest !== CANDIDATE_MANIFEST) return {passed:false,issues:['Le manifeste de revue attendu est data/framework-candidate.json.'],sourceCommit:context?.sourceCommit||null};
  try {
    root=await realpath(root);
    const target=resolve(root,CANDIDATE_MANIFEST);
    if (!(await lstat(target)).isFile() || !(await realpath(target)).startsWith(root+sep)) throw new Error('Manifeste hors projet ou lié.');
    return await verifyCandidateManifest(root,JSON.parse(await readFile(target,'utf8')),context);
  } catch(error) {return {passed:false,issues:['Manifeste de revue illisible ou absent.'],sourceCommit:context?.sourceCommit||null};}
}

async function main() {
  const [action='verify',ref='HEAD']=process.argv.slice(2);
  const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
  if (action==='prepare') {
    const manifest=await createCandidateManifest(root,ref);
    const check=await verifyCandidateManifest(root,manifest);
    if (!check.passed) throw new Error(check.issues.join('\n'));
    await mkdir(resolve(root,'data'),{recursive:true});
    await writeFile(resolve(root,CANDIDATE_MANIFEST),JSON.stringify(manifest,null,2)+'\n');
    console.log(JSON.stringify({manifest:CANDIDATE_MANIFEST,sourceCommit:manifest.sourceCommit,artifactDigest:manifest.artifactDigest,files:manifest.files.length}));
  } else if(action==='verify') {
    const manifest=JSON.parse(await readFile(resolve(root,CANDIDATE_MANIFEST),'utf8'));
    const result=await verifyCandidateManifest(root,manifest);
    console.log(JSON.stringify(result,null,2));
    if (!result.passed) process.exitCode=1;
  } else throw new Error('Usage : node scripts/framework-candidate.mjs prepare [SHA|HEAD] | verify');
}
if (process.argv[1] && resolve(process.argv[1])===fileURLToPath(import.meta.url)) main().catch(error=>{console.error(error.message);process.exitCode=1;});
