import { readFile, writeFile, mkdir, readdir } from 'node:fs/promises';
import { createHash } from 'node:crypto';

// Consomme uniquement les pages autonomes de demo:build. Aucune publication.
const root = new URL('../', import.meta.url);
const destination = new URL('demo-o2switch/', root);
const pages = ['index.html', 'adherer.html', 'parcours.html', 'bureau.html', 'inscriptions.html', 'communication.html', 'actualites-bureau.html'];
const filenames = [...pages, 'robots.txt', '.htaccess', 'maintenance.active'];
const privatePagePattern = /(?:parcours|bureau|inscriptions|communication|actualites-bureau)\.html/i;
const publicRibbon = '<aside class="demo-ribbon" aria-label="Mode démonstration"><div><strong>DÉMONSTRATION</strong><span>Essais fictifs · aucune inscription ni diffusion réelle</span><a href="./index.html">Le club</a><a href="./adherer.html">Essayer le formulaire</a></div></aside>';
const internalRibbon = '<aside class="demo-ribbon" aria-label="Mode démonstration"><div><strong>DÉMONSTRATION</strong><span>Accès par lien · sans authentification · essais fictifs et visuels fournis</span><a href="./index.html">Le club</a><a href="./parcours.html">Visite guidée</a><a href="./adherer.html">Formulaire</a><a href="./bureau.html">Bureau</a><a href="./inscriptions.html">Inscriptions</a><a href="./communication.html">Communication</a></div></aside>';

function replaceOne(html, pattern, replacement, label) {
  const matches = [...html.matchAll(new RegExp(pattern.source, pattern.flags.includes('g') ? pattern.flags : pattern.flags + 'g'))];
  if (matches.length !== 1) throw new Error(`${label} : un emplacement attendu, ${matches.length} trouvé(s). Relancez demo:build et vérifiez la source.`);
  return html.replace(pattern, replacement);
}
function preparePublicForm(html) {
  html = replaceOne(html, /<a class="back-link" href="\.\/parcours\.html">[\s\S]*?<\/a>/, '<a class="back-link" href="./index.html">Revenir au club <span aria-hidden="true">↗</span></a>', 'Retour du formulaire');
  html = replaceOne(html, /<a id="success-bureau-link"[^>]*>[\s\S]*?<\/a>/, '<p id="success-bureau-link" class="field-hint">Cette demande reste une simulation dans ce navigateur. Aucun dossier réel n’est transmis au club.</p>', 'Message après dépôt fictif');
  html = replaceOne(html, /[ \t]*byId\('success-bureau-link'\)\.href = `\.\/inscriptions\.html\?dossier=\$\{encodeURIComponent\(registration\.id\)\}`;/, '', 'Suppression du lien bureau créé après dépôt');
  html = html.replace(/<a\b[^>]*\bhref="\.\/(?:parcours|bureau|inscriptions|communication|actualites-bureau)\.html(?:[?#][^"]*)?"[^>]*>[\s\S]*?<\/a>/g, '');
  html = replaceOne(html, /<noscript>[\s\S]*?<\/noscript>/, '<noscript><p class="inline-notice">Activez JavaScript pour essayer ce formulaire avec des données fictives uniquement.</p></noscript>', 'Aide sans JavaScript');
  html = html.replace('Relisez votre demande fictive avant de la retrouver du côté du bureau.', 'Relisez votre demande fictive avant de terminer la simulation.');
  html = html.replace('Passez côté bureau pour examiner ce dossier, comparer les disponibilités et comprendre la suite.', 'La simulation est terminée. Le fonctionnement réel prévoira ensuite un contrôle du dossier par le club.');
  html = html.replaceAll('Revenez à la présentation du processus.', 'Revenez à la page du club puis réessayez le formulaire.');
  html = html.replaceAll('Réessayez depuis la visite guidée.', 'Rechargez ce formulaire pour réessayer.');
  html = html.replaceAll('Autorisez le stockage local pour essayer la continuité avec le tableau du bureau.', 'Les essais restent uniquement dans ce navigateur.');
  html = html.replaceAll('Démonstration locale', 'Démonstration');
  return html;
}

const outputs = {};
for (const page of pages) {
  let html;
  try { html = await readFile(new URL(`prototype/${page}`, root), 'utf8'); }
  catch (error) { throw new Error(`Page prototype/${page} introuvable : exécutez npm.cmd run demo:build avant ce script.`, { cause: error }); }
  const publicPage = page === 'index.html' || page === 'adherer.html';
  html = replaceOne(html, /<aside class="demo-ribbon"[^>]*>[\s\S]*?<\/aside>/, publicPage ? publicRibbon : internalRibbon, `Bannière ${page}`);
  html = html.replace(/<meta\b[^>]*\bname="robots"[^>]*>\s*/gi, '');
  html = replaceOne(html, /<\/head>/, '<meta name="robots" content="noindex,nofollow">\n</head>', `Indexation ${page}`);
  html = html.replaceAll('tcl.demo.inscriptions.v1', 'tcl.hosted-demo.inscriptions.v1').replaceAll('tcl.demo.communication.v1', 'tcl.hosted-demo.communication.v1');
  if (page === 'index.html') {
    html = replaceOne(html, /(<div class="join-intro">[\s\S]*?)(<\/div>)/, '$1<a class="button button-light" href="./adherer.html">Essayer le formulaire fictif <span aria-hidden="true">↗</span></a>$2', 'Bouton formulaire dans la section Adhérer');
  }
  if (page === 'adherer.html') html = preparePublicForm(html);
  // Le contrôle inclut le JavaScript : aucun lien de gestion ne peut être réintroduit au dépôt.
  if (publicPage && privatePagePattern.test(html)) throw new Error(`Une adresse interne subsiste dans ${page}. Génération interrompue.`);
  if (/tcl\.demo\.|['"]tcl\.communication\.v1['"]/.test(html)) throw new Error(`Un espace de stockage non isolé subsiste dans ${page}.`);
  if (/<script[^>]+src=|<link[^>]+rel="stylesheet"|src="(?!data:)/.test(html)) throw new Error(`La page ${page} n’est pas autonome. Relancez demo:build.`);
  if (!html.includes('window.TCL_DEMONSTRATION = true;')) throw new Error(`Les garde-fous de démonstration manquent dans ${page}.`);
  outputs[page] = html;
}
outputs['robots.txt'] = 'User-agent: *\nDisallow: /\n';
outputs['.htaccess'] = `# Copie de demonstration uniquement. Aucun mecanisme d'authentification.
Options -Indexes
DirectoryIndex index.html

# Ferme par defaut. Renommer maintenance.active en maintenance.inactive pour ouvrir.
RewriteEngine On
RewriteCond %{DOCUMENT_ROOT}/maintenance.active -f
RewriteRule ^ - [R=503,L]
ErrorDocument 503 "Demonstration fermee. Ouverture et fermeture sous le controle du club."

<FilesMatch "^maintenance\\.(active|inactive)$">
  Require all denied
</FilesMatch>

<IfModule mod_headers.c>
  Header always set Cache-Control "no-store"
  Header always set X-Robots-Tag "noindex, nofollow"
</IfModule>
`;
outputs['maintenance.active'] = 'Demonstration fermee par defaut. Renommer ce fichier en maintenance.inactive pour ouvrir.\n';

await mkdir(destination, { recursive: true });
const unexpected = (await readdir(destination)).filter(name => !filenames.includes(name));
if (unexpected.length) throw new Error(`La sortie contient des fichiers inattendus (${unexpected.join(', ')}). Aucun fichier supprimé : vérifiez demo-o2switch/ avant de poursuivre.`);
const manifest = { purpose: 'hosted-fictional-demonstration-only', defaultState: 'maintenance-closed', authentication: 'none', files: {} };
for (const [name, content] of Object.entries(outputs)) {
  await writeFile(new URL(name, destination), content, 'utf8');
  manifest.files[name] = { bytes: Buffer.byteLength(content), sha256: createHash('sha256').update(content).digest('hex') };
}
await mkdir(new URL('data/', root), { recursive: true });
await writeFile(new URL('data/hosted-demo-manifest.json', root), JSON.stringify(manifest, null, 2) + '\n');
console.log('Démonstration statique préparée dans demo-o2switch/ : 7 pages, robots.txt, .htaccess et maintenance.active. Fermée par défaut ; aucun transfert externe.');
