import { access, mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';
const root = new URL('../', import.meta.url);
const names = ['index.html','parcours.html','adherer.html','bureau.html','inscriptions.html','communication.html','actualites-bureau.html','serveur-demo.mjs','ouvrir-prototype.cmd','lisez-moi.md','manifest.json'];
const files = names.map(name => fileURLToPath(new URL('prototype/'+name, root)));
for (const file of files) await access(file);
await mkdir(new URL('livrables/',root),{recursive:true});
const output = fileURLToPath(new URL('livrables/tc-longages-prototype.zip',root));
if (process.platform !== 'win32') throw new Error('La création automatique de cette archive utilise la bibliothèque ZIP .NET sous Windows. Les pages et le serveur restent utilisables sur les autres systèmes.');
// Literal PowerShell strings, with escaped apostrophes; no execution-policy change.
const quote = text => "'" + text.replaceAll("'", "''") + "'";
const command = [
  "$ErrorActionPreference = 'Stop'",
  'Add-Type -AssemblyName System.IO.Compression',
  'Add-Type -AssemblyName System.IO.Compression.FileSystem',
  '$taskZipStream = [System.IO.File]::Open('+quote(output)+', [System.IO.FileMode]::Create)',
  '$taskZipArchive = [System.IO.Compression.ZipArchive]::new($taskZipStream, [System.IO.Compression.ZipArchiveMode]::Create)',
  'try {',
  ...files.map((file,index) => '[System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($taskZipArchive, '+quote(file)+', '+quote(names[index])+', [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null'),
  '} finally { $taskZipArchive.Dispose(); $taskZipStream.Dispose() }'
].join('\n');
execFileSync('powershell.exe',['-NoProfile','-Command',command],{stdio:'inherit',windowsHide:true});
console.log(`Archive locale : ${output}\n11 fichiers autorisés uniquement ; aucun compte ni envoi externe.`);
