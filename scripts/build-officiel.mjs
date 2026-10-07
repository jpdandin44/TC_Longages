import { readFile, writeFile, mkdir, readdir } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import { inline } from './inline-html.mjs';
import { loadOfficialConfig, escapeHTML as e, officialPages } from './official-config.mjs';
import { actionPages } from '../src/officiel-pages.mjs';
import { loadCalendarPreview } from './calendar-preview.mjs';
import { withoutCalendarEmbed } from './calendar-embed.mjs';

const root = new URL('../',import.meta.url);
const config = await loadOfficialConfig();
const calendarPreview = process.argv.includes('--calendar-preview') ? await loadCalendarPreview() : null;
const sha = data => createHash('sha256').update(data).digest('hex');
let base = (await readFile(new URL('src/index.html',root),'utf8')).replace(/<!-- PROTOTYPE:START -->[\s\S]*?<!-- PROTOTYPE:END -->/g,'');
// Un contenu partagé avec la démonstration historique ; seules les présentations divergent.
for (const [original,replacement] of [
  ['https://tenup.fft.fr/club/60310230/offres',config.links.offers],
  ['https://tenup.fft.fr/club/60310230',config.links.tenup],
  ['https://www.facebook.com/tc.longages.31',config.links.facebook],
  ['https://www.google.com/maps/search/?api=1&amp;query=Tennis%20Club%20de%20Longages%2035%2C%20chemin%20de%20Muret%2031410',config.links.map],
  ['Tennis Club de Longages',config.club.name],
  ['35, chemin de Muret',config.club.address],['31410 Longages',config.club.postalCity]
]) base=base.replaceAll(original,()=>e(replacement));
base=base.replaceAll('LONGAGES<small>',()=>`${e(config.club.wordmark)}<small>`);
base=base.replaceAll('%23153c32',config.theme.primary.replace('#','%23')).replaceAll('%23d5eb8d',config.theme.soft.replace('#','%23'));
base=base.replace('</head>','<meta name="robots" content="noindex,nofollow"><link rel="stylesheet" href="./officiel.css"><script defer src="./officiel.js"></script></head>');
base=base.replace('<body>','<body class="official-site"><aside class="official-ribbon" aria-label="État du site"><div class="container"><strong>SITE EN PRÉPARATION</strong><span>Aperçu V1 · les services du club seront ouverts après validation.</span></div></aside>');
const nav = `<nav class="main-nav" id="main-nav" aria-label="Navigation principale"><a href="./index.html#le-club">Le club</a><a href="./competitions.html">Compétitions</a><a href="./calendrier.html">Calendrier</a><a href="./disponibilites.html">Disponibilités</a><a href="./equipes.html">Équipes</a><a class="button button-dark nav-cta" href="./contact.html">Contact <span aria-hidden="true">↗</span></a></nav>`;
base=base.replace(/<nav class="main-nav"[\s\S]*?<\/nav>/,()=>nav);
const quick = `<section class="container quick-access" id="acces-rapides" aria-labelledby="quick-title"><div class="quick-access-head"><div><p class="eyebrow">LES ACCÈS DU CLUB</p><h2 id="quick-title">À vous de <em>jouer.</em></h2></div><p>Les informations et les actions utiles,<br>à portée de main.</p></div><div class="quick-grid">${config.quickAccess.map((item,index)=>`<a class="quick-card" href="${e(item.href)}"><span class="quick-kicker">0${index+1}</span><h3>${e(item.label)}</h3><p>${e(item.description)}</p><span class="quick-state">Découvrir <span aria-hidden="true">↗</span></span></a>`).join('')}</div></section>`;
base=base.replace('<div class="values-strip">',()=>quick+'\n<div class="values-strip">');
base=base.replace('Rejoindre le club <span','Jouer au club <span');
const outputs = {'index.html':base};
for (const [name,page] of Object.entries(actionPages(config, { calendarPreview }))) {
  let html=base.replace(/<main id="contenu">[\s\S]*?<\/main>/,()=>`<main id="contenu" class="container action-page">${page.body}</main>`);
  html=html.replace(/<title>[\s\S]*?<\/title>/,()=>`<title>${e(page.title)} · ${e(config.club.name)}</title>`);
  html=html.replace(/href="#([a-z-]+)"/g,'href="./index.html#$1"');
  // Le skip-link reste local à la page d'action.
  html=html.replace('class="skip-link" href="./index.html#contenu"','class="skip-link" href="#contenu"');
  outputs[name]=html;
}
const variables = ':root{' + Object.entries(config.theme).map(([key,value])=>`--club-${key}:${value}`).join(';')+'}';
for (const name of officialPages) {
  let html=await inline(outputs[name]);
  html=html.replace('</head>',`<style data-official-theme>${variables}</style></head>`);
  html=html.replace("(min-width: 761px)","(min-width: 1101px)");
  if (/<script[^>]+src=|<link[^>]+rel="stylesheet"|src="(?!data:)/.test(withoutCalendarEmbed(html, name))) throw new Error('Ressource externe non autorisée : '+name);
  if (/localStorage|TCLDemo|tcl\.demo\.|adherer\.html|inscriptions\.html|communication\.html/.test(html)) throw new Error('Contenu de démonstration métier inattendu : '+name);
  outputs[name]=html;
}
outputs['robots.txt']='User-agent: *\nDisallow: /\n';
outputs['maintenance.active']='Apercu local V1. Aucune autorisation de mise en ligne ou de beta.\n';
outputs['.htaccess']=`# Apercu de revue uniquement ; les acces prives et le domaine ne sont pas actives.
Options -Indexes
DirectoryIndex index.html
RewriteEngine On
# La garde ne peut pas etre desactivee par le renommage d'un temoin de l'ancienne demo.
RewriteRule ^ - [R=503,L]
ErrorDocument 503 "Site en preparation. Ouverture apres validation du club."
<FilesMatch "^(?:maintenance\\.(active|inactive)|\\.htaccess)$">
  Require all denied
</FilesMatch>
<IfModule mod_headers.c>
  Header always set Cache-Control "no-store"
  Header always set X-Robots-Tag "noindex, nofollow"
</IfModule>
`;
const destination=new URL(calendarPreview ? '.local/agenda-preview/' : 'officiel/',root);
await mkdir(destination,{recursive:true});
const unexpected=(await readdir(destination)).filter(name=>!Object.hasOwn(outputs,name));
if (unexpected.length) throw new Error('Fichiers inattendus dans officiel/ : '+unexpected.join(', '));
const manifest={mode:calendarPreview ? 'local-calendar-preview-only' : 'local-review-only',domain:config.domain,domainStatus:config.domainStatus,files:{}};
for (const [name,html] of Object.entries(outputs)) {
  await writeFile(new URL(name,destination),html,'utf8');
  manifest.files[name]={bytes:Buffer.byteLength(html),sha256:sha(html)};
}
await writeFile(new URL(calendarPreview ? '.local/agenda-preview-manifest.json' : 'data/officiel-manifest.json',root),JSON.stringify(manifest,null,2)+'\n');
console.log(calendarPreview ? 'Aperçu local de l’agenda généré dans .local/agenda-preview/. Le navigateur consulte Google Agenda ; aucun transfert du site.' : 'Aperçu officiel généré : 7 pages dans officiel/. Agenda Google affiché seulement si son partage est qualifié. Domaine prévu '+config.domain+' ; aucun transfert, compte ou service externe activé.');
