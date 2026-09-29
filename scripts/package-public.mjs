import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';

const root = new URL('../', import.meta.url);
const source = new URL('release/index.html', root);
const html = await readFile(source, 'utf8');
// Only this named file is allowed; never archive an entire project directory.
if (!html.includes('<html lang="fr">') || /localStorage|PROTOTYPE|TCLDemo|(?:bureau|communication|inscriptions|adherer|parcours)\.html|<form\b|<script[^>]+src=|<link[^>]+rel="stylesheet"|src="(?!data:)/i.test(html)) {
  throw new Error('Vitrine non autonome ou contenu interne détecté : archive publique refusée.');
}
if (process.platform !== 'win32') throw new Error('La création du ZIP utilise .NET sous Windows ; le HTML livré fonctionne sur un hébergement statique.');
await mkdir(new URL('livrables/', root), { recursive: true });
const archive = new URL('livrables/tc-longages-vitrine-o2switch.zip', root);
const quote = value => "'" + value.replaceAll("'", "''") + "'";
const command = [
  "$ErrorActionPreference = 'Stop'",
  'Add-Type -AssemblyName System.IO.Compression',
  'Add-Type -AssemblyName System.IO.Compression.FileSystem',
  '$taskZipStream = [System.IO.File]::Open(' + quote(fileURLToPath(archive)) + ', [System.IO.FileMode]::Create)',
  '$taskZipArchive = [System.IO.Compression.ZipArchive]::new($taskZipStream, [System.IO.Compression.ZipArchiveMode]::Create)',
  'try {',
  '[System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($taskZipArchive, ' + quote(fileURLToPath(source)) + ", 'index.html', [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null",
  '} finally { $taskZipArchive.Dispose(); $taskZipStream.Dispose() }'
].join('\n');
execFileSync('powershell.exe', ['-NoProfile', '-Command', command], { stdio: 'inherit', windowsHide: true });
const sha256 = data => createHash('sha256').update(data).digest('hex');
const zip = await readFile(archive);
const manifest = {
  mode: 'prepared-only',
  scope: 'public-vitrine-only',
  archive: { file: 'tc-longages-vitrine-o2switch.zip', bytes: zip.length, sha256: sha256(zip) },
  entries: [{ file: 'index.html', source: 'release/index.html', bytes: Buffer.byteLength(html), sha256: sha256(html) }]
};
await writeFile(new URL('livrables/tc-longages-vitrine-o2switch.manifest.json', root), JSON.stringify(manifest, null, 2) + '\n');
console.log(`Archive préparée : ${fileURLToPath(archive)}\nUn seul fichier public : index.html. Aucun transfert ni changement sur o2switch.`);
