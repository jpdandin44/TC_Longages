// Recette d'une copie temporaire sous Apache local ; aucun hébergement contacté.
import assert from 'node:assert/strict';
import { readFile, writeFile, rename, unlink, mkdir, realpath } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../', import.meta.url));
const [origin, directory] = process.argv.slice(2);
assert.match(origin || '', /^http:\/\/127\.0\.0\.1:\d+$/);
const folder = await realpath(directory);
const allowedRoot = await realpath(path.join(root, 'tmp'));
assert.ok(folder.startsWith(allowedRoot + path.sep), 'La copie doit rester dans tmp/ du projet.');
const output = path.join(root, 'docs/recette/v1-sous-domaine');
await mkdir(output, { recursive: true });
const marker = name => path.join(folder, 'maintenance.' + name);
const activeBytes = await readFile(marker('active'));
const names = ['index.html','competitions.html','calendrier.html','disponibilites.html','equipes.html','espace.html','contact.html'];
const routes = ['/', ...names.map(name => '/' + name), '/robots.txt'];
const denied = ['/bureau.html','/communication.html','/inscriptions.html','/adherer.html','/parcours.html','/actualites-bureau.html','/index.php','/app.cjs','/.env','/reste.txt','/assets/','/contact.html/extra'];
const hash = value => createHash('sha256').update(value).digest('hex');
const results = [];
let status = 'failed', failure;
async function request(state, url, method, expected, content) {
  const response = await fetch(origin + url, { method, redirect: 'manual' });
  assert.equal(response.status, expected, `${state} ${method} ${url}`);
  assert.equal(response.headers.get('cache-control'), 'no-store');
  assert.equal(response.headers.get('x-robots-tag'), 'noindex, nofollow');
  const body = Buffer.from(await response.arrayBuffer());
  if (content && method === 'GET') assert.equal(hash(body), hash(content), 'Octets servis : ' + url);
  if (method === 'HEAD') assert.equal(body.length, 0);
  results.push({ state, method, path: url, status: response.status });
}
async function closed(state) {
  for (const url of routes) for (const method of ['GET','HEAD']) await request(state, url, method, 503);
}
try {
  await closed('fermeture-initiale');
  await rename(marker('active'), marker('inactive'));
  for (const url of routes) {
    const content = await readFile(path.join(folder, url === '/' ? 'index.html' : url.slice(1)));
    for (const method of ['GET','HEAD']) await request('ouverture', url, method, 200, content);
  }
  for (const url of denied) for (const method of ['GET','HEAD']) await request('ancien-chemin-bloque', url, method, 404);
  for (const method of ['POST','PUT','DELETE','OPTIONS']) await request('methode-refusee', '/contact.html', method, 405);
  for (const url of ['/.htaccess','/maintenance.active','/maintenance.inactive']) {
    const response = await fetch(origin + url, { redirect: 'manual' });
    assert.ok([403,404].includes(response.status), 'Contrôle non exposé : ' + url);
    await response.arrayBuffer();
    results.push({ state:'controle-non-expose', method:'GET', path:url, status:response.status });
  }
  await rename(marker('inactive'), marker('hold'));
  await closed('aucun-temoin');
  await rename(marker('hold'), marker('inactive'));
  await writeFile(marker('active'), activeBytes, { flag:'wx' });
  await closed('deux-temoins');
  await unlink(marker('inactive'));
  await closed('refermeture');
  status = 'passed';
} catch (error) {
  failure = error.message;
  throw error;
} finally {
  await writeFile(marker('active'), activeBytes);
  for (const suffix of ['inactive','hold']) await unlink(marker(suffix)).catch(error => { if (error.code !== 'ENOENT') throw error; });
  await writeFile(path.join(output,'apache-results.json'), JSON.stringify({ date:new Date().toISOString(), status, environment:'Apache 2.4 local en conteneur, mod_rewrite/mod_headers, AllowOverride All', remoteDeployment:false, ...(failure ? {failure} : {}), results },null,2)+'\n');
}
console.log(`Apache : ${results.length} contrôles réussis, ouverture volontaire et fermeture par défaut confirmées. Aucun dépôt distant.`);
