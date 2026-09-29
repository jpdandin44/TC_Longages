// Browser QA only: fake identity provider in memory, no provider request or real account.
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const http = require('node:http');
const { once } = require('node:events');
const { randomBytes } = require('node:crypto');
const { mkdir, writeFile } = require('node:fs/promises');
const path = require('node:path');

(async () => {
  const { createBureauServer } = await import('../server/bureau-server.mjs');
  const reservation = http.createServer();
  reservation.listen(0, '127.0.0.1'); await once(reservation, 'listening');
  const port = reservation.address().port;
  await new Promise(resolve => reservation.close(resolve));
  const origin = `http://127.0.0.1:${port}`;
  const issuer = 'https://identity.example.test';
  let subject = 'admin-fiction';
  const accounts = [
    { role: 'admin', label: 'Administrateur fictif', subject, enabled: true },
    { role: 'bureau', label: 'Bureau fictif', subject: 'bureau-fiction', enabled: true }
  ];
  const server = createBureauServer({
    settings: { origin, issuer, clientId: 'fixture', clientSecret: randomBytes(32).toString('base64url'), localHttp: true },
    getAccounts: async () => accounts,
    clientFactory: async () => ({
      begin: async () => ({ url: origin + '/auth/callback?code=fixture', state: 'fixture', nonce: 'fixture', verifier: 'fixture' }),
      finish: async () => ({ iss: issuer, sub: subject })
    })
  });
  server.listen(port, '127.0.0.1'); await once(server, 'listening');
  const browser = await chromium.launch({ channel: process.env.TCL_BROWSER_CHANNEL || 'chrome', headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
  context.setDefaultTimeout(10000);
  context.setDefaultNavigationTimeout(10000);
  const page = await context.newPage(), errors = [], external = [], checks = [];
  page.on('pageerror', error => errors.push(error.message));
  await context.route(/^https?:/, route => {
    if (!route.request().url().startsWith(origin + '/')) { external.push(route.request().url()); return route.abort(); }
    return route.continue();
  });
  const output = path.resolve(__dirname, '../docs/recette/oidc');
  await mkdir(output, { recursive: true });
  try {
    await page.goto(origin + '/communication.html');
    assert.equal(new URL(page.url()).pathname, '/login');
    await page.screenshot({ path: path.join(output, 'connexion.png'), fullPage: true });
    await page.getByRole('button', { name: 'Se connecter', exact: true }).click();
    await page.waitForURL(origin + '/bureau.html');
    await page.getByRole('link', { name: 'Les deux accès' }).click();
    assert.equal(await page.locator('td').count(), 6);
    for (const width of [1440, 390, 320]) {
      await page.setViewportSize({ width, height: 900 });
      for (const route of ['/bureau.html', '/communication.html', '/inscriptions.html', '/actualites-bureau.html', '/admin/acces']) {
        const response = await page.goto(origin + route);
        assert.equal(response.status(), 200);
        assert.equal(await page.locator('.tcl-session').count(), 1);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, `${route} width ${width}`);
        checks.push({ route, width, status: 200, overflow: false });
      }
    }
    await page.screenshot({ path: path.join(output, 'acces-mobile.png'), fullPage: true });
    await page.getByRole('button', { name: 'Se déconnecter', exact: true }).click();
    await page.getByRole('heading', { name: 'Vous êtes déconnecté' }).waitFor();
    await page.goto(origin + '/bureau.html');
    assert.equal(new URL(page.url()).pathname, '/login');
    subject = 'bureau-fiction';
    await page.getByRole('button', { name: 'Se connecter', exact: true }).click();
    await page.waitForURL(origin + '/bureau.html');
    assert.equal(await page.getByRole('link', { name: 'Les deux accès' }).count(), 0);
    assert.equal((await page.goto(origin + '/admin/acces')).status(), 403);
    accounts[1].enabled = false;
    assert.equal((await page.goto(origin + '/communication.html')).status(), 403);
    assert.deepEqual(errors, []); assert.deepEqual(external, []);
    const result = { provider: 'simulated-in-memory', checks, admin: true, bureauAdminDenied: true, logout: true, revocation: true, errors, externalRequests: external };
    await writeFile(path.join(output, 'results.json'), JSON.stringify(result, null, 2) + '\n');
    console.log('Recette navigateur OIDC : connexion fictive, rôles, déconnexion, révocation et 3 largeurs OK. Aucun appel fournisseur.');
  } finally {
    await browser.close();
    await new Promise(resolve => { server.close(resolve); server.closeAllConnections(); });
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
