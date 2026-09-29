import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import { readFile, readdir } from 'node:fs/promises';
import { createHash } from 'node:crypto';

const root = new URL('../', import.meta.url);
const read = name => readFile(new URL(name, root), 'utf8');
const pages = ['index.html', 'adherer.html', 'parcours.html', 'bureau.html', 'inscriptions.html', 'communication.html', 'actualites-bureau.html'];
const expected = [...pages, 'robots.txt', '.htaccess', 'maintenance.active'].sort();
const hiddenPages = /(?:parcours|bureau|inscriptions|communication|actualites-bureau)\.html/i;
// Les constructions demo:build puis build-hosted-demo précèdent la recette.
// Ne pas réécrire prototype/ en parallèle des autres fichiers node:test.

test('La démo hébergeable contient exactement dix fichiers autorisés et un manifeste externe fidèle', async () => {
  assert.deepEqual((await readdir(new URL('demo-o2switch/', root))).sort(), expected);
  const manifest = JSON.parse(await read('data/hosted-demo-manifest.json'));
  assert.deepEqual(Object.keys(manifest.files).sort(), expected);
  assert.equal(manifest.defaultState, 'maintenance-closed');
  assert.equal(manifest.authentication, 'none');
  for (const name of expected) {
    const data = await read(`demo-o2switch/${name}`);
    assert.equal(manifest.files[name].bytes, Buffer.byteLength(data));
    assert.equal(manifest.files[name].sha256, createHash('sha256').update(data).digest('hex'));
  }
});

test('Vitrine et formulaire ne contiennent aucune adresse des pages internes, même dans leurs scripts', async () => {
  for (const name of ['index.html', 'adherer.html']) {
    const html = await read(`demo-o2switch/${name}`);
    assert.doesNotMatch(html, hiddenPages);
    assert.match(html, /Essais fictifs/);
    assert.match(html, /href="\.\/adherer\.html"/);
  }
  const index = await read('demo-o2switch/index.html');
  assert.match(index, /id="adherer"[\s\S]*?<a class="button button-light" href="\.\/adherer\.html">Essayer le formulaire fictif/);
  const form = await read('demo-o2switch/adherer.html');
  assert.match(form, /<p id="success-bureau-link"/);
  assert.doesNotMatch(form, /byId\('success-bureau-link'\)\.href\s*=/);
});

test('Le dépôt fictif ne crée aucun lien de gestion après exécution de son gestionnaire JavaScript', async () => {
  const html = await read('demo-o2switch/adherer.html');
  const handler = html.match(/form\.addEventListener\('submit', async \(event\) => \{[\s\S]*?\n  \}\);(?=\n  window\.addEventListener\('beforeunload')/)?.[0];
  assert.ok(handler, 'Le gestionnaire réellement émis doit être présent.');
  for (const persistent of [true, false]) {
    const nodes = new Map();
    const byId = id => {
      if (!nodes.has(id)) nodes.set(id, { checked: true, hidden: false, disabled: false, textContent: '', removeAttribute(name) { delete this[name]; }, setAttribute(name, value) { this[name] = value; }, focus() {}, scrollIntoView() {} });
      return nodes.get(id);
    };
    let submit;
    const context = vm.createContext({
      form: { addEventListener(type, fn) { assert.equal(type, 'submit'); submit = fn; } },
      byId, document: { querySelector: () => byId('success-next') },
      demo: { add: () => ({ id: 'DEMO-TEST' }), isPersistent: () => persistent },
      validateStep: () => true, collect: () => ({}), showStep: () => {},
      showError: message => assert.fail(message), encodeURIComponent
    });
    vm.runInContext(`let submitted = false, hasChanges = true, currentStep = 4; ${handler}`, context);
    await submit({ preventDefault() {} });
    assert.equal(byId('success-reference').textContent, 'DEMO-TEST');
    assert.equal(byId('registration-success').hidden, false);
    assert.equal(byId('form-workspace').hidden, true);
    assert.equal(byId('success-bureau-link').href, undefined);
    for (const node of nodes.values()) assert.doesNotMatch(String(node.href || ''), hiddenPages);
  }
});

test('Les sept pages restent autonomes, non indexables, sans liens locaux cassés', async () => {
  for (const page of pages) {
    const html = await read(`demo-o2switch/${page}`);
    assert.equal((html.match(/<meta name="robots" content="noindex,nofollow">/g) || []).length, 1);
    assert.doesNotMatch(html, /<script[^>]+src=|<link[^>]+rel="stylesheet"|src="(?!data:)/);
    for (const [, link] of html.matchAll(/href="\.\/([^"#?]+)(?:[?#][^"]*)?"/g)) assert.ok(pages.includes(link), `${page} -> ${link}`);
    if (!['index.html', 'adherer.html'].includes(page)) {
      assert.match(html, /Accès par lien · sans authentification · essais fictifs et visuels fournis/);
      for (const target of ['parcours.html', 'bureau.html', 'inscriptions.html', 'communication.html']) assert.ok(html.includes(`href="./${target}"`));
    }
  }
});

test('Le stockage hébergé reste isolé des données du bureau et de la visite locale', async () => {
  const html = await read('demo-o2switch/adherer.html');
  const script = [...html.matchAll(/<script>([\s\S]*?)<\/script>/g)].map(match => match[1]).find(text => text.includes('window.TCLDemo ='));
  assert.ok(script);
  const original = new Map([
    ['tcl.communication.v1', 'original-private'],
    ['tcl.demo.communication.v1', 'original-local-communication'],
    ['tcl.demo.inscriptions.v1', 'original-local-registrations']
  ]);
  const map = new Map(original);
  const context = vm.createContext({ window: {}, localStorage: { getItem: key => map.get(key) || null, setItem: (key, value) => map.set(key, value), removeItem: key => map.delete(key) } });
  vm.runInContext(script, context);
  const demo = context.window.TCLDemo;
  demo.add(demo.all()[0]);
  assert.ok(map.has('tcl.hosted-demo.inscriptions.v1'));
  map.set('tcl.hosted-demo.communication.v1', 'hosted-only');
  demo.reset();
  assert.equal(map.has('tcl.hosted-demo.communication.v1'), false);
  for (const [key, value] of original) assert.equal(map.get(key), value);
  for (const page of pages) assert.doesNotMatch(await read(`demo-o2switch/${page}`), /tcl\.demo\.|['"]tcl\.communication\.v1['"]/);
});

test('La copie hébergée conserve les interceptions WhatsApp et presse-papiers ainsi que l’import réel désactivé', async () => {
  const html = await read('demo-o2switch/communication.html');
  const guard = [...html.matchAll(/<script>([\s\S]*?)<\/script>/g)].map(match => match[1]).find(text => text.includes('window.TCL_DEMONSTRATION = true;'));
  assert.ok(guard);
  const listeners = new Map(); const feedback = {};
  const context = vm.createContext({ window: {}, document: { addEventListener: (type, fn, capture) => { assert.equal(capture, true); listeners.set(type, fn); }, getElementById: id => id === 'feedback' ? feedback : null } });
  vm.runInContext(guard, context);
  for (const id of ['share-whatsapp', 'copy-whatsapp']) {
    let blocked = 0;
    listeners.get('click')({ target: { closest: () => ({ id }) }, preventDefault: () => blocked++, stopImmediatePropagation: () => blocked++ });
    assert.equal(blocked, 2);
    assert.match(feedback.textContent, /Simulation/i);
  }
  const file = { id: 'import-posts', value: 'fictional-filename.json' }; let blocked = 0;
  listeners.get('change')({ target: file, preventDefault: () => blocked++, stopImmediatePropagation: () => blocked++ });
  assert.equal(blocked, 2); assert.equal(file.value, '');
  assert.match(html, /type="file" accept="application\/json,\.json" disabled/);
  assert.match(html, /\$\('import-posts'\)\.disabled = true;/);
  assert.match(html, /tcl\.hosted-demo\.communication\.v1/);
});

test('La configuration Apache ferme la démo par défaut sans exposer les marqueurs ni énumérer les pages', async () => {
  // Contrôle statique uniquement : Apache/o2switch n’est pas exécuté par ce test.
  const config = await read('demo-o2switch/.htaccess');
  assert.match(config, /^Options -Indexes$/m);
  assert.match(config, /^DirectoryIndex index\.html$/m);
  assert.match(config, /^RewriteCond %\{DOCUMENT_ROOT\}\/maintenance\.active -f$/m);
  assert.match(config, /^RewriteRule \^ - \[R=503,L\]$/m);
  assert.match(config, /^ErrorDocument 503 "[\x20-\x7E]+"$/m);
  assert.match(config, /<FilesMatch "\^maintenance\\\.\(active\|inactive\)\$">\s+Require all denied\s+<\/FilesMatch>/);
  assert.match(config, /Header always set Cache-Control "no-store"/);
  assert.match(config, /Header always set X-Robots-Tag "noindex, nofollow"/);
  assert.doesNotMatch(config, /AuthType|AuthUserFile|https:\/\//);
  assert.equal(await read('demo-o2switch/robots.txt'), 'User-agent: *\nDisallow: /\n');
});
