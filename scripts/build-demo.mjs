import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { inline } from './inline-html.mjs';
import { createHash } from 'node:crypto';

const root = new URL('../', import.meta.url);
const out = new URL('prototype/', root);
await mkdir(out, { recursive: true });
const banner = `<aside class="demo-ribbon" aria-label="Mode démonstration"><div><strong>DÉMONSTRATION</strong><span>Essais fictifs · aucune inscription ni diffusion réelle</span><a href="./parcours.html">Visite guidée →</a><a href="./adherer.html">Formulaire</a><a href="./inscriptions.html">Gestion bureau</a></div></aside>`;
const ribbonCSS = `.demo-ribbon{background:#d6e798;color:#153c32;font:12px/1.5 Arial,sans-serif;border-bottom:1px solid #b5c880}.demo-ribbon>div{width:min(1168px,calc(100% - 40px));margin:auto;display:flex;align-items:center;flex-wrap:wrap;gap:8px 18px;padding:11px 0}.demo-ribbon strong{letter-spacing:1.2px;font-size:10px}.demo-ribbon a{font-weight:700;color:inherit;text-decoration:underline;text-underline-offset:3px;min-height:28px;display:inline-flex;align-items:center}.demo-ribbon span{margin-right:auto}@media(max-width:600px){.demo-ribbon span{flex-basis:100%;order:1}.demo-ribbon a{font-size:11px}}`;
const pages = { 'index.html':'index', 'parcours.html':'demo-parcours', 'adherer.html':'demo-adherer', 'bureau.html':'bureau', 'inscriptions.html':'demo-inscriptions', 'communication.html':'communication', 'actualites-bureau.html':'actualites-bureau' };
const manifest = { purpose: 'fictional-demonstration-only', pages: {} };
for (const [name, source] of Object.entries(pages)) {
  let html = await readFile(new URL(`src/${source}.html`, root), 'utf8');
  html = html.replace(/<div class="prototype-banner">[\s\S]*?<\/div>\s*<\/div>/g, '');
  html = html.replace('</head>', `<style>${ribbonCSS}</style><script defer src="./demo-mode.js"></script></head>`);
  html = html.replace(/<body([^>]*)>/, `<body$1>${banner}`);
  if (name === 'bureau.html') html = html.replace('Les inscriptions,<br><em>bientôt ici.</em>', 'Les inscriptions,<br><em>pas à pas.</em>').replace('Une page dédiée est prévue pour le bureau. Son contenu sera précisé lors d’une prochaine étape.', 'Examinez des demandes fictives, comparez les disponibilités et essayez la constitution des groupes.').replace('Voir la page inscriptions', 'Tester la gestion');
  if (name === 'index.html') {
    html = html.replace(/<!-- PROTOTYPE:START -->[\s\S]*?<!-- PROTOTYPE:END -->/g, '');
    html = html.replace('</head>', '<meta name="robots" content="noindex,nofollow"></head>');
  }
  if (name === 'communication.html') {
    html = html.replace('<script defer src="./communication.js"></script>', '<script defer src="./demo-communication-seed.js"></script><script defer src="./communication.js"></script>');
    const poster = await readFile(new URL('Affiche.jpeg', root));
    const slot = '<div id="demo-image-example-slot"></div>';
    if (!html.includes(slot)) throw new Error('Zone de l’affiche exemple absente de la page Communication.');
    html = html.replace(slot, () => `<div id="demo-image-example-slot"><button type="button" class="text-button" id="use-example-poster">Utiliser l’affiche exemple</button><p class="field-help">Exemple fourni par le club pour cette présentation. Son ajout ne publie rien.</p><template id="demo-poster-data">data:image/jpeg;base64,${poster.toString('base64')}</template></div>`);
    html = html.replace('</head>', '<script defer src="./demo-communication-example.js"></script></head>');
    html = html.replace(/<p class="storage-note">[\s\S]*?<\/p>/, '<p class="storage-note">Démonstration avec dossiers fictifs et visuels fournis pour la présentation. Les textes et images ajoutés restent dans ce navigateur, sans envoi au serveur. Facebook, WhatsApp, ADOC et la copie sont simulés ici. N’ajoutez aucune donnée confidentielle ; les comptes du bureau restent à configurer séparément.</p>');
    html = html.replace('type="file" accept="application/json,.json"', 'type="file" accept="application/json,.json" disabled title="Import de fichiers réels indisponible dans la visite"');
    html = html.replace(/<p>Après validation, ouvrez WhatsApp[\s\S]*?<\/p>/, '<p>Essayez les boutons après validation : ils affichent une simulation. Aucun message ne quitte cette démonstration.</p>');
    html = html.replace('Ouvrir WhatsApp ↗', 'Simuler WhatsApp ↗').replace('>Copier le message</button>', '>Simuler la copie</button>');
    html = html.replace('WHATSAPP · PARTAGE MANUEL', 'WHATSAPP · SIMULATION');
  }
  html = await inline(html);
  html = html.replaceAll('tcl.communication.v1', 'tcl.demo.communication.v1');
  html = html.replaceAll("$('import-posts').disabled = storageBlocked;", "$('import-posts').disabled = true;");
  html = html.replaceAll('Cet espace est réservé au bureau', 'Démonstration de l’espace bureau');
  // Keep dynamic editor guidance consistent with its intercepted demo buttons.
  html = html.replaceAll('Prêt à partager. Ouvrir WhatsApp transmet ce texte à WhatsApp ; vous devrez encore choisir un groupe et confirmer Envoyer.', 'Prêt à simuler. Aucun message ne sera transmis dans cette démonstration.');
  html = html.replaceAll('Texte préparé pour WhatsApp. Après validation, vous choisissez les groupes et confirmez l’envoi dans WhatsApp. Aucun relais automatique.', 'Aperçu WhatsApp : les boutons simulent la suite du parcours, sans ouvrir WhatsApp.');
  html = html.replaceAll('Aperçu du message et de l’affiche. Le raccourci WhatsApp ne transmet que le texte ; il faudra joindre l’image séparément. Aucun relais automatique.', 'Aperçu du message et de l’affiche : les boutons simulent la suite du parcours. Aucun texte ni image ne sera transmis à WhatsApp.');
  html = html.replaceAll(' L’affiche n’est pas jointe automatiquement : ajoutez-la vous-même dans WhatsApp.', ' Cette démonstration ne transmet ni le texte ni l’affiche.');
  html = html.replaceAll('Le message WhatsApp est prêt pour un partage manuel dans vos groupes.', 'Le message WhatsApp est prêt pour une simulation sans envoi.');
  html = html.replaceAll('Le groupe et l’envoi se choisissent dans WhatsApp', 'Aperçu fictif · aucun groupe contacté');
  await writeFile(new URL(name, out), html);
  manifest.pages[name] = { bytes: Buffer.byteLength(html), sha256: createHash('sha256').update(html).digest('hex') };
}
await writeFile(new URL('serveur-demo.mjs', out), await readFile(new URL('scripts/demo-server.mjs', root)));
await writeFile(new URL('ouvrir-prototype.cmd', out), '@echo off\r\ncd /d "%~dp0"\r\nwhere node >nul 2>nul\r\nif errorlevel 1 (\r\n echo Node.js 22 ou plus recent est necessaire pour cette visite locale.\r\n pause\r\n exit /b 1\r\n)\r\necho Ouvrez http://127.0.0.1:4174/ dans votre navigateur.\r\nnode serveur-demo.mjs\r\npause\r\n');
await writeFile(new URL('lisez-moi.md', out), await readFile(new URL('docs/visiter-prototype.md', root)));
await writeFile(new URL('manifest.json', out), JSON.stringify(manifest, null, 2) + '\n');
console.log('Sept pages de démonstration générées dans prototype/. Données fictives et diffusion simulée. Aucun transfert externe.');
