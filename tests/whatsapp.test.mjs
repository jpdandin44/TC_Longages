import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { webcrypto } from 'node:crypto';

function setup() {
  const data = new Map();
  const context = vm.createContext({ window: { crypto: webcrypto }, localStorage: { getItem: k => data.get(k) ?? null, setItem: (k, v) => data.set(k, v) }, URL });
  for (const script of ['communication-store.js', 'communication-whatsapp.js']) {
    vm.runInContext(readFileSync(new URL('../src/' + script, import.meta.url), 'utf8'), context);
  }
  return { store: context.window.TCLCommunication, whatsapp: context.window.TCLWhatsApp, data };
}
const content = { title: 'Échanges & rencontres 🎾', body: 'Texte de test + lien\nDeuxième ligne #club', category: 'Vie du club', link: 'https://tenup.fft.fr/club/60310230?test=1&lang=fr' };
test('WhatsApp conserve accents, sauts de ligne et URL sans injecter de destinataire', () => {
  const { store, whatsapp } = setup();
  const draft = store.save(content);
  const post = store.validate(draft.id);
  const text = whatsapp.approvedText(post.id, post.updatedAt);
  assert.equal(text, ['Tennis Club de Longages', content.title, content.body, content.link].join('\n\n'));
  const url = new URL(whatsapp.shareUrl(text));
  assert.equal(url.origin, 'https://wa.me');
  assert.equal(url.pathname, '/');
  assert.deepEqual([...url.searchParams.keys()], ['text']);
  assert.equal(url.searchParams.get('text'), text);
});
test('Brouillons, imports, modifications et anciennes validations ne sont pas partageables', () => {
  const { store, whatsapp } = setup();
  const draft = store.save(content);
  assert.throws(() => whatsapp.approvedText(draft.id, draft.updatedAt), /plus validée/);
  const post = store.validate(draft.id);
  const backup = store.exportData();
  const target = setup(); target.store.importData(backup);
  assert.throws(() => target.whatsapp.approvedText(post.id, post.updatedAt), /plus validée/);
  const edited = store.save({ ...post, title: 'Autre message' });
  assert.throws(() => whatsapp.approvedText(post.id, post.updatedAt), /plus validée/);
  store.validate(edited.id);
  assert.throws(() => whatsapp.approvedText(post.id, post.updatedAt), /plus validée/);
});
test('Le partage ne change ni les données ni un statut de livraison ; un long texte reste copiable', () => {
  const { store, whatsapp, data } = setup();
  const draft = store.save({ ...content, body: 'Long texte 🎾'.repeat(300) });
  const post = store.validate(draft.id);
  const before = data.get(store.key);
  const text = whatsapp.approvedText(post.id, post.updatedAt);
  assert.equal(whatsapp.shareUrl(text), null);
  assert.match(text, /Long texte/);
  assert.equal(data.get(store.key), before);
  assert.equal(store.exportData().version, 1);
});
