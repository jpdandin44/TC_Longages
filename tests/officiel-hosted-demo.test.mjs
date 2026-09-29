import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile, writeFile, readdir, mkdir, mkdtemp, copyFile, rm, rmdir, symlink, unlink } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { inflateRawSync } from 'node:zlib';
import { buildOfficialHostedDemo, hostedOfficialNames, hostedOfficialHtaccess } from '../scripts/build-officiel-hosted-demo.mjs';
import { validateOfficialHostedPackage, packageOfficialHostedDemo } from '../scripts/package-officiel-hosted-demo.mjs';
import { officialPages } from '../scripts/official-config.mjs';

const root = new URL('../', import.meta.url);
const read = file => readFile(new URL(file, root), 'utf8');
const sha256 = value => createHash('sha256').update(value).digest('hex');
async function fixture(t) {
  const base = await mkdtemp(path.join(tmpdir(), 'tcl-hosted-official-test-'));
  const sourceDirectory = path.join(base, 'source');
  const directory = path.join(base, 'demo');
  const outputDirectory = path.join(base, 'archives');
  for (const folder of [sourceDirectory, directory, outputDirectory]) await mkdir(folder);
  const sourceManifestFile = path.join(base, 'source.json');
  const manifestFile = path.join(base, 'demo.json');
  for (const name of hostedOfficialNames) await copyFile(new URL('officiel/' + name, root), path.join(sourceDirectory, name));
  await copyFile(new URL('data/officiel-manifest.json', root), sourceManifestFile);
  t.after(async () => {
    // Only the three known flat fixture folders and their files are removed.
    for (const folder of [sourceDirectory, directory, outputDirectory]) {
      for (const name of await readdir(folder)) await rm(path.join(folder, name), { force: true });
      await rmdir(folder);
    }
    for (const name of ['source.json', 'demo.json']) await rm(path.join(base, name), { force: true });
    await rmdir(base);
  });
  return { sourceDirectory, sourceManifestFile, directory, manifestFile, outputDirectory, base };
}

test('Démo V1 : sept pages identiques à l’aperçu hormis la bannière, coordonnées et limites conservées', async () => {
  const manifest = JSON.parse(await read('data/officiel-hosted-demo-manifest.json'));
  assert.deepEqual(Object.keys(manifest.files), hostedOfficialNames);
  assert.equal(manifest.defaultState, 'maintenance-closed');
  assert.equal(manifest.authentication, 'none');
  assert.equal(manifest.contact, 'preview-only');
  const banner = /<aside class="official-ribbon"[^>]*>[\s\S]*?<\/aside>/;
  for (const name of officialPages) {
    const source = await read('officiel/' + name);
    const demo = await read('officiel-demo-o2switch/' + name);
    assert.equal(source.replace(banner, ''), demo.replace(banner, ''), name);
    assert.equal(sha256(demo), manifest.files[name].sha256);
    assert.match(demo, /DÉMONSTRATION V1/);
    assert.match(demo, /aucun envoi de formulaire ni connexion aux espaces privés/);
    assert.match(demo, /35, chemin de Muret/);
    assert.match(demo, /mailto:tclongages@gmail\.com/);
    assert.doesNotMatch(demo, /23310230@fft\.fr|localStorage|sessionStorage|type="password"|fetch\(|XMLHttpRequest|(?:bureau|communication|inscriptions|adherer)\.html/);
  }
  assert.match(await read('officiel-demo-o2switch/contact.html'), /support@tclongages\.fr/);
  assert.match(await read('officiel-demo-o2switch/contact.html'), /activation à confirmer/);
  assert.match(await read('officiel-demo-o2switch/espace.html'), /connexion n’est pas encore ouverte/);
  assert.match(await read('officiel/.htaccess'), /RewriteRule \^ - \[R=503,L\]/);
  assert.doesNotMatch(await read('officiel/.htaccess'), /RewriteCond/);
});

test('Démo V1 : configuration Apache fermée par défaut, méthodes et fichiers résiduels filtrés', async () => {
  const config = await read('officiel-demo-o2switch/.htaccess');
  assert.equal(config, hostedOfficialHtaccess);
  assert.match(config, /Options -Indexes -MultiViews/);
  assert.match(config, /RewriteCond %\{DOCUMENT_ROOT\}\/maintenance\.active -f \[OR\]\nRewriteCond %\{DOCUMENT_ROOT\}\/maintenance\.active -d \[OR\]\nRewriteCond %\{DOCUMENT_ROOT\}\/maintenance\.inactive !-f\nRewriteRule \^ - \[R=503,L\]/);
  assert.match(config, /RewriteCond %\{REQUEST_METHOD\} !\^\(GET\|HEAD\)\$/);
  assert.match(config, /RewriteRule !\^\(\|index\\\.html\|competitions\\\.html\|calendrier\\\.html\|disponibilites\\\.html\|equipes\\\.html\|espace\\\.html\|contact\\\.html\|robots\\\.txt\)\$ - \[R=404,L\]/);
  for (const text of ['Require all denied', 'Cache-Control "no-store"', 'X-Robots-Tag "noindex, nofollow"', "connect-src 'none'", "form-action 'none'"]) assert.ok(config.includes(text), text);
  assert.doesNotMatch(config, /https?:\/\/|AuthUserFile|AuthType/);
  assert.equal(await read('officiel-demo-o2switch/robots.txt'), 'User-agent: *\nDisallow: /\n');
  assert.deepEqual((await readdir(new URL('officiel-demo-o2switch/', root))).sort(), [...hostedOfficialNames].sort());
});

test('Construction démo V1 : refus de source altérée et de fichiers inattendus, sans écrasement hors variante', async t => {
  const options = await fixture(t);
  await buildOfficialHostedDemo(options);
  const sourceFile = path.join(options.sourceDirectory, 'contact.html');
  const original = await readFile(sourceFile);
  await writeFile(sourceFile, Buffer.concat([original, Buffer.from('change')]));
  await assert.rejects(buildOfficialHostedDemo(options), /Fichier modifié/);
  await writeFile(sourceFile, original);
  await writeFile(path.join(options.directory, 'secret.json'), 'private');
  await assert.rejects(buildOfficialHostedDemo(options), /Fichiers inattendus/);
  assert.equal(await readFile(path.join(options.directory, 'secret.json'), 'utf8'), 'private');
  await rm(path.join(options.directory, 'secret.json'));
  await assert.rejects(buildOfficialHostedDemo({ ...options, directory: options.sourceDirectory }), /distincte/);
  await rm(path.join(options.directory, 'contact.html'));
  await mkdir(path.join(options.directory, 'contact.html'));
  await assert.rejects(buildOfficialHostedDemo(options), /dossier ou un lien/);
  await rmdir(path.join(options.directory, 'contact.html'));
});

test('Démo V1 : construction et archivage refusent les dossiers symboliques', async t => {
  const options = await fixture(t);
  const link = path.join(options.base, 'linked');
  await buildOfficialHostedDemo(options);
  await symlink(options.directory, link, process.platform === 'win32' ? 'junction' : 'dir');
  try {
    await assert.rejects(buildOfficialHostedDemo({ ...options, directory: link }), /lien symbolique/);
    await assert.rejects(validateOfficialHostedPackage({ ...options, directory: link }), /lien/);
    await assert.rejects(buildOfficialHostedDemo({ ...options, sourceDirectory: link }), /lien symbolique/);
  } finally { await unlink(link); }
});

test('Archive démo V1 : intégrité, mode, fermeture et liste exacte imposés avant création', async t => {
  const options = await fixture(t);
  await buildOfficialHostedDemo(options);
  assert.deepEqual([...await validateOfficialHostedPackage(options)].map(([name]) => name), hostedOfficialNames);
  await writeFile(path.join(options.directory, 'bureau.html'), 'ancienne page');
  await assert.rejects(validateOfficialHostedPackage(options), /dix fichiers/);
  await rm(path.join(options.directory, 'bureau.html'));
  const contact = path.join(options.directory, 'contact.html');
  const original = await readFile(contact);
  await writeFile(contact, 'modified');
  await assert.rejects(validateOfficialHostedPackage(options), /Fichier modifié/);
  await writeFile(contact, original);
  const manifest = JSON.parse(await readFile(options.manifestFile, 'utf8'));
  await writeFile(options.manifestFile, JSON.stringify({ ...manifest, authentication: 'drupal' }));
  await assert.rejects(validateOfficialHostedPackage(options), /Manifeste/);
  await writeFile(options.manifestFile, JSON.stringify(manifest));
  await rm(path.join(options.directory, 'maintenance.active'));
  await writeFile(path.join(options.directory, 'maintenance.inactive'), 'open');
  await assert.rejects(validateOfficialHostedPackage(options), /maintenance.active/);
  await rm(path.join(options.directory, 'maintenance.inactive'));
  await buildOfficialHostedDemo(options);
  const changed = '# changed maintenance';
  await writeFile(path.join(options.directory, '.htaccess'), changed);
  manifest.files['.htaccess'] = { bytes: Buffer.byteLength(changed), sha256: sha256(changed) };
  await writeFile(options.manifestFile, JSON.stringify(manifest));
  await assert.rejects(validateOfficialHostedPackage(options), /règle de fermeture/);
});

test('Archive démo V1 : ZIP exact de dix fichiers vérifiés, manifeste conservé hors du paquet', { skip: process.platform !== 'win32' }, async t => {
  const options = await fixture(t);
  await buildOfficialHostedDemo(options);
  const report = await packageOfficialHostedDemo(options);
  assert.equal(report.mode, 'hosted-official-demonstration-only');
  assert.equal(report.maintenance, 'active-or-missing-inactive-503');
  const zip = await readFile(path.join(options.outputDirectory, report.archive.file));
  assert.equal(zip.length, report.archive.bytes);
  assert.equal(sha256(zip), report.archive.sha256);
  const end = zip.length - 22;
  assert.equal(zip.readUInt32LE(end), 0x06054b50);
  assert.equal(zip.readUInt16LE(end + 10), hostedOfficialNames.length);
  let offset = zip.readUInt32LE(end + 16);
  const names = [];
  for (let index = 0; index < hostedOfficialNames.length; index++) {
    assert.equal(zip.readUInt32LE(offset), 0x02014b50);
    const size = zip.readUInt16LE(offset + 28);
    const name = zip.subarray(offset + 46, offset + 46 + size).toString('utf8');
    const local = zip.readUInt32LE(offset + 42);
    assert.equal(zip.readUInt32LE(local), 0x04034b50);
    const dataStart = local + 30 + zip.readUInt16LE(local + 26) + zip.readUInt16LE(local + 28);
    const compressed = zip.subarray(dataStart, dataStart + zip.readUInt32LE(offset + 20));
    const method = zip.readUInt16LE(offset + 10);
    assert.ok([0, 8].includes(method));
    const bytes = method === 8 ? inflateRawSync(compressed) : compressed;
    assert.equal(bytes.length, report.files[name].bytes);
    assert.equal(sha256(bytes), report.files[name].sha256);
    assert.deepEqual(bytes, await readFile(path.join(options.directory, name)));
    names.push(name);
    offset += 46 + size + zip.readUInt16LE(offset + 30) + zip.readUInt16LE(offset + 32);
  }
  assert.deepEqual(names, hostedOfficialNames);
  assert.deepEqual((await readdir(options.outputDirectory)).sort(), ['tc-longages-v1-demo-o2switch.manifest.json', 'tc-longages-v1-demo-o2switch.zip']);
});
