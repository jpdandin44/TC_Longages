import { readFile, writeFile, readdir, mkdir, mkdtemp, lstat, rename, rm, rmdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import path from 'node:path';

const root = fileURLToPath(new URL('../', import.meta.url));
const names = Object.freeze(['index.html', 'competitions.html', 'calendrier.html', 'disponibilites.html', 'equipes.html', 'espace.html', 'contact.html', 'robots.txt', '.htaccess', 'maintenance.active']);
const digest = data => createHash('sha256').update(data).digest('hex');
const diskPath = value => path.resolve(value instanceof URL ? fileURLToPath(value) : value);
const exactNames = entries => entries.length === names.length && entries.every(name => names.includes(name));

export async function validateOfficialPackage({ directory = path.join(root, 'officiel'), manifestFile = path.join(root, 'data', 'officiel-manifest.json') } = {}) {
  const folder = diskPath(directory);
  const entries = await readdir(folder);
  if (!exactNames(entries)) throw new Error('L’aperçu doit contenir exactement les dix fichiers autorisés, dont maintenance.active. Archivage refusé.');
  const manifest = JSON.parse(await readFile(manifestFile, 'utf8'));
  if (manifest.mode !== 'local-review-only' || !manifest.files || !exactNames(Object.keys(manifest.files))) throw new Error('Manifeste d’aperçu invalide. Régénérez les fichiers avant archivage.');
  const files = new Map();
  for (const name of names) {
    const filename = path.join(folder, name);
    const info = await lstat(filename);
    if (!info.isFile() || info.isSymbolicLink()) throw new Error('Un fichier autorisé est remplacé par un dossier ou un lien. Archivage refusé.');
    const data = await readFile(filename);
    if (manifest.files[name]?.bytes !== data.length || manifest.files[name]?.sha256 !== digest(data)) throw new Error('Fichier modifié depuis sa construction : ' + name + '. Régénérez l’aperçu.');
    files.set(name, data);
  }
  return files;
}

export async function packageOfficial({ directory = path.join(root, 'officiel'), manifestFile = path.join(root, 'data', 'officiel-manifest.json'), outputDirectory = path.join(root, 'livrables') } = {}) {
  const files = await validateOfficialPackage({ directory, manifestFile });
  if (process.platform !== 'win32') throw new Error('La création de cette archive utilise ZIP/.NET sous Windows.');
  const outputFolder = diskPath(outputDirectory);
  await mkdir(outputFolder, { recursive: true });
  const staging = await mkdtemp(path.join(outputFolder, '.official-package-'));
  const archiveName = 'tc-longages-officiel-apercu.zip';
  const archive = path.join(outputFolder, archiveName);
  const stagedArchive = path.join(staging, archiveName);
  const packageManifestName = 'tc-longages-officiel-apercu.manifest.json';
  const stagedManifest = path.join(staging, packageManifestName);
  try {
    // Archive verified bytes, not files that a concurrent build could replace.
    for (const [name, data] of files) await writeFile(path.join(staging, name), data, { flag: 'wx' });
    const quote = value => "'" + value.replaceAll("'", "''") + "'";
    const command = [
      "$ErrorActionPreference = 'Stop'",
      'Add-Type -AssemblyName System.IO.Compression',
      'Add-Type -AssemblyName System.IO.Compression.FileSystem',
      '$taskZipStream = [System.IO.File]::Open(' + quote(stagedArchive) + ', [System.IO.FileMode]::CreateNew)',
      '$taskZipArchive = [System.IO.Compression.ZipArchive]::new($taskZipStream, [System.IO.Compression.ZipArchiveMode]::Create)',
      'try {',
      ...names.map(name => '[System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($taskZipArchive, ' + quote(path.join(staging, name)) + ', ' + quote(name) + ', [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null'),
      '} finally { $taskZipArchive.Dispose(); $taskZipStream.Dispose() }'
    ].join('\n');
    execFileSync('powershell.exe', ['-NoProfile', '-NonInteractive', '-EncodedCommand', Buffer.from(command, 'utf16le').toString('base64')], { stdio: 'pipe', windowsHide: true, timeout: 30000 });
    const zip = await readFile(stagedArchive);
    const manifest = {
      mode: 'local-review-only',
      maintenance: 'unconditional-503',
      archive: { file: archiveName, bytes: zip.length, sha256: digest(zip) },
      files: Object.fromEntries([...files].map(([name, data]) => [name, { bytes: data.length, sha256: digest(data) }]))
    };
    await writeFile(stagedManifest, JSON.stringify(manifest, null, 2) + '\n', { flag: 'wx' });
    await rename(stagedArchive, archive);
    await rename(stagedManifest, path.join(outputFolder, packageManifestName));
    return manifest;
  } finally {
    // Only exact files created in this mkdtemp directory are removed. No recursive
    // delete and no computed parent-directory removal are performed.
    for (const name of [...names, archiveName, packageManifestName]) await rm(path.join(staging, name), { force: true });
    await rmdir(staging);
  }
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  try {
    const manifest = await packageOfficial();
    console.log(`Archive de revue : ${path.join(root, 'livrables', manifest.archive.file)}\nDix fichiers, maintenance.active obligatoire. Fermeture Apache 503 inconditionnelle : renommer le témoin n’ouvre rien. Manifeste séparé. Aucun transfert ni publication.`);
  } catch (error) {
    console.error(error.message);
    process.exitCode = 1;
  }
}
