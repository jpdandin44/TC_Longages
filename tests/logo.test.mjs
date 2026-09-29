import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import { inline } from '../scripts/inline-html.mjs';

const root = new URL('../', import.meta.url);
const hash = bytes => createHash('sha256').update(bytes).digest('hex');

test('Toutes les pages intègrent le JPEG fourni sans modification et gardent leurs styles autonomes', async () => {
  const original = await readFile(new URL('Images_Photos/Logo.jpeg', root));
  for (const name of ['index', 'bureau', 'communication', 'inscriptions', 'actualites-bureau', 'demo-parcours', 'demo-adherer', 'demo-inscriptions']) {
    const html = await inline(await readFile(new URL(`src/${name}.html`, root), 'utf8'));
    const logos = [...html.matchAll(/class="club-logo-image" src="data:image\/jpeg;base64,([^"]+)"/g)];
    assert.ok(logos.length >= 1, `${name} contient au moins le logo de l’en-tête`);
    for (const [, encoded] of logos) assert.equal(hash(Buffer.from(encoded, 'base64')), hash(original), `${name} conserve les octets exacts du JPEG`);
    assert.match(html, /<style data-club-brand>/);
    assert.doesNotMatch(html, /<span class="brand-mark"|<span class="avatar" aria-hidden="true">TCL/);
    assert.doesNotMatch(html, /<script[^>]+src=|<link[^>]+rel="stylesheet"|src="(?!data:)/);
  }
});

test('Les marques de pied de page et les avatars utilisent le même logo, sans remplacer les photos', async () => {
  const home = await inline(await readFile(new URL('src/index.html', root), 'utf8'));
  assert.equal((home.match(/class="club-logo-image"/g) || []).length, 2);
  assert.equal((home.match(/src="data:image\/webp;base64,/g) || []).length, 1);
  assert.match(home, /class="hero-photo"><img src="data:image\/jpeg;base64,/);
  const sample = await inline('<html><head></head><body><span class="avatar" aria-hidden="true">TCL</span><span class="club-logo club-logo-avatar" aria-hidden="true"></span></body></html>');
  assert.equal((sample.match(/class="club-logo club-logo-avatar"/g) || []).length, 2);
  assert.equal((sample.match(/class="club-logo-image"/g) || []).length, 2);
  assert.match(sample, /width="1131" height="1600" alt=""/);
});
