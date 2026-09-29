import { readFile, writeFile, mkdir, readdir, lstat } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import { officialPages } from './official-config.mjs';
import { validateOfficialPackage } from './package-officiel.mjs';

const root = fileURLToPath(new URL('../', import.meta.url));
export const hostedOfficialNames = Object.freeze([...officialPages, 'robots.txt', '.htaccess', 'maintenance.active']);
export const hostedOfficialMode = 'hosted-official-demonstration-only';
const digest = bytes => createHash('sha256').update(bytes).digest('hex');
const diskPath = value => path.resolve(value instanceof URL ? fileURLToPath(value) : value);
const ribbon = '<aside class="official-ribbon" aria-label="Mode démonstration"><div class="container"><strong>DÉMONSTRATION V1</strong><span>Version de test · aucun envoi de formulaire ni connexion aux espaces privés.</span></div></aside>';

export const hostedOfficialHtaccess = `# Demonstration V1 statique. Aucun compte ou service de production.
# Installer uniquement a la racine documentaire du sous-domaine dedie.
Options -Indexes -MultiViews
DirectoryIndex index.html
RewriteEngine On

# Les methodes de modification sont refusees, y compris lorsque la demo est ouverte.
RewriteCond %{REQUEST_METHOD} !^(GET|HEAD)$
RewriteRule ^ - [R=405,L]

# Fermee si active existe OU si inactive manque. Deux temoins = fermee.
# Ouvrir volontairement : renommer maintenance.active en maintenance.inactive.
RewriteCond %{DOCUMENT_ROOT}/maintenance.active -f [OR]
RewriteCond %{DOCUMENT_ROOT}/maintenance.active -d [OR]
RewriteCond %{DOCUMENT_ROOT}/maintenance.inactive !-f
RewriteRule ^ - [R=503,L]
ErrorDocument 503 "Demonstration V1 fermee. Ouverture et fermeture sous le controle du club."
ErrorDocument 404 "Page absente de cette demonstration V1."
ErrorDocument 405 "Seules les consultations GET et HEAD sont autorisees."

# Seuls les sept ecrans et robots.txt sont accessibles. Les anciens fichiers,
# meme restes sur le serveur, ne font pas partie de cette presentation.
RewriteRule !^(|index\\.html|competitions\\.html|calendrier\\.html|disponibilites\\.html|equipes\\.html|espace\\.html|contact\\.html|robots\\.txt)$ - [R=404,L]

<FilesMatch "^(?:maintenance\\.(active|inactive)|\\.htaccess)$">
  Require all denied
</FilesMatch>
<IfModule mod_headers.c>
  Header always set Cache-Control "no-store"
  Header always set X-Robots-Tag "noindex, nofollow"
  Header always set X-Content-Type-Options "nosniff"
  Header always set X-Frame-Options "DENY"
  Header always set Referrer-Policy "no-referrer"
  Header always set Content-Security-Policy "default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; img-src data:; connect-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'"
  Header always set Allow "GET, HEAD" "expr=%{REQUEST_STATUS} == 405"
</IfModule>
`;

async function regularDirectory(directory, label) {
  const info = await lstat(directory);
  if (!info.isDirectory() || info.isSymbolicLink()) throw new Error(label + ' doit être un dossier réel, sans lien symbolique.');
}

export async function buildOfficialHostedDemo({
  sourceDirectory = path.join(root, 'officiel'),
  sourceManifestFile = path.join(root, 'data', 'officiel-manifest.json'),
  directory = path.join(root, 'officiel-demo-o2switch'),
  manifestFile = path.join(root, 'data', 'officiel-hosted-demo-manifest.json')
} = {}) {
  const sourceFolder = diskPath(sourceDirectory);
  const destination = diskPath(directory);
  if (sourceFolder === destination) throw new Error('La démonstration doit rester distincte de l’aperçu officiel.');
  await regularDirectory(sourceFolder, 'La source');
  const sourceFiles = await validateOfficialPackage({ directory: sourceFolder, manifestFile: sourceManifestFile });
  const outputs = new Map();
  for (const name of officialPages) {
    const html = sourceFiles.get(name).toString('utf8');
    const pattern = /<aside class="official-ribbon"[^>]*>[\s\S]*?<\/aside>/g;
    if ([...html.matchAll(pattern)].length !== 1 || !html.includes('SITE EN PRÉPARATION')) throw new Error('Bannière officielle source inattendue : ' + name);
    const result = html.replace(pattern, () => ribbon);
    if (/<script[^>]+src=|<link[^>]+rel="stylesheet"|src="(?!data:)/.test(result)) throw new Error('Page non autonome : ' + name);
    if (/localStorage|sessionStorage|TCLDemo|type="password"|(?:bureau|inscriptions|communication|adherer)\.html|XMLHttpRequest|navigator\.sendBeacon|\bfetch\(/.test(result)) throw new Error('Donnée ou fonction privée inattendue : ' + name);
    if (!result.includes('<meta name="robots" content="noindex,nofollow">')) throw new Error('Consigne noindex absente : ' + name);
    outputs.set(name, Buffer.from(result, 'utf8'));
  }
  outputs.set('robots.txt', Buffer.from('User-agent: *\nDisallow: /\n'));
  outputs.set('.htaccess', Buffer.from(hostedOfficialHtaccess));
  outputs.set('maintenance.active', Buffer.from('Demonstration V1 fermee par defaut. Renommer ce fichier en maintenance.inactive pour ouvrir volontairement.\n'));
  await mkdir(destination, { recursive: true });
  await regularDirectory(destination, 'La destination');
  const existing = await readdir(destination);
  if (existing.some(name => !hostedOfficialNames.includes(name))) throw new Error('Fichiers inattendus dans la sortie de démonstration V1. Aucun fichier supprimé.');
  for (const name of existing) {
    const info = await lstat(path.join(destination, name));
    if (!info.isFile() || info.isSymbolicLink()) throw new Error('Fichier de sortie remplacé par un dossier ou un lien : ' + name);
  }
  const manifest = {
    mode: hostedOfficialMode,
    target: 'tclongages.daje3540.odns.fr',
    defaultState: 'maintenance-closed',
    maintenance: 'active-or-missing-inactive-503',
    authentication: 'none',
    contact: 'preview-only',
    files: {}
  };
  for (const [name, data] of outputs) {
    await writeFile(path.join(destination, name), data);
    manifest.files[name] = { bytes: data.length, sha256: digest(data) };
  }
  await mkdir(path.dirname(diskPath(manifestFile)), { recursive: true });
  await writeFile(manifestFile, JSON.stringify(manifest, null, 2) + '\n');
  return manifest;
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  try {
    await buildOfficialHostedDemo();
    console.log('Démonstration V1 préparée : officiel-demo-o2switch/, sept pages et trois fichiers techniques. Fermeture par défaut ; aucun transfert externe.');
  } catch (error) {
    console.error(error.message);
    process.exitCode = 1;
  }
}
