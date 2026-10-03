import {readFile,writeFile,mkdir} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import {fileURLToPath} from 'node:url';
import {resolve} from 'node:path';

const root=fileURLToPath(new URL('../',import.meta.url));
export const pages=['index','competitions','calendrier','disponibilites','equipes','espace','contact'];

export function publicPage(html) {
  if(!html.includes('<meta name="robots" content="noindex,nofollow">')||!html.includes('SITE EN PRÉPARATION'))throw new Error('Source V1 inattendue : garde de revue absente.');
  const result=html.replace('<meta name="robots" content="noindex,nofollow">','').replace(/<aside class="official-ribbon"[\s\S]*?<\/aside>/,'');
  if(result.includes('SITE EN PRÉPARATION')||result.includes('<meta name="robots" content="noindex'))throw new Error('La page publique conserve un marquage de revue.');
  return result;
}

export async function buildDrupalPublicPages(projectRoot=root) {
  const target=resolve(projectRoot,'.local/drupal-public-candidate/site-pages');
  await mkdir(target,{recursive:true});
  const manifest={purpose:'candidat Drupal V1 public, non déployé',createdAt:new Date().toISOString(),pages:{}};
  for(const page of pages){
    const file=page+'.html';
    const html=publicPage(await readFile(resolve(projectRoot,'officiel',file),'utf8'));
    await writeFile(resolve(target,file),html,'utf8');
    manifest.pages[file]={bytes:Buffer.byteLength(html),sha256:createHash('sha256').update(html).digest('hex')};
  }
  await writeFile(resolve(projectRoot,'.local/drupal-public-candidate/manifest.json'),JSON.stringify(manifest,null,2)+'\n');
  return target;
}

if(process.argv[1]&&resolve(process.argv[1])===fileURLToPath(import.meta.url)){
  await buildDrupalPublicPages();
  console.log('Pages du candidat Drupal générées localement dans .local/drupal-public-candidate/site-pages ; aucun transfert.');
}
