// Recette des HTML uniquement. Ce serveur éphémère n’interprète pas .htaccess.
// Les requêtes externes sont bloquées et les ouvertures/copies instrumentées.
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const http = require('node:http');
const { mkdir, readFile, writeFile } = require('node:fs/promises');
const path = require('node:path');

(async () => {
  const directory = path.resolve(__dirname, '../demo-o2switch');
  const output = path.resolve(__dirname, '../docs/recette/demo-o2switch');
  const pages = new Set(['index.html', 'adherer.html', 'parcours.html', 'bureau.html', 'inscriptions.html', 'communication.html', 'actualites-bureau.html']);
  const errors = [], external = [], checks = [], captures = [];
  await mkdir(output, { recursive: true });
  const server = http.createServer(async (req, res) => {
    const pathname = new URL(req.url, 'http://127.0.0.1').pathname;
    const name = pathname === '/' ? 'index.html' : pathname.slice(1);
    res.setHeader('Cache-Control', 'no-store');
    if (!['GET', 'HEAD'].includes(req.method)) { res.writeHead(405); res.end(); return; }
    if (!pages.has(name)) { res.writeHead(404); res.end(); return; }
    try {
      const content = await readFile(path.join(directory, name));
      res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
      res.end(req.method === 'HEAD' ? undefined : content);
    } catch { res.writeHead(503); res.end(); }
  });
  await new Promise((resolve, reject) => { server.once('error', reject); server.listen(0, '127.0.0.1', resolve); });
  const origin = `http://127.0.0.1:${server.address().port}`;
  let browser;
  const saveResult = async (status, failure) => {
    const result = { date: new Date().toISOString(), status, scope: 'HTML statiques hébergeables servis uniquement sur 127.0.0.1 à port éphémère ; .htaccess non interprété dans cette recette navigateur.', checks, captures, errors, external, ...(failure ? { failure } : {}) };
    await writeFile(path.join(output, 'results.json'), JSON.stringify(result, null, 2) + '\n');
    console.log(JSON.stringify(result, null, 2));
  };
  try {
    browser = await chromium.launch({ channel: process.env.TCL_BROWSER_CHANNEL || 'chrome', headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, acceptDownloads: true });
    await context.route('**/*', route => {
      const url = route.request().url();
      if (url.startsWith(origin + '/') || url.startsWith('data:')) return route.continue();
      external.push(url); return route.abort();
    });
    await context.addInitScript(() => {
      window.__hostedOpened = [];
      window.__hostedCopies = [];
      window.open = (...args) => { window.__hostedOpened.push(args); return null; };
      Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText: async text => { window.__hostedCopies.push(text); } } });
    });
    const page = await context.newPage();
    page.on('pageerror', error => errors.push(error.message));
    page.on('dialog', dialog => dialog.accept());
    async function screenshot(name, locator = page) {
      // Un lien fixed volontairement décalé hors viewport peut apparaître dans une
      // capture Chrome pleine page prise après défilement. Repartir du haut garde
      // les mêmes styles et le même focus tout en évitant cet artefact de capture.
      if (locator === page) {
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
      }
      await locator.screenshot({ path: path.join(output, name), ...(locator === page ? { fullPage: true } : {}) });
      captures.push(name);
    }
    async function assertNoInternalLinks(label) {
      const links = await page.locator('a[href]').evaluateAll(nodes => nodes.map(node => node.getAttribute('href')).filter(href => /(?:parcours|bureau|inscriptions|communication|actualites-bureau)\.html/i.test(href)));
      assert.deepEqual(links, [], label);
    }
    async function submitExample(kind) {
      await page.goto(origin + '/adherer.html');
      await assertNoInternalLinks(`Formulaire ${kind}, avant saisie`);
      await page.locator(kind === 'child' ? '#example-child' : '#example-adult').click();
      for (let step = 0; step < 4; step++) await page.locator('#next-step').click();
      await page.locator('#confirm-fiction').check();
      await page.locator('#next-step').click();
      await page.locator('#registration-success').waitFor({ state: 'visible' });
      const id = await page.locator('#success-reference').textContent();
      assert.match(id, /^DEMO-\d+$/);
      assert.equal(await page.locator('#success-bureau-link').evaluate(node => node.tagName), 'P');
      await assertNoInternalLinks(`Formulaire ${kind}, après dépôt`);
      return id;
    }
    await page.goto(origin + '/');
    await assertNoInternalLinks('Vitrine');
    assert.equal(await page.locator('#adherer a[href="./adherer.html"]').count(), 1);
    await page.evaluate(() => {
      localStorage.setItem('tcl.communication.v1', 'bureau-original');
      localStorage.setItem('tcl.demo.communication.v1', 'communication-locale-originale');
      localStorage.setItem('tcl.demo.inscriptions.v1', 'inscriptions-locales-originales');
    });
    await screenshot('vitrine-1440.png');
    await screenshot('vitrine-adherer-1440.png', page.locator('#adherer'));
    await page.locator('#adherer a[href="./adherer.html"]').click();
    assert.equal(new URL(page.url()).pathname, '/adherer.html');
    await assertNoInternalLinks('Formulaire initial');
    await page.locator('#next-step').click();
    assert.equal(await page.locator('#form-error').isVisible(), true);
    await page.locator('#example-adult').click();
    await screenshot('formulaire-1440.png');
    // Retour initial avec exemple complété, puis dépôt normal jusqu’à la confirmation.
    for (let step = 0; step < 4; step++) await page.locator('#next-step').click();
    await page.locator('#confirm-fiction').check();
    await page.locator('#next-step').click();
    await page.locator('#registration-success').waitFor({ state: 'visible' });
    const adultId = await page.locator('#success-reference').textContent();
    assert.match(adultId, /^DEMO-\d+$/);
    await assertNoInternalLinks('Formulaire adulte après dépôt');
    assert.equal(await page.locator('#success-bureau-link').evaluate(node => node.tagName), 'P');
    await screenshot('formulaire-depose-1440.png');
    checks.push('Vitrine : bouton formulaire présent ; aucun lien interne de gestion sur les deux pages publiques avant ou après dépôt fictif.');

    await page.goto(`${origin}/inscriptions.html?dossier=${encodeURIComponent(adultId)}`);
    assert.match(await page.locator('#dossier-detail').innerText(), new RegExp(adultId));
    assert.equal(await page.evaluate(id => TCLDemo.all().find(record => record.id === id).training, adultId), false);
    await page.getByRole('button', { name: 'Finaliser le dossier fictif', exact: true }).click();
    await page.locator('#dialog-confirm').click();
    await page.waitForFunction(id => TCLDemo.all().find(record => record.id === id).status === 'finalise', adultId);
    checks.push('Adresse directe inscriptions.html : le dossier adulte déposé est retrouvé puis finalisé après confirmation, sans authentification ni transmission.');

    const childId = await submitExample('child');
    await page.goto(`${origin}/inscriptions.html?dossier=${encodeURIComponent(childId)}`);
    assert.match(await page.locator('#dossier-detail').innerText(), new RegExp(childId));
    assert.equal(await page.getByRole('button', { name: 'Finaliser le dossier fictif', exact: true }).isDisabled(), true);
    await page.locator('#dossier-group').selectOption('g-ex-jeunes');
    await page.getByRole('button', { name: 'Confirmer le choix du groupe', exact: true }).click();
    await page.locator('#dialog-confirm').click();
    await page.waitForFunction(id => TCLDemo.all().find(record => record.id === id).groupId === 'g-ex-jeunes', childId);
    await screenshot('inscriptions-1440.png');
    checks.push('Mineur : responsable et disponibilités conservés ; finalisation bloquée sans groupe, affectation compatible confirmée.');

    await page.goto(origin + '/communication.html');
    assert.equal(await page.locator('#import-posts').isDisabled(), true);
    await page.locator('#post-list button').first().click();
    await page.locator('#preview-facebook').click();
    assert.equal(await page.locator('#facebook-header').isVisible(), true);
    assert.match(await page.locator('#facebook-header').innerText(), /aucune publication/);
    await page.locator('#review-post').click();
    await page.locator('#confirm-approval').click();
    await page.locator('#share-whatsapp').click();
    assert.match(await page.locator('#feedback').innerText(), /Simulation/);
    await page.locator('#copy-whatsapp').click();
    assert.match(await page.locator('#feedback').innerText(), /Simulation de copie/);
    assert.deepEqual(await page.evaluate(() => [window.__hostedOpened, window.__hostedCopies]), [[], []]);
    assert.deepEqual(await page.evaluate(() => [localStorage.getItem('tcl.communication.v1'), localStorage.getItem('tcl.demo.communication.v1'), localStorage.getItem('tcl.demo.inscriptions.v1')]), ['bureau-original', 'communication-locale-originale', 'inscriptions-locales-originales']);
    assert.equal(await page.evaluate(() => localStorage.getItem('tcl.hosted-demo.communication.v1') !== null && localStorage.getItem('tcl.hosted-demo.inscriptions.v1') !== null), true);
    await screenshot('communication-simulee-1440.png');
    await page.goto(origin + '/actualites-bureau.html');
    assert.match(await page.locator('body').innerText(), /Bienvenue dans la démonstration/);
    checks.push('Communication : aperçu Facebook et validation locale ; WhatsApp/copie interceptés, aucun nouvel onglet ni accès au presse-papiers, import réel désactivé.');
    checks.push('Clés hébergées présentes ; données privées et données de la visite locale inchangées.');

    for (const width of [390, 320]) {
      await page.setViewportSize({ width, height: 844 });
      for (const name of pages) {
        await page.goto(`${origin}/${name}`);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false, `${name} à ${width} px`);
        if (['index.html', 'adherer.html'].includes(name)) await assertNoInternalLinks(`${name} à ${width} px`);
        if (['index.html', 'adherer.html', 'inscriptions.html'].includes(name)) await screenshot(`${name.replace('.html', '')}-${width}.png`);
      }
      await page.goto(origin + '/adherer.html');
      await page.locator('#example-child').click();
      for (let step = 0; step < 4; step++) {
        await page.locator('#next-step').click();
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false, `Étape ${step + 2} du formulaire à ${width} px`);
      }
      await page.locator('#confirm-fiction').check();
      await page.locator('#next-step').click();
      await page.locator('#registration-success').waitFor({ state: 'visible' });
      await assertNoInternalLinks(`Après dépôt à ${width} px`);
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false, `Confirmation à ${width} px`);
      await screenshot(`formulaire-depose-${width}.png`);
    }
    checks.push('Sept pages sans débordement horizontal à 390 et 320 px ; cinq étapes du formulaire et confirmations également vérifiées.');
    assert.deepEqual(errors, []);
    assert.deepEqual(external, []);
    checks.push('Aucune erreur JavaScript et aucune tentative de requête externe pendant les parcours testés.');
    await saveResult('passed');
  } catch (error) {
    await saveResult('failed', error instanceof Error ? error.message : String(error));
    throw error;
  } finally {
    if (browser) await browser.close();
    server.closeAllConnections();
    await new Promise(resolve => server.close(resolve));
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
