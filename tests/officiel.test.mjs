import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import { loadOfficialConfig, validateOfficialConfig, officialPages } from '../scripts/official-config.mjs';
import { actionPages } from '../src/officiel-pages.mjs';
import { withoutCalendarEmbed } from '../scripts/calendar-embed.mjs';

const root=new URL('../',import.meta.url);
const read=file=>readFile(new URL(file,root),'utf8');
const sha=bytes=>createHash('sha256').update(bytes).digest('hex');
test('La V1 conserve les visuels exacts et les tarifs actuels tout en appliquant six accès et la charte reçue',async()=>{
  const config=await loadOfficialConfig(),html=await read('officiel/index.html');
  assert.equal(config.domain,'tclongages.fr'); assert.equal(config.domainStatus,'obtained-user-confirmed');
  assert.equal(config.theme.primary,'#a6192e');
  assert.equal((html.match(/class="quick-card"/g)||[]).length,6);
  for(const item of config.quickAccess) assert.ok(html.includes(`href="${item.href}"`));
  const img=html.match(/class="hero-photo"><img src="data:image\/jpeg;base64,([^"]+)"/);
  assert.equal(sha(Buffer.from(img[1],'base64')),sha(await readFile(new URL('Images_Photos/Image_terrain.jpg',root))));
  const logo=html.match(/class="club-logo-image" src="data:image\/jpeg;base64,([^"]+)"/);
  assert.equal(sha(Buffer.from(logo[1],'base64')),sha(await readFile(new URL('Images_Photos/Logo.jpeg',root))));
  assert.equal((html.match(/scope="row"/g)||[]).length,9);
  assert.match(html,/125,00 € ou 150 €/);
});
test('Le paquet officiel reste autonome, non indexable, sans effet de démonstration métier ni référence Google privée',async()=>{
  const manifest=JSON.parse(await read('data/officiel-manifest.json'));
  assert.equal(Object.keys(manifest.files).length,10);
  for(const page of officialPages){
    const html=await read('officiel/'+page);
    assert.equal(sha(html),manifest.files[page].sha256);
    assert.match(html,/<meta name="robots" content="noindex,nofollow">/);
    assert.match(html,/min-width: 1101px/);
    assert.doesNotMatch(html,/ownerAccount|localStorage|TCLDemo|type="password"|(?:inscriptions|communication|adherer|bureau)\.html/);
    assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+rel="stylesheet"|src="(?!data:)|\uFFFD/);
    for(const [,href] of html.matchAll(/href="\.\/([^"#?]+)(?:[?#][^"]*)?"/g)) assert.ok(officialPages.includes(href),page+' -> '+href);
  }
  assert.match(await read('officiel/.htaccess'),/RewriteRule \^ - \[R=503,L\]/);
  assert.doesNotMatch(await read('officiel/.htaccess'),/RewriteCond/);
});
test('Liens Google absents : aucune date, équipe, formulaire ou connexion inventés',async()=>{
  const config=await loadOfficialConfig();
  assert.equal(config.google.calendarUrl,null); assert.deepEqual(config.google.forms,[]);assert.deepEqual(config.teams,[]);
  assert.match(await read('officiel/calendrier.html'),/calendrier arrive bientôt/);
  assert.match(await read('officiel/disponibilites.html'),/Aucun formulaire d’équipe/);
  assert.match(await read('officiel/espace.html'),/connexion n’est pas encore ouverte/);
  assert.match(await read('officiel/contact.html'),/ne transmet et ne conserve aucune donnée/);
});
test('La configuration refuse activation involontaire, URLs dangereuses et effectifs publics',async()=>{
  const config=await loadOfficialConfig();
  for(const change of [
    c=>c.contact.testSupport.status='active',c=>c.contact.testSupport.email='bad',c=>c.noindex=false,c=>c.mode='production',c=>c.contact.mode='send',c=>c.authentication.technology='php',
    c=>c.links.facebook='javascript:alert(1)',c=>c.theme.primary='red;display:none',
    c=>c.google.calendarUrl='https://calendar.google.com/calendar/u/0/r',
    c=>c.teams=[{id:'a',name:'A',category:'Test',tags:[],players:['secret']}],
    c=>c.quickAccess[0].href='./../.env'
  ]){const fixture=structuredClone(config);change(fixture);assert.throws(()=>validateOfficialConfig(fixture));}
});
test('Une ressource Google renseignée doit être revue, liée à son équipe et ne révèle ni joueurs ni feuille de réponses',async()=>{
  const config=await loadOfficialConfig();
  config.teams=[{id:'a',name:'Équipe <test>',category:'Essai',tags:['Tag & essai']}];
  const form={id:'rencontre-test',teamId:'a',label:'Rencontre test',url:'https://docs.google.com/forms/d/e/EXEMPLE/viewform',sharingReviewed:true};
  config.google.forms=[form];
  assert.equal(validateOfficialConfig(config),config);
  const html=actionPages(config)['disponibilites.html'].body;
  assert.match(html,/Équipe &lt;test&gt;/);assert.ok(html.includes(form.url));
  for(const change of [c=>c.google.forms[0].sharingReviewed=false,c=>c.google.forms[0].teamId='b',c=>c.google.forms[0].url='https://docs.google.com/spreadsheets/d/reponses',c=>c.google.forms[0].url='https://docs.google.com/forms/d/EXEMPLE/edit#responses',c=>c.google.forms[0].players=['secret']]){
    const fixture=structuredClone(config);change(fixture);assert.throws(()=>validateOfficialConfig(fixture));
  }
});

test('Le calendrier embarqué exige la revue de partage et limite sa source à Google Agenda',async()=>{
  const config=await loadOfficialConfig();
  assert.equal(config.google.calendarEmbedId,null);
  const pending=structuredClone(config);
  pending.google.calendarEmbedId='club-public@example.invalid';
  assert.throws(()=>validateOfficialConfig(pending));
  assert.doesNotMatch(actionPages(pending)['calendrier.html'].body,/<iframe/);
  pending.google.calendarSharingReviewed=true;
  assert.equal(validateOfficialConfig(pending),pending);
  const html=actionPages(pending)['calendrier.html'].body;
  assert.match(html,/<iframe class="club-calendar" title="Rendez-vous du Tennis Club de Longages"/);
  assert.match(html,/https:\/\/calendar\.google\.com\/calendar\/embed\?src=club-public%40example.invalid/);
  assert.match(html,/ctz=Europe%2FParis&amp;hl=fr&amp;mode=AGENDA/);
  assert.doesNotMatch(withoutCalendarEmbed(html,'calendrier.html'),/<iframe/);
  assert.throws(()=>withoutCalendarEmbed(html,'contact.html'));
  assert.throws(()=>withoutCalendarEmbed(html.replace('https://calendar.google.com/','https://evil.invalid/'),'calendrier.html'));
  for(const id of ['https://evil.invalid', 'secret/private-token', 'bad" onload="alert(1)', 'a@x.invalid?private=1']) {
    const invalid=structuredClone(pending); invalid.google.calendarEmbedId=id;
    assert.throws(()=>validateOfficialConfig(invalid));
  }
});
