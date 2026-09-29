import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { webcrypto } from 'node:crypto';

function setup() {
  const data = new Map();
  const context = vm.createContext({ window: { crypto: webcrypto }, localStorage: { getItem: key => data.get(key) ?? null, setItem: (key, value) => data.set(key, value) }, URL });
  for (const name of ['communication-store', 'communication-adoc']) vm.runInContext(readFileSync(new URL('../src/' + name + '.js', import.meta.url), 'utf8'), context);
  return { store: context.window.TCLCommunication, adoc: context.window.TCLADOC, data };
}
const content = { title: 'Article de recette', body: 'Un rendez-vous au club.', category: 'Vie du club', link: '' };

test('ADOC conserve le contenu complet et compte le lien dans la limite de 2 000 caractères', () => {
  const { adoc } = setup();
  const exact = adoc.prepare({ ...content, body: 'a'.repeat(2000) });
  assert.equal(exact.count, 2000); assert.equal(exact.ready, true);
  const tooLong = adoc.prepare({ ...content, body: 'a'.repeat(2001) });
  assert.equal(tooLong.count, 2001); assert.equal(tooLong.body.length, 2001); assert.equal(tooLong.ready, false);
  assert.match(tooLong.reason, /aucun texte n’est coupé/);
  const linked = adoc.prepare({ ...content, body: 'a'.repeat(1985), link: 'https://example.org' });
  assert.equal(linked.body, 'a'.repeat(1985) + '\n\nhttps://example.org');
  assert.equal(linked.ready, false);
  assert.equal(adoc.prepare({ ...content, body: '🎾'.repeat(1001) }).ready, false);
});

test('Ten’Up est Non par défaut ; son changement annule la validation précédente', () => {
  const { store, adoc, data } = setup();
  const draft = store.save(content);
  assert.equal(draft.adocVisibleOnTenup, false);
  assert.throws(() => adoc.approved(draft.id, draft.updatedAt), /plus validée/);
  const validated = store.validate(draft.id, draft.updatedAt);
  const before = data.get(store.key);
  assert.equal(adoc.approved(validated.id, validated.updatedAt).visibleOnTenup, false);
  assert.equal(data.get(store.key), before, 'La simulation ne crée aucun état envoyé ni autre écriture.');
  const changed = store.save({ ...validated, adocVisibleOnTenup: true });
  assert.equal(changed.status, 'draft'); assert.equal(store.publicPosts().length, 0);
  assert.throws(() => adoc.approved(validated.id, validated.updatedAt), /plus validée/);
  const approved = store.validate(changed.id, changed.updatedAt);
  assert.equal(adoc.approved(approved.id, approved.updatedAt).visibleOnTenup, true);
  assert.throws(() => adoc.approved(approved.id, validated.updatedAt), /plus validée/);
});

test('Un contenu trop long reste validable pour les autres canaux mais la préparation ADOC est refusée', () => {
  const { store, adoc, data } = setup();
  const draft = store.save({ ...content, body: 'b'.repeat(2200) });
  const validated = store.validate(draft.id, draft.updatedAt);
  assert.equal(store.publicPosts().length, 1);
  const before = data.get(store.key);
  assert.throws(() => adoc.approved(validated.id, validated.updatedAt), /dépasse 2 000/);
  assert.equal(data.get(store.key), before);
});

test('Les anciens contenus v1 et les imports conservent un choix Ten’Up explicite sans doublon artificiel', () => {
  const { store, adoc, data } = setup();
  const original = store.save(content); delete original.adocVisibleOnTenup;
  data.set(store.key, JSON.stringify({ version: 1, posts: [original] }));
  assert.equal(adoc.prepare(store.read()[0]).visibleOnTenup, false);
  assert.equal(store.importData({ version: 1, posts: [{ ...original, adocVisibleOnTenup: false }] }).skipped, 1);
  const conflict = store.importData({ version: 1, posts: [{ ...original, adocVisibleOnTenup: true }] });
  assert.equal(conflict.conflicts, 1);
  assert.equal(store.read()[1].adocVisibleOnTenup, true);
  assert.equal(store.read()[1].status, 'draft');
  const before = data.get(store.key);
  assert.throws(() => store.save({ ...content, adocVisibleOnTenup: 'oui' }), /Oui ou Non/);
  assert.throws(() => store.importData({ version: 1, posts: [{ ...original, adocVisibleOnTenup: null }] }), /Oui ou Non/);
  assert.equal(data.get(store.key), before);
});

test('ADOC conserve l’affiche entière et invite à préparer les formats non acceptés par l’écran fourni', () => {
  const { adoc } = setup();
  const image = { dataUrl: 'data:image/jpeg;base64,/9j/AAAA', alt: 'Affiche de recette', name: 'recette.jpg' };
  const poster = adoc.prepare({ ...content, body: '', image, adocVisibleOnTenup: true });
  assert.equal(poster.ready, true); assert.equal(poster.count, 0);
  assert.equal(poster.image, image); assert.equal(poster.visibleOnTenup, true);
  const webp = adoc.prepare({ ...content, image: { ...image, dataUrl: 'data:image/webp;base64,UklGRg==' } });
  assert.equal(webp.ready, false); assert.match(webp.reason, /préparer en JPEG/);
});
