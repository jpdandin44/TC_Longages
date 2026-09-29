import { test } from 'node:test';
import assert from 'node:assert/strict';
import { once } from 'node:events';
import http from 'node:http';
import { createBureauServer } from '../server/bureau-server.mjs';
import { validateAccounts, validateSettings, settingsFromEnv } from '../server/settings.mjs';

// Recette HTTP isolée : aucune connexion à un fournisseur et aucun compte réel.
const issuer = 'https://identity.example.test';
const localSettings = {
  origin: 'http://localhost:4179', issuer, clientId: 'local-test-client',
  clientSecret: 'fictitious-test-client-secret', localHttp: true
};
const initialAccounts = () => [
  { role: 'admin', enabled: true, label: 'Administrateur fictif', subject: 'subject-admin' },
  { role: 'bureau', enabled: true, label: 'Bureau fictif', subject: 'subject-bureau' }
];
const privateRoutes = ['/bureau.html', '/communication.html', '/inscriptions.html', '/actualites-bureau.html', '/admin/acces'];
const minute = 60000;

function rawRequest(server, settings, route, { method = 'GET', headers = {}, body } = {}) {
  return new Promise((resolve, reject) => {
    const request = http.request({
      hostname: '127.0.0.1', port: server.address().port, path: route, method,
      headers: { Host: settings ? new URL(settings.origin).host : 'localhost', ...(settings?.trustLocalProxy ? { 'X-Forwarded-Proto': 'https' } : {}), ...headers }
    }, response => {
      let text = '';
      response.setEncoding('utf8'); response.on('data', chunk => { text += chunk; });
      response.once('end', () => resolve({ status: response.statusCode, headers: response.headers, body: text }));
      response.once('error', reject);
    });
    request.once('error', reject);
    request.setTimeout(5000, () => request.destroy(new Error('Délai dépassé pour le serveur de recette')));
    request.end(body);
  });
}

function readCookie(response, name) {
  const value = (response.headers['set-cookie'] || []).find(item => item.startsWith(`${name}=`));
  assert.ok(value, `Cookie ${name} attendu`);
  return { pair: value.split(';', 1)[0], value: value.slice(name.length + 1).split(';', 1)[0], header: value };
}

async function start(t, { settings = localSettings, beforeFinish } = {}) {
  let clock = Date.UTC(2026, 8, 16, 9);
  let accountList = initialAccounts();
  let accountError = false;
  let sequence = 0;
  let finishCalls = 0;
  let claims = { iss: issuer, sub: 'subject-admin', email: 'admin@example.invalid' };
  const clientFactory = async () => ({
    async begin() {
      const state = `test-state-${++sequence}`;
      return { state, nonce: `test-nonce-${sequence}`, verifier: `test-verifier-${sequence}`, url: `${issuer}/authorize?state=${state}` };
    },
    async finish(url, transaction) {
      finishCalls++;
      if (beforeFinish) await beforeFinish();
      if (url.searchParams.get('state') !== transaction.state || url.searchParams.get('code') !== 'test-code') throw new Error('Faux callback refusé');
      assert.ok(transaction.nonce.startsWith('test-nonce-'));
      assert.ok(transaction.verifier.startsWith('test-verifier-'));
      return { ...claims };
    }
  });
  const server = createBureauServer({
    settings, clientFactory, now: () => clock,
    getAccounts: async requestedIssuer => {
      assert.equal(requestedIssuer, issuer);
      if (accountError) throw new Error('Configuration de recette indisponible');
      return accountList;
    }
  });
  server.listen(0, '127.0.0.1'); await once(server, 'listening');
  t.after(() => new Promise(resolve => { server.close(resolve); server.closeAllConnections(); }));
  const sessionName = settings?.localHttp === true ? 'tcl-session-local' : '__Host-tcl-session';
  const transactionName = settings?.localHttp === true ? 'tcl-login-local' : '__Host-tcl-login';
  const request = (route, options) => rawRequest(server, settings, route, options);
  const begin = async (cookie) => {
    const response = await request('/auth/login', { method: 'POST', headers: { Origin: settings.origin, ...(cookie ? { Cookie: cookie } : {}) } });
    assert.equal(response.status, 303);
    const state = new URL(response.headers.location).searchParams.get('state');
    return { response, state, cookie: readCookie(response, transactionName).pair };
  };
  const callback = (transaction, options = {}) => request(`/auth/callback?code=test-code&state=${encodeURIComponent(transaction.state)}`, { headers: { Cookie: transaction.cookie }, ...options });
  const login = async (nextClaims = { iss: issuer, sub: 'subject-admin' }) => {
    claims = nextClaims;
    const transaction = await begin();
    const response = await callback(transaction);
    return { transaction, response, cookie: response.status === 303 ? readCookie(response, sessionName).pair : undefined };
  };
  return {
    request, begin, callback, login, sessionName, transactionName,
    advance: milliseconds => { clock += milliseconds; },
    accounts: () => accountList,
    replaceAccounts: value => { accountList = value; },
    failAccounts: () => { accountError = true; },
    setClaims: value => { claims = value; },
    finished: () => finishCalls
  };
}

test('OIDC : pages bureau anonymes fermées en GET et HEAD, vitrine et connexion accessibles', async t => {
  const app = await start(t);
  for (const route of privateRoutes) for (const method of ['GET', 'HEAD']) {
    const response = await app.request(route, { method });
    assert.equal(response.status, 303, `${method} ${route}`);
    assert.equal(response.headers.location, '/login');
    assert.equal(response.headers['cache-control'], 'no-store');
    assert.equal(response.body, '');
  }
  const publicPage = await app.request('/index.html');
  assert.equal(publicPage.status, 200);
  assert.doesNotMatch(publicPage.body, /TCLCommunication|localStorage|name="csrf"/);
  const loginPage = await app.request('/login');
  assert.equal(loginPage.status, 200);
  assert.match(loginPage.body, /action="\/auth\/login" method="post"/);
  assert.equal(loginPage.headers['x-frame-options'], 'DENY');
  assert.equal(loginPage.headers['referrer-policy'], 'same-origin');
  assert.match(loginPage.headers['content-security-policy'], /form-action 'self'/);
});

test('OIDC : absence de configuration ferme le service sans exposer de page interne', async t => {
  const app = await start(t, { settings: null });
  for (const route of ['/login', '/auth/callback?code=test-code', ...privateRoutes]) {
    const response = await app.request(route);
    assert.equal(response.status, 503);
    assert.doesNotMatch(response.body, /TCLCommunication|post-form|name="csrf"/);
  }
  assert.equal(app.finished(), 0);
});

test('OIDC : hôte, chemins privés et méthodes ne permettent aucun contournement', async t => {
  const app = await start(t);
  assert.equal((await app.request('/login', { headers: { Host: 'attacker.example.test', 'X-Forwarded-Host': 'localhost:4179' } })).status, 403);
  const response = await app.request('/auth/login', { method: 'POST', headers: { Origin: localSettings.origin, 'X-Forwarded-Host': 'attacker.example.test', 'X-Forwarded-Proto': 'https' } });
  assert.equal(new URL(response.headers.location).origin, issuer);
  for (const path of ['/.local/oidc-accounts.json', '/.env', '/server/settings.mjs', '/dist/bureau.html', '/src/inscriptions.html', '/%62ureau.html', '/%2e%2e/.local/oidc-accounts.json']) {
    assert.equal((await app.request(path)).status, 404, path);
  }
  for (const [path, method, allow] of [['/bureau.html', 'POST', 'GET, HEAD'], ['/auth/login', 'GET', 'POST'], ['/auth/logout', 'GET', 'POST'], ['/auth/callback', 'HEAD', 'GET']]) {
    const denied = await app.request(path, { method });
    assert.equal(denied.status, 405, `${method} ${path}`);
    assert.equal(denied.headers.allow, allow);
  }
  assert.equal((await app.request('/auth/login', { method: 'POST' })).status, 403);
  assert.equal((await app.request('/auth/login', { method: 'POST', headers: { Origin: 'https://attacker.example.test' } })).status, 403);
});

test('OIDC : les deux rôles ont accès au bureau, seul admin voit les accès', async t => {
  const app = await start(t);
  for (const [subject, expectedStatus, label] of [['subject-admin', 200, 'Administrateur fictif'], ['subject-bureau', 403, 'Bureau fictif']]) {
    const login = await app.login({ iss: issuer, sub: subject });
    assert.equal(login.response.status, 303);
    assert.equal(login.response.headers.location, '/bureau.html');
    for (const route of privateRoutes.filter(item => item !== '/admin/acces')) {
      const page = await app.request(route, { headers: { Cookie: login.cookie } });
      assert.equal(page.status, 200, route);
      assert.match(page.body, new RegExp(label));
      assert.match(page.body, /action="\/auth\/logout"/);
      if (subject === 'subject-bureau') assert.doesNotMatch(page.body, /href="\/admin\/acces"/);
    }
    assert.equal((await app.request('/admin/acces', { headers: { Cookie: login.cookie } })).status, expectedStatus);
  }
});

test('OIDC : issuer et subject exacts autorisent, aucun email ou rôle déclaré par le fournisseur', async t => {
  const app = await start(t);
  for (const claims of [
    { iss: issuer, sub: 'unknown-subject', email: 'admin@example.invalid', role: 'admin' },
    { iss: issuer, sub: 'SUBJECT-ADMIN', email: 'admin@example.invalid' },
    { iss: issuer + '/', sub: 'subject-admin' },
    { iss: 'https://other.example.test', sub: 'subject-admin' },
    { iss: issuer, sub: '' },
    { iss: issuer, sub: 123 }
  ]) {
    const login = await app.login(claims);
    assert.equal(login.response.status, 403);
    assert.equal((login.response.headers['set-cookie'] || []).some(value => value.startsWith(`${app.sessionName}=`) && !value.startsWith(`${app.sessionName}=;`)), false);
  }
  const allowed = await app.login({ iss: issuer, sub: 'subject-bureau', email: 'changed@example.invalid', role: 'admin' });
  assert.equal(allowed.response.status, 303);
  assert.equal((await app.request('/admin/acces', { headers: { Cookie: allowed.cookie } })).status, 403);
});

test('OIDC : révocation et configuration invalide prennent effet sur la requête suivante', async t => {
  const app = await start(t);
  const login = await app.login();
  assert.equal(login.response.headers['referrer-policy'], 'no-referrer');
  assert.equal((await app.request('/bureau.html', { headers: { Cookie: login.cookie } })).status, 200);
  app.accounts()[0].enabled = false;
  const revoked = await app.request('/bureau.html', { headers: { Cookie: login.cookie } });
  assert.equal(revoked.status, 403);
  assert.match(readCookie(revoked, app.sessionName).header, /Max-Age=0/);
  assert.equal((await app.request('/bureau.html', { headers: { Cookie: login.cookie } })).status, 303);
  const bureau = await app.login({ iss: issuer, sub: 'subject-bureau' });
  app.replaceAccounts([{ role: 'admin', enabled: true, label: 'Liste incomplète', subject: 'subject-bureau' }]);
  assert.equal((await app.request('/bureau.html', { headers: { Cookie: bureau.cookie } })).status, 503);
  assert.equal((await app.request('/bureau.html', { headers: { Cookie: bureau.cookie } })).status, 303);
});

test('OIDC : cookies de production Secure, HttpOnly, SameSite et préfixe Host, sans secret dans le HTML', async t => {
  const app = await start(t, { settings: { ...localSettings, origin: 'https://club.example.test', localHttp: false, trustLocalProxy: true } });
  const login = await app.login();
  const pending = readCookie(login.transaction.response, app.transactionName);
  const session = readCookie(login.response, app.sessionName);
  for (const cookie of [pending, session]) {
    assert.match(cookie.header, /; Path=\/;/);
    assert.match(cookie.header, /; HttpOnly;/);
    assert.match(cookie.header, /; SameSite=Lax;/);
    assert.match(cookie.header, /; Secure(?:;|$)/);
    assert.doesNotMatch(cookie.header, /Domain=/);
    assert.match(cookie.value, /^[A-Za-z0-9_-]{43}$/);
  }
  assert.match(pending.header, /Max-Age=300/);
  assert.match(session.header, /Max-Age=28800/);
  assert.notEqual(pending.value, session.value);
  const bureau = await app.request('/bureau.html', { headers: { Cookie: session.pair } });
  assert.doesNotMatch(bureau.body, new RegExp(`${session.value}|${pending.value}|fictitious-test-client-secret`));
});

test('OIDC : configuration HTTPS refuse HTTP direct et en-têtes proxy non autorisés', async t => {
  const production = { ...localSettings, origin: 'https://club.example.test', localHttp: false };
  const strict = await start(t, { settings: production });
  assert.equal((await strict.request('/login')).status, 426);
  assert.equal((await strict.request('/login', { headers: { 'X-Forwarded-Proto': 'https' } })).status, 426);
  const proxy = await start(t, { settings: { ...production, trustLocalProxy: true } });
  assert.equal((await proxy.request('/login')).status, 200);
  for (const protocol of ['', 'http', 'https,http', 'HTTPS']) {
    assert.equal((await proxy.request('/login', { headers: { 'X-Forwarded-Proto': protocol } })).status, 426);
  }
});

test('OIDC : cookie de transaction nécessaire, état lié et transaction utilisable une seule fois', async t => {
  const app = await start(t);
  const transaction = await app.begin();
  assert.equal((await app.callback(transaction, { headers: {} })).status, 400);
  assert.equal(app.finished(), 0);
  assert.equal((await app.callback(transaction)).status, 303);
  assert.equal((await app.callback(transaction)).status, 400);
  assert.equal(app.finished(), 1);
  const other = await app.begin();
  assert.equal((await app.callback({ ...other, state: 'wrong-state' })).status, 400);
  assert.equal((await app.callback(other)).status, 400);
  assert.equal(app.finished(), 2);
  const previous = await app.begin();
  const replacement = await app.begin(previous.cookie);
  assert.equal((await app.callback(previous)).status, 400);
  assert.equal((await app.callback(replacement)).status, 303);
});

test('OIDC : callback concurrent consommé avant échange et expiration de transaction à cinq minutes', { timeout: 10000 }, async t => {
  let release;
  let started;
  const startedPromise = new Promise(resolve => { started = resolve; });
  const gate = new Promise(resolve => { release = resolve; });
  const app = await start(t, { beforeFinish: async () => { started(); await gate; } });
  const transaction = await app.begin();
  const first = app.callback(transaction);
  await startedPromise;
  assert.equal((await app.callback(transaction)).status, 400);
  release();
  assert.equal((await first).status, 303);
  assert.equal(app.finished(), 1);
  const expired = await app.begin();
  app.advance(5 * minute);
  assert.equal((await app.callback(expired)).status, 400);
  assert.equal(app.finished(), 1);
});

test('OIDC : session renouvelée sans fixation et cookies ambigus refusés', async t => {
  const app = await start(t);
  const first = await app.login();
  const pending = await app.begin(first.cookie);
  assert.equal((await app.request('/bureau.html', { headers: { Cookie: first.cookie } })).status, 303);
  const replacement = await app.callback(pending);
  const next = readCookie(replacement, app.sessionName).pair;
  assert.notEqual(next, first.cookie);
  assert.equal((await app.request('/bureau.html', { headers: { Cookie: `${next}; ${next}` } })).status, 303);
  assert.equal((await app.request('/bureau.html', { headers: { Cookie: next } })).status, 200);
  assert.equal((await app.request('/bureau.html', { headers: { Cookie: `${app.sessionName}=invalid-token` } })).status, 303);
});

test('OIDC : déconnexion exige POST, origine et jeton CSRF, puis invalide la session serveur', async t => {
  const app = await start(t);
  const login = await app.login();
  const page = await app.request('/bureau.html', { headers: { Cookie: login.cookie } });
  const csrf = page.body.match(/name="csrf" value="([A-Za-z0-9_-]+)"/)?.[1];
  assert.match(csrf, /^[A-Za-z0-9_-]{43}$/);
  const headers = { Cookie: login.cookie, Origin: localSettings.origin, 'Content-Type': 'application/x-www-form-urlencoded' };
  assert.equal((await app.request('/auth/logout', { headers })).status, 405);
  for (const invalidHeaders of [
    { ...headers, Origin: '' }, { ...headers, Origin: 'https://attacker.example.test' }, { ...headers, 'Content-Type': 'application/json' }
  ]) assert.equal((await app.request('/auth/logout', { method: 'POST', headers: invalidHeaders, body: `csrf=${csrf}` })).status, 403);
  assert.equal((await app.request('/auth/logout', { method: 'POST', headers, body: 'csrf=wrong' })).status, 403);
  assert.equal((await app.request('/bureau.html', { headers: { Cookie: login.cookie } })).status, 200);
  const logout = await app.request('/auth/logout', { method: 'POST', headers, body: `csrf=${csrf}` });
  assert.equal(logout.status, 200);
  assert.match(readCookie(logout, app.sessionName).header, /Max-Age=0/);
  assert.equal((await app.request('/bureau.html', { headers: { Cookie: login.cookie } })).status, 303);
});

test('OIDC : expiration après trente minutes inactives, activité privée seule prolongeant la session', async t => {
  const app = await start(t);
  const login = await app.login();
  const visit = () => app.request('/bureau.html', { headers: { Cookie: login.cookie } });
  app.advance(29 * minute); assert.equal((await visit()).status, 200);
  app.advance(29 * minute); assert.equal((await visit()).status, 200);
  app.advance(29 * minute); assert.equal((await app.request('/login', { headers: { Cookie: login.cookie } })).status, 200);
  app.advance(minute); assert.equal((await visit()).status, 303);
});

test('OIDC : expiration absolue à huit heures même avec activité régulière', async t => {
  const app = await start(t);
  const login = await app.login();
  for (let index = 0; index < 16; index++) {
    app.advance(29 * minute);
    assert.equal((await app.request('/bureau.html', { headers: { Cookie: login.cookie } })).status, 200);
  }
  app.advance(16 * minute);
  assert.equal((await app.request('/bureau.html', { headers: { Cookie: login.cookie } })).status, 303);
});

test('OIDC : tentatives de connexion limitées puis réouvertes, sans bloquer la vitrine', async t => {
  const app = await start(t);
  for (let index = 0; index < 10; index++) await app.begin();
  const limited = await app.request('/auth/login', { method: 'POST', headers: { Origin: localSettings.origin } });
  assert.equal(limited.status, 429);
  assert.equal(limited.headers['retry-after'], '60');
  assert.equal((await app.request('/index.html')).status, 200);
  app.advance(minute);
  assert.equal((await app.begin()).response.status, 303);
});

test('OIDC : paramètres refusent HTTP distant, secrets absents et origines non canoniques', () => {
  const valid = validateSettings(localSettings);
  assert.equal(valid.redirectUri, localSettings.origin + '/auth/callback');
  assert.equal(valid.localHttp, true);
  assert.equal(Object.isFrozen(valid), true);
  assert.equal(validateSettings({ ...localSettings, origin: 'https://club.example.test', localHttp: false }).localHttp, false);
  for (const patch of [
    { origin: 'http://club.example.test' }, { origin: 'http://localhost:4179', localHttp: false },
    { origin: 'https://club.example.test/' }, { origin: 'https://club.example.test/subpath' },
    { origin: 'https://username:password@club.example.test' }, { issuer: 'http://identity.example.test' },
    { issuer: 'https://identity.example.test?redirect=other' }, { issuer: 'https://identity.example.test#fragment' },
    { clientId: '' }, { clientSecret: ' ' }
  ]) assert.throws(() => validateSettings({ ...localSettings, ...patch }));
  assert.throws(() => settingsFromEnv({}));
  const fromEnv = settingsFromEnv({ TCL_PUBLIC_BASE_URL: localSettings.origin, TCL_OIDC_ISSUER: issuer, TCL_OIDC_CLIENT_ID: 'test', TCL_OIDC_CLIENT_SECRET: 'fictitious', TCL_OIDC_LOCAL_HTTP: 'true' });
  assert.equal(fromEnv.origin, localSettings.origin);
});

test('OIDC : liste exacte de deux comptes et rôles distincts, identité obligatoire seulement si activée', () => {
  const document = { version: 1, issuer, accounts: initialAccounts() };
  assert.deepEqual(validateAccounts(document, issuer), document.accounts);
  for (const accounts of [
    [], [initialAccounts()[0]], [...initialAccounts(), initialAccounts()[0]],
    [{ ...initialAccounts()[0] }, { ...initialAccounts()[1], role: 'admin' }],
    [{ ...initialAccounts()[0] }, { ...initialAccounts()[1], subject: 'subject-admin' }],
    [{ ...initialAccounts()[0], subject: '' }, initialAccounts()[1]],
    [{ ...initialAccounts()[0], enabled: 'true' }, initialAccounts()[1]],
    [{ ...initialAccounts()[0], subject: 'unsafe\nsubject' }, initialAccounts()[1]],
    [{ ...initialAccounts()[0], role: 'visitor' }, initialAccounts()[1]]
  ]) assert.throws(() => validateAccounts({ ...document, accounts }, issuer));
  assert.throws(() => validateAccounts({ ...document, issuer: issuer + '/' }, issuer));
  const inactive = initialAccounts().map(item => ({ ...item, enabled: false, subject: '' }));
  assert.equal(validateAccounts({ ...document, accounts: inactive }, issuer).length, 2);
});
