import { readFile, writeFile, readdir, mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';

const root = new URL('../', import.meta.url);
const names = ['index.html', 'parcours.html', 'adherer.html', 'bureau.html', 'inscriptions.html', 'communication.html', 'actualites-bureau.html', 'robots.txt', '.htaccess', 'maintenance.active'];
const source = new URL('demo-o2switch/', root);
const existing = await readdir(source);
if (existing.length !== names.length || existing.some(name => !names.includes(name))) throw new Error('Contenu inattendu dans demo-o2switch : archivage refusé.');
const manifest = JSON.parse(await readFile(new URL('data/hosted-demo-manifest.json', root), 'utf8'));
const sha256 = data => createHash('sha256').update(data).digest('hex');
for (const name of names) {
  const data = await readFile(new URL(name, source));
  if (manifest.files[name]?.sha256 !== sha256(data)) throw new Error('Reconstruire la démonstration avant archivage : ' + name);
}
if (process.platform !== 'win32') throw new Error('La création automatique du ZIP utilise .NET sous Windows.');
await mkdir(new URL('livrables/', root), { recursive: true });
const output = new URL('livrables/tc-longages-demo-o2switch.zip', root);
const quote = value => "'" + value.replaceAll("'", "''") + "'";
const command = [
  "$ErrorActionPreference = 'Stop'",
  'Add-Type -AssemblyName System.IO.Compression',
  'Add-Type -AssemblyName System.IO.Compression.FileSystem',
  '$taskZipStream = [System.IO.File]::Open(' + quote(fileURLToPath(output)) + ', [System.IO.FileMode]::Create)',
  '$taskZipArchive = [System.IO.Compression.ZipArchive]::new($taskZipStream, [System.IO.Compression.ZipArchiveMode]::Create)',
  'try {',
  ...names.map(name => '[System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($taskZipArchive, ' + quote(fileURLToPath(new URL(name, source))) + ', ' + quote(name) + ', [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null'),
  '} finally { $taskZipArchive.Dispose(); $taskZipStream.Dispose() }'
].join('\n');
execFileSync('powershell.exe', ['-NoProfile', '-Command', command], { stdio: 'inherit', windowsHide: true });
const zip = await readFile(output);
await writeFile(new URL('livrables/tc-longages-demo-o2switch.manifest.json', root), JSON.stringify({
  mode: 'prepared-only', maintenance: 'active-by-default',
  archive: { file: 'tc-longages-demo-o2switch.zip', bytes: zip.length, sha256: sha256(zip) },
  files: manifest.files
}, null, 2) + '\n');
console.log(`Archive de démonstration : ${fileURLToPath(output)}\n10 fichiers autorisés. Fermeture initiale par maintenance.active. Aucun transfert externe.`);
