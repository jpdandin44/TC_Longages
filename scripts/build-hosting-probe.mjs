import {readFile,writeFile,mkdir} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import {resolve} from 'node:path';
import {fileURLToPath} from 'node:url';

const root=fileURLToPath(new URL('../',import.meta.url));
const sha256=data=>createHash('sha256').update(data).digest('hex');

export function hostingRequirements(lock){
  const core=lock.packages.find(item=>item.name==='drupal/core');
  const match=/^>=(\d+)\.(\d+)(?:\.(\d+))?$/.exec(core?.require?.php || '');
  if(!match)throw new Error('Contrainte PHP Drupal à qualifier avant de générer la sonde.');
  const extensions=new Set(['pdo_mysql']);
  for(const item of lock.packages)for(const name of Object.keys(item.require || {})){
    if(name.startsWith('ext-'))extensions.add(name.slice(4).toLowerCase());
  }
  return {coreVersion:core.version,minimumPhp:[match[1],match[2],match[3] || '0'].join('.'),minimumPhpId:Number(match[1])*10000+Number(match[2])*100+Number(match[3] || 0),requiredExtensions:[...extensions].sort()};
}

export async function buildHostingProbe(projectRoot=root,output=resolve(projectRoot,'.local/qualification-preproduction')){
  const lockData=await readFile(resolve(projectRoot,'drupal/composer.lock'));
  const requirements=hostingRequirements(JSON.parse(lockData));
  const web=resolve(output,'web');
  await mkdir(web,{recursive:true});
  const extensions=requirements.requiredExtensions.map(name=>`'${name}'`).join(', ');
  const files={
    '.htaccess':'Options -Indexes\nRequire all denied\n',
    '.htaccess.test.example':`# INACTIF : remplacer .htaccess uniquement pour le test autorisé.\n# Sans code Drupal, secret ou fichier tiers dans ce dossier.\nOptions -Indexes\nRequire all denied\n<Files "tcl-probe.php">\n  Require all granted\n  SetHandler application/x-httpd-php83\n</Files>\nAddHandler application/x-httpd-php83 .php\n<Files "robots.txt">\n  Require all granted\n</Files>\n<IfModule mod_headers.c>\n  Header always set X-Robots-Tag "noindex, nofollow, noarchive"\n  Header always set Cache-Control "no-store"\n</IfModule>\n`,
    'robots.txt':'User-agent: *\nDisallow: /\n',
    'tcl-probe.php':`<?php
declare(strict_types=1);
// Sonde autonome sans Drupal, secret, connexion SQL ou lecture de fichier.
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('X-Content-Type-Options: nosniff');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'HEAD'], true)) {
  http_response_code(405);
  header('Allow: GET, HEAD');
  exit;
}
$required = [${extensions}];
$loaded = [];
$missing = [];
foreach ($required as $extension) {
  $loaded[$extension] = extension_loaded($extension);
  if (!$loaded[$extension]) { $missing[] = $extension; }
}
$phpCompatible = PHP_VERSION_ID >= ${requirements.minimumPhpId};
$compatible = $phpCompatible && count($missing) === 0;
http_response_code($compatible ? 200 : 503);
if ($method !== 'HEAD') {
  echo json_encode([
    'kind' => 'tcl-runtime-probe',
    'php' => PHP_VERSION,
    'sapi' => PHP_SAPI,
    'minimumPhp' => '${requirements.minimumPhp}',
    'phpCompatible' => $phpCompatible,
    'extensions' => $loaded,
    'missingExtensions' => $missing,
    'compatible' => $compatible,
    'limits' => [
      'memory' => ini_get('memory_limit'),
      'executionSeconds' => ini_get('max_execution_time'),
      'upload' => ini_get('upload_max_filesize'),
    ],
  ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
`,
  };
  for(const [name,content] of Object.entries(files))await writeFile(resolve(web,name),content);
  const manifest={createdAt:new Date().toISOString(),kind:'qualification PHP préparée localement, fermée par défaut',remoteDeployment:false,composerLockSha256:sha256(lockData),requirements,files:Object.fromEntries(Object.entries(files).map(([name,content])=>[name,{sha256:sha256(content),bytes:Buffer.byteLength(content)}]))};
  await writeFile(resolve(output,'manifest.json'),JSON.stringify(manifest,null,2)+'\n');
  return {output,web,manifest};
}

if(process.argv[1]&&resolve(process.argv[1])===fileURLToPath(import.meta.url)){
  const result=await buildHostingProbe();
  console.log(`Sonde locale fermée préparée (${result.manifest.requirements.requiredExtensions.length} extensions). Aucun transfert distant.`);
}
