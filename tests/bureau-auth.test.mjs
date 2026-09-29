import { test } from 'node:test';
import assert from 'node:assert/strict';
import { once } from 'node:events';
import { randomBytes } from 'node:crypto';
import http from 'node:http';
import { createPreviewServer } from '../scripts/preview-server.mjs';
import { passwordRecord, validateUsers } from '../scripts/bureau-auth.mjs';

const password = randomBytes(24).toString('base64url');
const hash = await passwordRecord(password);
const member = { login: 'recette', role: 'bureau', enabled: true, ...hash };
const authorization = (login = member.login, secret = password) => 'Basic ' + Buffer.from(`${login}:${secret}`).toString('base64');
const routes = ['/bureau.html', '/communication.html', '/inscriptions.html', '/actualites-bureau.html'];
async function start(t, getUsers = async () => [member]) {
  const server = createPreviewServer({ getUsers });
  server.listen(0, '127.0.0.1'); await once(server, 'listening');
  t.after(() => new Promise(resolve => { server.close(resolve); server.closeAllConnections(); }));
  return { server, origin: `http://127.0.0.1:${server.address().port}` };
}
test('Accès anonyme refusé sur toutes les pages du bureau, y compris HEAD et URL directe', async t => {
  const { origin } = await start(t);
  for (const route of routes) for (const method of ['GET', 'HEAD']) {
    const response = await fetch(origin + route, { method });
    assert.equal(response.status, 401);
    assert.match(response.headers.get('www-authenticate'), /Basic/);
    assert.equal(response.headers.get('cache-control'), 'no-store');
    const body = await response.text();
    assert.doesNotMatch(body, /post-form|TCLCommunication|waiting-panel|news-list/);
    if (method === 'HEAD') assert.equal(body, '');
  }
});
test('Configuration absente, invalide ou sans membre actif : fermeture par défaut', async t => {
  for (const getUsers of [async () => { throw new Error('ENOENT'); }, async () => [], async () => [{}], async () => [{ ...member, enabled: false }]]) {
    const { origin } = await start(t, getUsers);
    const response = await fetch(origin + '/communication.html', { headers: { Authorization: authorization() } });
    assert.equal(response.status, 503);
    assert.doesNotMatch(await response.text(), /post-form|TCLCommunication/);
    assert.equal((await fetch(origin + '/')).status, 200);
  }
});
test('Compte actif bureau accepté, mauvais mot de passe, rôle ou compte désactivé refusés', async t => {
  const users = [member, { ...member, login: 'inactive', enabled: false }, { ...member, login: 'visitor', role: 'visiteur' }];
  const { origin } = await start(t, async () => users);
  for (const credential of [authorization('inconnu'), authorization(member.login, 'incorrect'), authorization('inactive'), authorization('visitor'), 'Basic !!!!']) {
    assert.equal((await fetch(origin + '/inscriptions.html', { headers: { Authorization: credential } })).status, 401);
  }
  for (const route of routes) {
    const response = await fetch(origin + route, { headers: { Authorization: authorization() } });
    assert.equal(response.status, 200);
    assert.equal(response.headers.get('cache-control'), 'no-store');
  }
});
test('Révocation prise en compte à la prochaine requête sans redémarrage', async t => {
  const users = [{ ...member }, { ...member, login: 'autre' }];
  const { origin } = await start(t, async () => users);
  const request = () => fetch(origin + '/communication.html', { headers: { Authorization: authorization() } });
  assert.equal((await request()).status, 200);
  users[0].enabled = false;
  assert.equal((await request()).status, 401);
});
test('Vitrine publique sans lecteur de brouillons et aucun fichier privé exposé', async t => {
  const { origin } = await start(t);
  const html = await (await fetch(origin + '/')).text();
  assert.doesNotMatch(html, /localStorage|TCLCommunication|news-list|post-form/);
  for (const route of ['/.local/bureau-users.json', '/data/bureau-users.example.json', '/src/communication.html', '/dist/communication.html', '/%63ommunication.html', '/%2e%2e/.local/bureau-users.json']) {
    assert.equal((await fetch(origin + route)).status, 404);
  }
  assert.equal((await fetch(origin + '/communication.html', { method: 'POST' })).status, 405);
  const code = await new Promise(resolve => {
    http.get(origin + '/', { headers: { Host: 'attacker.example' } }, res => { res.resume(); resolve(res.statusCode); });
  });
  assert.equal(code, 403);
});
test('Mots de passe hachés avec sels distincts et configurations incohérentes refusées', async () => {
  const other = await passwordRecord(password);
  assert.notEqual(hash.salt, other.salt);
  assert.notEqual(hash.passwordHash, other.passwordHash);
  assert.throws(() => validateUsers({ version: 1, users: [member, member] }));
  assert.throws(() => validateUsers({ version: 1, users: [{ ...member, passwordHash: '1234' }] }));
  assert.throws(() => validateUsers({ version: 1, users: [{ ...member, enabled: 'true' }] }));
});
test('Tentatives incorrectes répétées ralenties sans bloquer la vitrine', async t => {
  const { origin } = await start(t);
  for (let i = 0; i < 10; i++) assert.equal((await fetch(origin + '/bureau.html', { headers: { Authorization: 'Basic !!!!' } })).status, 401);
  const response = await fetch(origin + '/bureau.html', { headers: { Authorization: authorization() } });
  assert.equal(response.status, 429);
  assert.equal(response.headers.get('retry-after'), '60');
  assert.equal((await fetch(origin + '/')).status, 200);
});
test('Les échecs concurrents restent comptabilisés après le calcul du mot de passe', async t => {
  const { origin } = await start(t);
  for (let batch = 0; batch < 3; batch++) {
    const responses = await Promise.all(Array.from({ length: 4 }, () => fetch(origin + '/bureau.html', { headers: { Authorization: authorization(member.login, 'incorrect') } })));
    for (const response of responses) assert.ok([401, 429].includes(response.status));
  }
  assert.equal((await fetch(origin + '/bureau.html', { headers: { Authorization: authorization() } })).status, 429);
});
