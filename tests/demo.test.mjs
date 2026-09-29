import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import http from 'node:http';
import { readFile } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { createDemoServer } from '../scripts/demo-server.mjs';
import { compileDemoData } from '../scripts/tariffs.mjs';

const root = new URL('../', import.meta.url);
execFileSync(process.execPath, [fileURLToPath(new URL('scripts/build-demo.mjs', root))]);

test('La démonstration ne lit ni ne remplace le stockage de communication habituel', async () => {
  const map = new Map([['tcl.communication.v1', 'private-original']]);
  const context = vm.createContext({ window: {}, localStorage: { getItem: k => map.get(k) || null, setItem: (k,v) => map.set(k,v), removeItem: k => map.delete(k) } });
  vm.runInContext(compileDemoData(await readFile(new URL('src/demo-data.js', root), 'utf8')), context);
  const demo = context.window.TCLDemo;
  const input = demo.all()[0];
  const added = demo.add({ ...input, status: 'finalise', groupId: 'injected' });
  assert.equal(added.status, 'recu'); assert.equal(added.groupId, null);
  assert.equal(demo.all().length, 6);
  demo.reset(); assert.equal(demo.all().length, 5);
  assert.equal(map.get('tcl.communication.v1'), 'private-original');
});

test('La démo refuse les affectations incompatibles et la finalisation avant groupe', async () => {
  const map = new Map();
  const context = vm.createContext({ window: {}, localStorage: { getItem:k=>map.get(k)||null,setItem:(k,v)=>map.set(k,v),removeItem:k=>map.delete(k) } });
  vm.runInContext(compileDemoData(await readFile(new URL('src/demo-data.js', root), 'utf8')), context);
  const demo = context.window.TCLDemo;
  assert.throws(() => demo.update('DEMO-001', { groupId:'g-ex-jeunes' }));
  assert.throws(() => demo.update('DEMO-002', { status:'finalise' }));
  assert.throws(() => demo.update('DEMO-002', { groupId:'g-ex-adultes' }));
  demo.update('DEMO-002', { groupId:'g-ex-jeunes' });
  assert.equal(demo.update('DEMO-002', { status:'finalise' }).status,'finalise');
});

test('Les sept pages de visite sont autonomes, sans stockage privé ni liens locaux cassés', async () => {
  const names = new Set(['index.html','parcours.html','adherer.html','bureau.html','inscriptions.html','communication.html','actualites-bureau.html']);
  for (const name of names) {
    const html = await readFile(new URL('prototype/'+name, root),'utf8');
    assert.match(html,/DÉMONSTRATION/);
    assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+rel="stylesheet"|src="(?!data:)|'tcl\.communication\.v1'/);
    for (const [,link] of html.matchAll(/href="\.\/([^"#?]+)(?:[?#][^"]*)?"/g)) assert.ok(names.has(link), `${name} -> ${link}`);
  }
});

test('Le serveur de visite sert uniquement les pages fictives, refuse fichiers privés et écritures', async () => {
  const server = createDemoServer();
  await new Promise(resolve => server.listen(0,'127.0.0.1',resolve));
  const origin = `http://127.0.0.1:${server.address().port}`;
  try {
    for (const page of ['','index.html','parcours.html','adherer.html','bureau.html','inscriptions.html','communication.html','actualites-bureau.html']) assert.equal((await fetch(origin+'/'+page)).status,200);
    for (const target of ['.local/bureau-users.json','serveur-demo.mjs','manifest.json','../.env']) assert.equal((await fetch(origin+'/'+target)).status,404);
    assert.equal((await fetch(origin+'/inscriptions.html',{method:'POST'})).status,405);
    const foreignHostStatus = await new Promise((resolve,reject) => {
      const request = http.get(origin+'/', { headers:{Host:'outside.invalid'} }, response => { response.resume(); resolve(response.statusCode); });
      request.on('error',reject);
    });
    assert.equal(foreignHostStatus,403);
    assert.equal((await fetch(origin+'/')).headers.get('Cache-Control'),'no-store');
  } finally { server.closeAllConnections(); await new Promise(resolve=>server.close(resolve)); }
});
