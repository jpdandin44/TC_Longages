import { readFile, writeFile, readdir, mkdir, mkdtemp, lstat, rename, rm, rmdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import path from 'node:path';
import { hostedOfficialNames as names, hostedOfficialMode as mode, hostedOfficialHtaccess } from './build-officiel-hosted-demo.mjs';

const root = fileURLToPath(new URL('../', import.meta.url));
const digest = data => createHash('sha256').update(data).digest('hex');
const diskPath = value => path.resolve(value instanceof URL ? fileURLToPath(value) : value);
const exactNames = entries => entries.length === names.length && entries.every(name => names.includes(name));

export async function validateOfficialHostedPackage({ directory = path.join(root, 'officiel-demo-o2switch'), manifestFile = path.join(root, 'data', 'officiel-hosted-demo-manifest.json') } = {}) {
  const folder = diskPath(directory);
  const folderInfo = await lstat(folder);
  if (!folderInfo.isDirectory() || folderInfo.isSymbolicLink()) throw new Error('Le dossier de démonstration ne doit pas être un lien.');
  const entries = await readdir(folder);
  if (!exactNames(entries)) throw new Error('La démonstration doit contenir exactement les dix fichiers autorisés, dont maintenance.active. Archivage refusé.');
  const manifest = JSON.parse(await readFile(manifestFile, 'utf8'));
  if (manifest.mode !== mode || manifest.defaultState !== 'maintenance-closed' || manifest.authentication !== 'none' || manifest.contact !== 'preview-only' || manifest.maintenance !== 'active-or-missing-inactive-503' || !manifest.files || !exactNames(Object.keys(manifest.files))) throw new Error('Manifeste de démonstration V1 invalide. Régénérez les fichiers avant archivage.');
  const files = new Map();
  for (const name of names) {
    const filename = path.join(folder, name);
    const info = await lstat(filename);
    if (!info.isFile() || info.isSymbolicLink()) throw new Error('Un fichier autorisé est remplacé par un dossier ou un lien. Archivage refusé.');
    const data = await readFile(filename);
    if (manifest.files[name]?.bytes !== data.length || manifest.files[name]?.sha256 !== digest(data)) throw new Error('Fichier modifié depuis sa construction : ' + name + '. Régénérez la démonstration.');
    files.set(name, data);
  }
  if (files.get('.htaccess').toString('utf8') !== hostedOfficialHtaccess) throw new Error('La règle de fermeture et de filtrage Apache a changé. Archivage refusé.');
  return files;
}

export async function packageOfficialHostedDemo({ directory = path.join(root, 'officiel-demo-o2switch'), manifestFile = path.join(root, 'data', 'officiel-hosted-demo-manifest.json'), outputDirectory = path.join(root, 'livrables') } = {}) {
  const files = await validateOfficialHostedPackage({ directory, manifestFile });
  if (process.platform !== 'win32') throw new Error('La création de cette archive utilise ZIP/.NET sous Windows.');
  const outputFolder = diskPath(outputDirectory);
  await mkdir(outputFolder, { recursive: true });
  const staging = await mkdtemp(path.join(outputFolder, '.official-hosted-package-'));
  const archiveName = 'tc-longages-v1-demo-o2switch.zip';
  const stagedArchive = path.join(staging, archiveName);
  const packageManifestName = 'tc-longages-v1-demo-o2switch.manifest.json';
  const stagedManifest = path.join(staging, packageManifestName);
  try {
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
      mode,
      target: 'tclongages.daje3540.odns.fr',
      maintenance: 'active-or-missing-inactive-503',
      authentication: 'none',
      contact: 'preview-only',
      archive: { file: archiveName, bytes: zip.length, sha256: digest(zip) },
      files: Object.fromEntries([...files].map(([name, data]) => [name, { bytes: data.length, sha256: digest(data) }]))
    };
    await writeFile(stagedManifest, JSON.stringify(manifest, null, 2) + '\n', { flag: 'wx' });
    await rename(stagedArchive, path.join(outputFolder, archiveName));
    await rename(stagedManifest, path.join(outputFolder, packageManifestName));
    return manifest;
  } finally {
    // Exact files in the mkdtemp folder only. No recursive deletion.
    for (const name of [...names, archiveName, packageManifestName]) await rm(path.join(staging, name), { force: true });
    await rmdir(staging);
  }
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  try {
    const manifest = await packageOfficialHostedDemo();
    console.log(`Archive de démonstration V1 : ${path.join(root, 'livrables', manifest.archive.file)}\nDix fichiers, fermée par défaut. Ouverture manuelle par renommage de maintenance.active en maintenance.inactive. Aucun dépôt ni publication.`);
  } catch (error) {
    console.error(error.message);
    process.exitCode = 1;
  }
}
