import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { webcrypto } from 'node:crypto';

function setup() {
  const data = new Map();
  const storage = { getItem: key => data.get(key) ?? null, setItem: (key, value) => data.set(key, value) };
  const context = vm.createContext({ window: { crypto: webcrypto }, localStorage: storage, URL });
  vm.runInContext(readFileSync(new URL('../src/communication-store.js', import.meta.url), 'utf8'), context);
  return { api: context.window.TCLCommunication, storage, data };
}
const content = { title: 'Actualité de test', body: 'Texte de recette uniquement.', category: 'Vie du club', link: '' };
test('Un brouillon ne devient visible qu’après validation ; une modification exige un nouvel accord', () => {
  const { api } = setup();
  const draft = api.save(content);
  assert.equal(api.publicPosts().length, 0);
  const validated = api.validate(draft.id, draft.updatedAt);
  assert.equal(validated.facebookStatus, 'simulated');
  assert.equal(api.publicPosts().length, 1);
  assert.throws(() => api.validate(draft.id), /déjà validée/);
  const edited = api.save({ ...validated, title: 'Texte modifié' });
  assert.equal(edited.status, 'draft');
  assert.equal(edited.facebookStatus, 'not-sent');
  assert.equal(api.publicPosts().length, 0);
});
test('L’import ne contourne pas la validation et préserve un conflit sous forme de copie', () => {
  const first = setup();
  const original = first.api.save(content);
  first.api.validate(original.id);
  const second = setup();
  const backup = first.api.exportData();
  assert.equal(second.api.importData(backup).imported, 1);
  assert.equal(second.api.publicPosts().length, 0);
  assert.equal(second.api.importData(backup).skipped, 1);
  backup.posts[0].body = 'Texte concurrent';
  const result = second.api.importData(backup);
  assert.equal(result.conflicts, 1);
  assert.equal(second.api.read().length, 2);
  assert.equal(second.api.read()[0].body, content.body);
});
test('Un import invalide, des URLs dangereuses ou un dépassement ne remplacent pas les données', () => {
  const { api, data } = setup();
  api.save(content);
  const before = data.get(api.key);
  for (const link of ['javascript:alert(1)', 'http://example.org', 'https://user:password@example.org']) {
    assert.throws(() => api.save({ ...content, link }));
  }
  assert.throws(() => api.save({ ...content, title: 'x'.repeat(121) }));
  assert.throws(() => api.importData({ version: 1, posts: [{ id: '__proto__' }] }));
  assert.equal(data.get(api.key), before);
});
test('Une écriture refusée ou un stockage corrompu restent explicites et sans effacement', () => {
  const { api, data, storage } = setup();
  api.save(content);
  const before = data.get(api.key);
  storage.setItem = () => { throw new Error('QuotaExceeded'); };
  assert.throws(() => api.save(content), /Enregistrement impossible/);
  assert.equal(data.get(api.key), before);
  data.set(api.key, '{invalide');
  assert.throws(() => api.read(), /illisible/);
  assert.throws(() => api.save(content), /illisible/);
  assert.equal(data.get(api.key), '{invalide');
});
test('Un aperçu devenu obsolète ne peut pas valider une autre révision', () => {
  const { api } = setup();
  const post = api.save(content);
  assert.throws(() => api.validate(post.id, '2000-01-01T00:00:00.000Z'), /changé depuis votre aperçu/);
  assert.throws(() => api.save({ ...post, updatedAt: '2000-01-01T00:00:00.000Z' }), /autre onglet/);
  assert.equal(api.publicPosts().length, 0);
});
test('Deux cents longs textes Unicode ou échappés restent lisibles et réimportables', () => {
  const bodies = [
    'é'.repeat(5000),
    'a' + '\n'.repeat(4998) + 'b',
    'a' + '\\"\n\t'.repeat(1249) + 'xyz'
  ];
  for (const body of bodies) {
    const source = setup();
    for (let index = 0; index < 200; index++) {
      source.api.save({ ...content, title: 'Actualité ' + index, body });
    }
    assert.equal(source.api.read().length, 200);
    assert.equal(source.api.read()[199].body, body);
    const stored = source.data.get(source.api.key);
    const backup = JSON.stringify(source.api.exportData(), null, 2);
    assert.ok(Buffer.byteLength(backup, 'utf8') > 2_000_000);
    assert.ok(Buffer.byteLength(backup, 'utf8') <= source.api.maxImportBytes);
    assert.ok(stored.length <= source.api.maxImportBytes);
    assert.equal(source.api.maxImportBytes, 8_000_000);
    const destination = setup();
    assert.equal(destination.api.importData(JSON.parse(backup)).imported, 200);
    assert.equal(destination.api.read().length, 200);
    assert.equal(destination.api.read()[199].body, body);
  }
});
function testImage(size = 32, alt = 'Affiche de recette') {
  const bytes = Buffer.alloc(size); bytes.set([255,216,255,192,0,17,8,0,100,0,100,3,1,0,0,2,0,0,3,0,0]);
  return { dataUrl: 'data:image/jpeg;base64,' + bytes.toString('base64'), alt, name: 'affiche-test.jpg' };
}
test('Une affiche seule est enregistrée, relue et réimportée ; toute modification exige un nouvel accord', () => {
  const { api } = setup();
  const post = api.save({ ...content, body: '', image: testImage() });
  const approved = api.validate(post.id, post.updatedAt);
  assert.equal(api.publicPosts()[0].image.alt, 'Affiche de recette');
  const changed = api.save({ ...approved, image: { ...approved.image, alt: 'Description corrigée' } });
  assert.equal(changed.status, 'draft');
  assert.equal(api.publicPosts().length, 0);
  assert.throws(() => api.validate(post.id, approved.updatedAt), /changé depuis votre aperçu/);
  const replacement = api.save({ ...changed, image: testImage(48) });
  assert.equal(replacement.status, 'draft');
  assert.throws(() => api.save({ ...replacement, image: null }), /Texte/);
  const another = setup();
  another.api.importData(api.exportData());
  assert.equal(another.api.read()[0].image.dataUrl, replacement.image.dataUrl);
  assert.equal(another.api.publicPosts().length, 0);
});
test('Les anciens brouillons sans champ image restent lisibles et compatibles avec les doublons JSON', () => {
  const { api, data } = setup();
  const post = api.save(content);
  delete post.image;
  data.set(api.key, JSON.stringify({ version: 1, posts: [post] }));
  assert.equal(api.read()[0].image, undefined);
  assert.equal(api.importData({ version: 1, posts: [{ ...post, image: null }] }).skipped, 1);
  const upgraded = api.save({ ...post, image: testImage() });
  assert.equal(upgraded.image.alt, 'Affiche de recette');
});
test('Une image invalide ou trop volumineuse et le cumul excessif préservent le stockage', () => {
  const { api, data } = setup();
  api.save(content);
  const before = data.get(api.key);
  for (const image of [testImage(500_001), testImage(32, ''), { ...testImage(), dataUrl: 'https://example.org/image.jpg' }, { ...testImage(), dataUrl: 'data:image/svg+xml;base64,PHN2Zz4=' }, { ...testImage(), dataUrl: 'data:image/png;base64,' + Buffer.alloc(30).toString('base64') }]) {
    assert.throws(() => api.save({ ...content, image }));
    assert.equal(data.get(api.key), before);
  }
  for (let index = 0; index < 4; index++) api.save({ ...content, image: testImage(500_000) });
  const full = data.get(api.key);
  assert.throws(() => api.save({ ...content, image: testImage() }), /réserve de 2 Mo/);
  assert.equal(data.get(api.key), full);
});
test('Le quota navigateur conserve aussi la révision validée et son affiche', () => {
  const { api, data, storage } = setup();
  const post = api.save({ ...content, image: testImage() });
  const approved = api.validate(post.id, post.updatedAt);
  const before = data.get(api.key);
  storage.setItem = () => { throw new Error('QuotaExceeded'); };
  assert.throws(() => api.save({ ...approved, image: testImage(64) }), /dernière sauvegarde reste intacte/);
  assert.equal(data.get(api.key), before);
  assert.equal(api.publicPosts()[0].image.dataUrl, approved.image.dataUrl);
});
test('Un import JSON ne contourne pas la limite de dimensions des images préparées', () => {
  const { api, data } = setup();
  api.save(content);
  const before = data.get(api.key);
  const png = Buffer.alloc(33); png.set([137,80,78,71,13,10,26,10]); png.write('IHDR', 12);
  for (const width of [1601, 16001]) {
    png.writeUInt32BE(width, 16); png.writeUInt32BE(width, 20);
    const backup = api.exportData();
    backup.posts[0].image = { dataUrl: 'data:image/png;base64,' + png.toString('base64'), alt: 'Image trop grande', name: 'trop-grande.png' };
    assert.throws(() => api.importData(backup), /1 600 pixels/);
    assert.equal(data.get(api.key), before);
  }
});
