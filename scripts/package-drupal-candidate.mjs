import {mkdtemp,mkdir,copyFile,cp,readFile,writeFile,stat} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import {execFile} from 'node:child_process';
import {promisify} from 'node:util';
import {resolve,dirname,sep} from 'node:path';
import {fileURLToPath} from 'node:url';
import {buildDrupalPublicPages,pages} from './build-drupal-public-pages.mjs';

const run=promisify(execFile);
const root=fileURLToPath(new URL('../',import.meta.url));
const digest=data=>createHash('sha256').update(data).digest('hex');
const inside=(base,path)=>path.startsWith(base+sep);

export async function verifyDrupalCandidateZip(zip){
  const listing=(await run('tar',['-tf',zip],{maxBuffer:30*1024*1024})).stdout.toString('utf8').split(/\r?\n/).filter(Boolean);
  const forbidden=new Set(['drupal/web/sites/default/settings.php','drupal/.local/drupal-admin.json','drupal/.local/site.sqlite','drupal/.env']);
  if(listing.some(item=>forbidden.has(item))||!listing.includes('drupal/site-pages/index.html')||!listing.includes('drupal/web/.htaccess')||!listing.includes('manifest.json'))throw new Error('Contenu de l’archive invalide.');
  return listing.length;
}

export async function packageDrupalCandidate(projectRoot=root){
  const source=resolve(projectRoot,'drupal'),privateBase=resolve(projectRoot,'.local');
  await stat(resolve(source,'vendor/autoload.php'));
  await stat(resolve(source,'web/core/lib/Drupal.php'));
  const pagesSource=await buildDrupalPublicPages(projectRoot);
  const pagesManifest=JSON.parse(await readFile(resolve(privateBase,'drupal-public-candidate/manifest.json'),'utf8'));
  const stage=await mkdtemp(resolve(privateBase,'drupal-package-'));
  const target=resolve(stage,'drupal');
  await mkdir(target,{recursive:true});
  for(const name of ['composer.json','composer.lock'])await copyFile(resolve(source,name),resolve(target,name));
  const tracked=(await run('git',['ls-files','-z','--','drupal/web'],{cwd:projectRoot,maxBuffer:20*1024*1024})).stdout.toString('utf8').split('\0').filter(Boolean);
  for(const name of tracked){
    const from=resolve(projectRoot,name),to=resolve(target,name.slice('drupal/'.length));
    if(!inside(resolve(projectRoot,'drupal/web'),from)||!inside(resolve(target,'web'),to))throw new Error('Chemin de livraison inattendu.');
    await mkdir(dirname(to),{recursive:true});await copyFile(from,to);
  }
  await cp(resolve(source,'vendor'),resolve(target,'vendor'),{recursive:true});
  await cp(resolve(source,'web/core'),resolve(target,'web/core'),{recursive:true});
  await mkdir(resolve(target,'site-pages'),{recursive:true});
  for(const page of pages){const name=page+'.html',data=await readFile(resolve(pagesSource,name));if(digest(data)!==pagesManifest.pages[name]?.sha256)throw new Error('Page candidate modifiée pendant le paquet : '+name);await writeFile(resolve(target,'site-pages',name),data);}
  await mkdir(resolve(target,'config'),{recursive:true});
  await copyFile(resolve(source,'config/settings.hosting.example.php'),resolve(target,'config/settings.hosting.example.php'));
  const status=(await run('git',['status','--porcelain'],{cwd:projectRoot})).stdout.toString('utf8').trim();
  const commit=(await run('git',['rev-parse','HEAD'],{cwd:projectRoot})).stdout.toString('utf8').trim();
  const manifest={createdAt:new Date().toISOString(),kind:'candidat Drupal non configuré et non déployé',sourceCommit:commit,sourceClean:status==='',pages:pagesManifest.pages,missingByDesign:['web/sites/default/settings.php','base SQL','secrets et comptes','répertoires privés','certificat et cible HTTPS']};
  await writeFile(resolve(stage,'manifest.json'),JSON.stringify(manifest,null,2)+'\n');
  const zip=resolve(privateBase,'tc-longages-drupal-v1-candidat.zip');
  await run('tar',['-a','-cf',zip,'drupal','manifest.json'],{cwd:stage,maxBuffer:1024*1024});
  return {zip,manifest,files:await verifyDrupalCandidateZip(zip)};
}

if(process.argv[1]&&resolve(process.argv[1])===fileURLToPath(import.meta.url)){
  if(process.argv.includes('--verify')){
    const zip=resolve(root,'.local/tc-longages-drupal-v1-candidat.zip');
    console.log('Archive vérifiée : '+zip+' ('+await verifyDrupalCandidateZip(zip)+' entrées).');
  }
  else{
    const result=await packageDrupalCandidate();
    console.log('Candidat local non configurable en l’état : '+result.zip+' ('+result.files+' entrées). Aucune livraison distante.');
  }
}
