import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile, readdir } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = new URL('../', import.meta.url);
const read = file => readFile(new URL(file, root), 'utf8');
const hash = data => createHash('sha256').update(data).digest('hex');
test('La vitrine intègre la photo réelle du court, conserve l’illustration secondaire et les sept contacts du club', async () => {
  const html = await read('release/index.html');
  assert.equal((html.match(/mailto:tclongages@gmail\.com/g) || []).length, 7);
  assert.doesNotMatch(html, /hotmail|TCLongages@fft.fr/);
  const photos = [...html.matchAll(/src="data:image\/webp;base64,([^"]+)"/g)];
  assert.equal(photos.length, 1);
  assert.deepEqual(photos.map(match => hash(Buffer.from(match[1], 'base64'))), [
    'af1d7ac46b533fe7f2fb734f0afe921bf86e46a542cd4b7a000e95d618d7772c'
  ]);
  const court = html.match(/class="hero-photo"><img src="data:image\/png;base64,([^"]+)"/);
  assert.ok(court);
  assert.equal(hash(Buffer.from(court[1], 'base64')), hash(await readFile(new URL('Images_Photos/Image_terrain_OK.png', root))));
  assert.match(html, /ne représente pas les installations/);
  assert.match(html, /https:\/\/tenup.fft.fr\/club\/60310230/);
  assert.match(html, /https:\/\/www.facebook.com\/tc.longages.31/);
});
test('Le livrable vitrine exclut entièrement la gestion et les données de démonstration', async () => {
  assert.deepEqual(await readdir(new URL('release/', root)), ['index.html']);
  const html = await read('release/index.html');
  assert.doesNotMatch(html, /localStorage|TCLCommunication|communication\.html|PROTOTYPE|news-list|noindex/);
});
test('Les six HTML sont autonomes sans ressource de chargement externe', async () => {
  for (const file of ['dist/index.html', 'dist/communication.html', 'dist/bureau.html', 'dist/inscriptions.html', 'dist/actualites-bureau.html', 'release/index.html']) {
    const html = await read(file);
    assert.doesNotMatch(html, /<script[^>]+src=|<link[^>]+rel="stylesheet"|src="(?!data:)/);
    assert.doesNotMatch(html, /<\/script[^>]*>[^<]*undefined/);
    assert.match(html, /<html lang="fr">/);
    assert.ok(!html.includes('\uFFFD'), `${file}: aucun caractère de remplacement`);
  }
});
test('La construction est déterministe', async () => {
  const before = await read('data/build-manifest.json');
  execFileSync(process.execPath, [fileURLToPath(new URL('scripts/build.mjs', root))]);
  assert.equal(await read('data/build-manifest.json'), before);
});
