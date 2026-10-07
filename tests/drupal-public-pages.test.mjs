import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile,writeFile,mkdir,mkdtemp,rm} from 'node:fs/promises';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import {createHash} from 'node:crypto';
import {publicPage,pages,buildDrupalPublicPages} from '../scripts/build-drupal-public-pages.mjs';

test('Les sept pages Drupal publiques dérivent de la V1 sans bandeau ni noindex de revue',async()=>{
  for(const page of pages){
    const source=await readFile(new URL('../officiel/'+page+'.html',import.meta.url),'utf8');
    const output=publicPage(source);
    assert.match(output,/<main/);
    assert.doesNotMatch(output,/SITE EN PRÉPARATION|<meta name="robots" content="noindex/);
    assert.ok(output.includes('tclongages@gmail.com'));
  }
});

test('Une source sans les gardes attendues est refusée',()=>{
  assert.throws(()=>publicPage('<html>inconnu</html>'),/Source V1 inattendue/);
});

test('Le candidat agenda reste borné à la préproduction et refuse une page altérée',async t=>{
  const root=await mkdtemp(join(tmpdir(),'tcl-month-'));
  t.after(()=>rm(root,{recursive:true,force:true}));
  await mkdir(join(root,'.local/agenda-preview'),{recursive:true});
  const manifest={mode:'local-calendar-preview-only',files:{}};
  for(const page of pages){
    const frame=page==='calendrier'?'<iframe class="club-calendar" src="https://calendar.google.com/calendar/embed?src=fixture%40group.calendar.google.com&amp;ctz=Europe%2FParis&amp;mode=MONTH"></iframe>':'';
    const html='<meta name="robots" content="noindex,nofollow"><aside class="official-ribbon">SITE EN PRÉPARATION</aside><main><h1>Club</h1><p>Présentation</p>'+frame+'</main>';
    await writeFile(join(root,'.local/agenda-preview',page+'.html'),html);
    manifest.files[page+'.html']={sha256:createHash('sha256').update(html).digest('hex')};
  }
  await writeFile(join(root,'.local/agenda-preview-manifest.json'),JSON.stringify(manifest));
  await buildDrupalPublicPages(root,{calendarPreproduction:true});
  const delivery=JSON.parse(await readFile(join(root,'.local/drupal-public-candidate/manifest.json'),'utf8'));
  assert.equal(delivery.targetHost,'preprod.tclongages.fr');
  assert.equal(delivery.productionAllowed,false);
  assert.equal(delivery.calendarSharingReviewed,false);
  assert.equal(delivery.calendarView,'MONTH');
  assert.equal(Object.keys(delivery.pages).length,7);
  await writeFile(join(root,'.local/agenda-preview/calendrier.html'),'tampered');
  await assert.rejects(buildDrupalPublicPages(root,{calendarPreproduction:true}),/différente de son manifeste/);
});
