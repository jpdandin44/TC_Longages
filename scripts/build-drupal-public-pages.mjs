import {readFile,writeFile,mkdir} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import {fileURLToPath} from 'node:url';
import {resolve} from 'node:path';

import { withoutCalendarEmbed } from './calendar-embed.mjs';

const root=fileURLToPath(new URL('../',import.meta.url));
export const pages=['index','competitions','calendrier','disponibilites','equipes','espace','contact'];

export function publicPage(html) {
  if(!html.includes('<meta name="robots" content="noindex,nofollow">')||!html.includes('SITE EN PRÉPARATION'))throw new Error('Source V1 inattendue : garde de revue absente.');
  const result=html.replace('<meta name="robots" content="noindex,nofollow">','').replace(/<aside class="official-ribbon"[\s\S]*?<\/aside>/,'');
  if(result.includes('SITE EN PRÉPARATION')||result.includes('<meta name="robots" content="noindex'))throw new Error('La page publique conserve un marquage de revue.');
  return result;
}

export async function buildDrupalPublicPages(projectRoot=root, { calendarPreproduction=false }={}) {
  const target=resolve(projectRoot,'.local/drupal-public-candidate/site-pages');
  await mkdir(target,{recursive:true});
  const sourceDirectory=calendarPreproduction ? '.local/agenda-preview' : 'officiel';
  let preview;
  if(calendarPreproduction){
    preview=JSON.parse(await readFile(resolve(projectRoot,'.local/agenda-preview-manifest.json'),'utf8'));
    if(preview.mode!=='local-calendar-preview-only')throw new Error('Source agenda locale non qualifiée.');
  }
  const manifest={purpose:'candidat Drupal V1 public, non déployé',createdAt:new Date().toISOString(),pages:{}};
  if(calendarPreproduction)Object.assign(manifest,{purpose:'candidat Drupal V1 de préproduction protégée, non déployé',targetHost:'preprod.tclongages.fr',productionAllowed:false,calendarSharingReviewed:false,calendarView:'MONTH'});
  for(const page of pages){
    const file=page+'.html';
    const source=await readFile(resolve(projectRoot,sourceDirectory,file),'utf8');
    if(calendarPreproduction){
      if(createHash('sha256').update(source).digest('hex')!==preview.files[file]?.sha256)throw new Error('Page agenda différente de son manifeste.');
      withoutCalendarEmbed(source,file);
      const frames=(source.match(/<iframe\b/g)||[]).length;
      if(frames!==(page==='calendrier'?1:0))throw new Error('Nombre de cadres agenda inattendu.');
      if(page==='calendrier'&&!source.includes('mode=MONTH'))throw new Error('Vue Mois requise.');
    }
    const html=publicPage(source);
    await writeFile(resolve(target,file),html,'utf8');
    manifest.pages[file]={bytes:Buffer.byteLength(html),sha256:createHash('sha256').update(html).digest('hex')};
  }
  await writeFile(resolve(projectRoot,'.local/drupal-public-candidate/manifest.json'),JSON.stringify(manifest,null,2)+'\n');
  return target;
}

if(process.argv[1]&&resolve(process.argv[1])===fileURLToPath(import.meta.url)){
  await buildDrupalPublicPages(root,{calendarPreproduction:process.argv.includes('--calendar-preproduction')});
  console.log('Pages du candidat Drupal générées localement dans .local/drupal-public-candidate/site-pages ; aucun transfert.');
}
