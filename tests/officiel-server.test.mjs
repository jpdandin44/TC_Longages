import { test } from 'node:test';
import assert from 'node:assert/strict';
import http from 'node:http';
import { once } from 'node:events';
import { mkdtemp, writeFile, readFile, readdir, rm, rmdir } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { inflateRawSync } from 'node:zlib';
import { createOfficialServer } from '../scripts/official-server.mjs';
import { validateOfficialPackage, packageOfficial } from '../scripts/package-officiel.mjs';

const pages = ['index.html', 'competitions.html', 'calendrier.html', 'disponibilites.html', 'equipes.html', 'espace.html', 'contact.html'];
const names = [...pages, 'robots.txt', '.htaccess', 'maintenance.active'];
const sha256 = data => createHash('sha256').update(data).digest('hex');
async function fixture(t) {
  const directory = await mkdtemp(path.join(tmpdir(), 'tcl-official-test-'));
  const files = {};
  for (const name of names) {
    const value = name.endsWith('.html') ? `<!doctype html><html lang="fr"><body>${name} — aperçu sans donnée privée</body></html>` : name;
    await writeFile(path.join(directory, name), value);
    files[name] = { bytes: Buffer.byteLength(value), sha256: sha256(value) };
  }
  const manifestFile = path.join(tmpdir(), path.basename(directory) + '.json');
  await writeFile(manifestFile, JSON.stringify({ mode: 'local-review-only', files }));
  t.after(async () => {
    // The fixture has no subdirectories; remove exact enumerated regular files.
    for (const name of await readdir(directory)) await rm(path.join(directory, name), { force: true });
    await rmdir(directory);
    await rm(manifestFile, { force: true });
  });
  return { directory, manifestFile, files };
}
async function start(t, directory) {
  const server = createOfficialServer({ directory });
  server.listen(0, '127.0.0.1');
  await once(server, 'listening');
  t.after(() => new Promise(resolve => { server.close(resolve); server.closeAllConnections(); }));
  return { origin: `http://127.0.0.1:${server.address().port}`, server };
}
function request(origin, requestPath, { method = 'GET', headers = {} } = {}) {
  return new Promise((resolve, reject) => {
    const url = new URL(origin);
    const req = http.request({ hostname: url.hostname, port: url.port, path: requestPath, method, headers }, response => {
      const parts = [];
      response.on('data', part => parts.push(part));
      response.on('end', () => resolve({ status: response.statusCode, headers: response.headers, body: Buffer.concat(parts).toString('utf8') }));
    });
    req.on('error', reject); req.end();
  });
}

test('Aperçu officiel : sept pages et robots disponibles, racine sur accueil, HEAD sans contenu', async t => {
  const { directory } = await fixture(t);
  const { origin } = await start(t, directory);
  for (const name of pages) {
    const response = await request(origin, '/' + name);
    assert.equal(response.status, 200);
    assert.match(response.body, new RegExp(name.replace('.', '\\.')));
    assert.equal(response.headers['cache-control'], 'no-store');
    assert.equal(response.headers['x-robots-tag'], 'noindex, nofollow');
    assert.match(response.headers['content-security-policy'], /connect-src 'none'/);
    assert.match(response.headers['content-security-policy'], /form-action 'none'/);
    const head = await request(origin, '/' + name, { method: 'HEAD' });
    assert.equal(head.status, 200);
    assert.equal(head.body, '');
    assert.equal(head.headers['content-length'], response.headers['content-length']);
  }
  assert.match((await request(origin, '/')).body, /index\.html/);
  assert.equal((await request(origin, '/contact.html?apercu=1')).status, 200);
  const robots = await request(origin, '/robots.txt');
  assert.equal(robots.status, 200);
  assert.match(robots.headers['content-type'], /text\/plain/);
});

test('Aperçu officiel : chemins privés, anciens écrans, encodage et fichiers techniques refusés', async t => {
  const { directory } = await fixture(t);
  const { origin } = await start(t, directory);
  for (const target of ['/espace/', '/admin/', '/api/', '/api/contact', '/bureau.html', '/communication.html', '/inscriptions.html', '/adherer.html', '/parcours.html', '/actualites-bureau.html', '/.env', '/.local/users.json', '/.htaccess', '/maintenance.active', '/src/index.html', '/officiel/index.html', '/../index.html', '/%69ndex.html', '/%2e%2e/index.html', '/..\\index.html']) {
    const response = await request(origin, target);
    assert.equal(response.status, 404, target);
    assert.doesNotMatch(response.body, /aperçu sans donnée privée/);
  }
  assert.equal((await request(origin, '//outside.example/index.html')).status, 400);
});

test('Le calendrier configuré peut charger Google sans ouvrir les cadres des autres pages', async t => {
  const { directory } = await fixture(t);
  await writeFile(path.join(directory, 'calendrier.html'), '<!doctype html><iframe class="club-calendar" src="https://calendar.google.com/calendar/embed?src=club%40example.invalid"></iframe>');
  const { origin } = await start(t, directory);
  const calendar = await request(origin, '/calendrier.html');
  assert.equal(calendar.status, 200);
  assert.match(calendar.headers['content-security-policy'], /frame-src https:\/\/calendar\.google\.com\/calendar\//);
  const contact = await request(origin, '/contact.html');
  assert.doesNotMatch(contact.headers['content-security-policy'], /frame-src/);
  assert.match(contact.headers['content-security-policy'], /default-src 'none'/);
});

test('Aperçu officiel : méthodes d’écriture et hôte externe refusés, erreur sans fuite de chemin', async t => {
  const { directory } = await fixture(t);
  const { origin } = await start(t, directory);
  for (const method of ['POST', 'PUT', 'DELETE', 'OPTIONS']) {
    const response = await request(origin, '/contact.html', { method });
    assert.equal(response.status, 405);
    assert.equal(response.headers.allow, 'GET, HEAD');
  }
  assert.equal((await request(origin, '/', { headers: { Host: 'attacker.example' } })).status, 403);
  await rm(path.join(directory, 'contact.html'));
  const absent = await request(origin, '/contact.html');
  assert.equal(absent.status, 503);
  assert.ok(!absent.body.includes(directory));
});

test('Archive officielle : liste exacte, taille, hash, mode et maintenance active obligatoires', async t => {
  const { directory, manifestFile, files } = await fixture(t);
  const options = { directory, manifestFile };
  assert.deepEqual([...await validateOfficialPackage(options)].map(([name]) => name), names);
  await writeFile(path.join(directory, 'secret.txt'), 'private-test');
  await assert.rejects(validateOfficialPackage(options), /dix fichiers/);
  await rm(path.join(directory, 'secret.txt'));
  await writeFile(path.join(directory, 'contact.html'), 'modified');
  await assert.rejects(validateOfficialPackage(options), /Fichier modifié/);
  const body = '<!doctype html><html lang="fr"><body>contact.html — aperçu sans donnée privée</body></html>';
  await writeFile(path.join(directory, 'contact.html'), body);
  await writeFile(manifestFile, JSON.stringify({ mode: 'published', files }));
  await assert.rejects(validateOfficialPackage(options), /Manifeste/);
  await writeFile(manifestFile, JSON.stringify({ mode: 'local-review-only', files: { ...files, 'contact.html': { ...files['contact.html'], bytes: 0 } } }));
  await assert.rejects(validateOfficialPackage(options), /Fichier modifié/);
  await writeFile(manifestFile, JSON.stringify({ mode: 'local-review-only', files }));
  await rm(path.join(directory, 'maintenance.active'));
  await writeFile(path.join(directory, 'maintenance.inactive'), 'inactive');
  await assert.rejects(validateOfficialPackage(options), /maintenance.active/);
});

test('Archive officielle : ZIP de dix fichiers sans helper et manifeste séparé', { skip: process.platform !== 'win32' }, async t => {
  const { directory, manifestFile } = await fixture(t);
  const outputDirectory = await mkdtemp(path.join(tmpdir(), 'tcl-official-package-test-'));
  t.after(async () => {
    for (const name of await readdir(outputDirectory)) await rm(path.join(outputDirectory, name), { force: true });
    await rmdir(outputDirectory);
  });
  const report = await packageOfficial({ directory, manifestFile, outputDirectory });
  assert.equal(report.mode, 'local-review-only');
  assert.equal(report.maintenance, 'unconditional-503');
  assert.deepEqual(Object.keys(report.files), names);
  const zip = await readFile(path.join(outputDirectory, report.archive.file));
  assert.equal(report.archive.bytes, zip.length);
  assert.equal(report.archive.sha256, sha256(zip));
  // Independently read the ZIP central directory and verify every stored payload.
  const end = zip.length - 22;
  assert.equal(zip.readUInt32LE(end), 0x06054b50);
  assert.equal(zip.readUInt16LE(end + 10), names.length);
  let offset = zip.readUInt32LE(end + 16);
  const archived = [];
  for (let index = 0; index < names.length; index++) {
    assert.equal(zip.readUInt32LE(offset), 0x02014b50);
    const size = zip.readUInt16LE(offset + 28);
    const name = zip.subarray(offset + 46, offset + 46 + size).toString('utf8');
    const local = zip.readUInt32LE(offset + 42);
    assert.equal(zip.readUInt32LE(local), 0x04034b50);
    const dataStart = local + 30 + zip.readUInt16LE(local + 26) + zip.readUInt16LE(local + 28);
    const compressed = zip.subarray(dataStart, dataStart + zip.readUInt32LE(offset + 20));
    const method = zip.readUInt16LE(offset + 10);
    assert.ok([0, 8].includes(method));
    const content = method === 8 ? inflateRawSync(compressed) : compressed;
    assert.equal(content.length, report.files[name].bytes);
    assert.equal(sha256(content), report.files[name].sha256);
    archived.push(name);
    offset += 46 + size + zip.readUInt16LE(offset + 30) + zip.readUInt16LE(offset + 32);
  }
  assert.deepEqual(archived, names);
  assert.deepEqual((await readdir(outputDirectory)).sort(), ['tc-longages-officiel-apercu.manifest.json', 'tc-longages-officiel-apercu.zip']);
});
