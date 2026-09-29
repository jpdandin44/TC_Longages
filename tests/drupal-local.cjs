// Deliberate local integration test: toggles Drupal maintenance and restores it.
// No credentials or one-time login URLs are printed or included in the report.
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const project = path.resolve(__dirname, '..');
const origin = 'http://127.0.0.1:4182';
const pages = ['index', 'competitions', 'calendrier', 'disponibilites', 'equipes', 'espace', 'contact'];
const checks = [];
const report = { checkedAt: new Date().toISOString(), scope: 'local-loopback-only', origin, deployed: false, checks };
let browser, admin, maintenanceRestored = false;

async function httpCheck(route, status, label, expectedText) {
  const response = await fetch(origin + route, { redirect: 'manual' });
  assert.equal(response.status, status, label);
  const body = await response.text();
  if (expectedText) assert.ok(body.includes(expectedText), label + ' body');
  checks.push({ label, route, status: response.status, passed: true });
  return { body, response };
}

async function setMaintenance(enabled) {
  await admin.goto(origin + '/admin/config/development/maintenance');
  const checkbox = admin.getByLabel('Put site into maintenance mode', { exact: true });
  await checkbox.setChecked(enabled);
  await Promise.all([
    admin.waitForURL('**/admin/config/development/maintenance'),
    admin.getByRole('button', { name: 'Save configuration', exact: true }).click(),
  ]);
  await admin.getByText('The configuration options have been saved.', { exact: true }).waitFor();
  assert.equal(await checkbox.isChecked(), enabled);
  checks.push({ label: `Native administrative form: maintenance ${enabled ? 'enabled' : 'disabled'}`, passed: true });
}

(async () => {
  const secrets = JSON.parse(fs.readFileSync(path.join(project, '.local/drupal-admin.json'), 'utf8'));
  for (const page of pages) await httpCheck('/' + page + '.html', 503, `Closed ${page}`, 'en maintenance');
  await httpCheck('/', 503, 'Closed home');
  await httpCheck('/user/login', 200, 'Login accessible during maintenance', 'user-login-form');
  browser = await chromium.launch({ headless: true, channel: 'msedge' });
  const context = await browser.newContext();
  admin = await context.newPage();
  await admin.goto(origin + '/user/login');
  await admin.getByLabel('Username', { exact: true }).fill(secrets.username);
  await admin.getByLabel('Password', { exact: true }).fill(secrets.password);
  delete secrets.password;
  await admin.getByRole('button', { name: 'Log in', exact: true }).click();
  await admin.waitForURL(/\/user\/(?!login)/);
  checks.push({ label: 'Local administrator password login', passed: true });
  for (const page of pages) {
    const response = await context.request.get(origin + '/' + page + '.html');
    assert.equal(response.status(), 200, 'Administrator maintenance exemption ' + page);
    checks.push({ label: 'Administrator maintenance exemption ' + page, passed: true });
  }
  await setMaintenance(false);
  for (const page of pages) {
    const { body, response } = await httpCheck('/' + page + '.html', 200, `Open ${page}`);
    assert.equal(body, fs.readFileSync(path.join(project, 'drupal/site-pages/' + page + '.html'), 'utf8'));
    assert.match(response.headers.get('x-robots-tag'), /noindex/);
    assert.match(response.headers.get('cache-control'), /no-store/);
  }
  await httpCheck('/', 200, 'Open home');
  const unauthorized = await fetch(origin + '/admin/config/development/maintenance', { redirect: 'manual' });
  assert.equal(unauthorized.status, 403);
  checks.push({ label: 'Anonymous access to maintenance settings denied', passed: true });
  const post = await fetch(origin + '/contact.html', { method: 'POST', body: 'test=local' });
  assert.equal(post.status, 405);
  checks.push({ label: 'Public static form POST not accepted by page route', passed: true });
  for (const route of ['/site-pages/index.html', '/sites/default/settings.php', '/composer.json', '/vendor/autoload.php', '/local-router.php', '/.local/drupal-admin.json', '/bureau.html', '/communication.html', '/inscriptions.html']) {
    await httpCheck(route, 404, 'No direct access ' + route);
  }
  await setMaintenance(true);
  maintenanceRestored = true;
  for (const page of pages) await httpCheck('/' + page + '.html', 503, `Reclosed ${page}`);
  report.passed = true;
})().catch(error => {
  report.passed = false;
  report.error = String(error.message).replace(/user\/reset\/\S+/g, '[redacted]');
  process.exitCode = 1;
}).finally(async () => {
  if (admin && !maintenanceRestored) {
    try { await setMaintenance(true); maintenanceRestored = true; }
    catch { report.restoreError = 'Maintenance restoration through administrator form failed; local CLI intervention required.'; }
  }
  report.maintenanceRestored = maintenanceRestored;
  report.checkCount = checks.length;
  fs.writeFileSync(path.join(project, '.local/drupal-http-results.json'), JSON.stringify(report, null, 2) + '\n');
  if (browser) await browser.close();
  console.log(JSON.stringify({ passed: report.passed, checks: report.checkCount, maintenanceRestored, report: '.local/drupal-http-results.json', error: report.error }));
});
