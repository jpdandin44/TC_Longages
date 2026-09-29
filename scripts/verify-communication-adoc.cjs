// Recette locale ADOC : aucun accès au service FFT, envoi ou presse-papiers réel.
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const http = require('node:http');
const { readFile, writeFile, mkdir } = require('node:fs/promises');
const path = require('node:path');

(async () => {
  const root = path.resolve(__dirname, '..');
  const output = path.join(root, 'docs/recette/communication-adoc');
  await mkdir(output, { recursive: true });
  const allowed = new Set(['index.html', 'communication.html', 'actualites-bureau.html']);
  const server = http.createServer(async (req, res) => {
    const filename = new URL(req.url, 'http://127.0.0.1').pathname.slice(1) || 'index.html';
    if (!allowed.has(filename)) { res.writeHead(404); res.end(); return; }
    try {
      const html = await readFile(path.join(root, 'demo-o2switch', filename));
      res.writeHead(200, { 'Cache-Control': 'no-store', 'Content-Type': 'text/html; charset=utf-8' }); res.end(html);
    } catch (_) { res.writeHead(500); res.end('Livrable local indisponible'); }
  });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const origin = 'http://127.0.0.1:' + server.address().port;
  const checks = [], errors = [], external = [], requests = [];
  let browser;
  try {
    browser = await chromium.launch({ channel: 'chrome', headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.route('**/*', route => {
      const url = route.request().url();
      if (url.startsWith(origin + '/') || url.startsWith('data:') || url.startsWith('blob:' + origin + '/')) return route.continue();
      external.push(url); return route.abort();
    });
    await context.addInitScript(() => {
      window.__adocEffects = { clipboard: 0, opened: 0 };
      Object.defineProperty(navigator, 'clipboard', { configurable: true, value: {
        writeText: async () => { window.__adocEffects.clipboard++; },
        write: async () => { window.__adocEffects.clipboard++; }
      } });
      window.open = () => { window.__adocEffects.opened++; return null; };
    });
    const page = await context.newPage();
    page.on('pageerror', error => errors.push(error.message));
    page.on('request', request => requests.push(request.url()));
    page.on('dialog', dialog => dialog.accept());
    const title = 'Article ADOC de recette';
    const saved = () => page.evaluate(value => TCLCommunication.read().find(post => post.title === value), title);
    const validate = async () => {
      assert.equal(await page.locator('#review-post').isDisabled(), false);
      await page.locator('#review-post').click();
      await page.locator('#confirm-approval').click();
      assert.equal((await saved()).status, 'validated');
    };
    const selectSaved = async () => page.getByRole('button', { name: new RegExp(title) }).click();
    const noOverflow = async label => assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false, label);

    await page.goto(origin + '/communication.html');
    assert.equal(await page.locator('#post-adoc-tenup-no').isChecked(), true);
    assert.equal(await page.locator('#post-adoc-tenup-yes').isChecked(), false);
    assert.equal(await page.locator('#prepare-adoc').isDisabled(), true);
    await page.locator('#preview-adoc').click();
    assert.equal(await page.locator('#adoc-preview').isVisible(), true);
    assert.match(await page.locator('#adoc-preview-tenup').innerText(), /^Non/);
    await page.locator('#use-example-poster').click();
    await page.waitForFunction(() => { const image = document.querySelector('#adoc-preview-image'); return image && !image.hidden && image.complete && image.naturalWidth > 0; });
    await page.locator('#post-title').fill(title);
    await page.locator('#post-body').fill('Retrouvez les informations de la rentrée sur l’affiche.');
    await page.locator('#save-post').click();
    assert.equal((await saved()).adocVisibleOnTenup, false);
    assert.equal(await page.locator('#prepare-adoc').isDisabled(), true);
    await validate();
    assert.equal(await page.locator('#prepare-adoc').isDisabled(), false);
    const sourceImage = (await saved()).image.dataUrl;
    const beforePreparation = await page.evaluate(() => localStorage.getItem(TCLCommunication.key));
    const requestsBefore = requests.length;
    await page.locator('#prepare-adoc').click();
    assert.match(await page.locator('#feedback').innerText(), /Simulation/);
    assert.match(await page.locator('#feedback').innerText(), /Aucun article créé/);
    assert.equal(await page.evaluate(() => localStorage.getItem(TCLCommunication.key)), beforePreparation);
    assert.equal(requests.length, requestsBefore, 'La préparation ADOC ne doit émettre aucune requête.');
    assert.deepEqual(await page.evaluate(() => window.__adocEffects), { clipboard: 0, opened: 0 });
    checks.push('Ten’Up est Non par défaut. Préparation ADOC bloquée avant validation, disponible ensuite, sans requête, presse-papiers, ouverture externe ni modification du stockage.');

    for (const width of [1440, 390, 320]) {
      await page.setViewportSize({ width, height: width === 1440 ? 1000 : 844 });
      for (const mode of ['site', 'facebook', 'whatsapp', 'adoc']) {
        await page.locator('#preview-' + mode).click();
        assert.equal(await page.locator('#preview-' + mode).getAttribute('aria-pressed'), 'true');
        const card = mode === 'adoc' ? '#adoc-preview' : '#preview-card';
        const image = mode === 'adoc' ? '#adoc-preview-image' : '#preview-image';
        assert.equal(await page.locator(card).isVisible(), true);
        assert.equal(await page.locator(image).isVisible(), true);
        assert.equal(await page.locator(image).getAttribute('src'), sourceImage);
        assert.equal(await page.locator(image).evaluate(element => getComputedStyle(element).objectFit), 'contain');
        await noOverflow(mode + ' à ' + width + ' pixels');
        await page.locator(card).screenshot({ path: path.join(output, mode + '-' + width + '.png') });
      }
    }
    checks.push('Quatre aperçus contrôlés à 1 440, 390 et 320 pixels : affiche conservée entière, onglet actif cohérent et absence de débordement horizontal.');

    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.locator('#post-adoc-tenup-yes').check();
    assert.equal(await page.locator('#prepare-adoc').isDisabled(), true);
    assert.equal(await page.locator('#review-post').isDisabled(), true);
    await page.locator('#save-post').click();
    assert.equal((await saved()).status, 'draft');
    assert.equal(await page.locator('#prepare-adoc').isDisabled(), true);
    await page.locator('#review-post').click();
    assert.match(await page.locator('#confirm-adoc').innerText(), /Ten’Up prévue : Oui/);
    await page.locator('#confirm-approval').click();
    await page.reload();
    await selectSaved();
    assert.equal(await page.locator('#post-adoc-tenup-yes').isChecked(), true);
    assert.equal(await page.locator('#prepare-adoc').isDisabled(), false);
    await page.locator('#prepare-adoc').click();
    assert.match(await page.locator('#feedback').innerText(), /Ten’Up prévue : Oui/);
    assert.equal((await saved()).adocVisibleOnTenup, true);
    checks.push('Le choix Ten’Up Oui invalide l’accord précédent, se conserve après sauvegarde et rechargement, puis exige une nouvelle validation individuelle.');

    await page.locator('#post-body').fill('a'.repeat(2000));
    await page.locator('#save-post').click();
    await validate();
    await page.locator('#preview-adoc').click();
    assert.equal(await page.locator('#adoc-preview-body').innerText(), 'a'.repeat(2000));
    assert.equal(await page.locator('#prepare-adoc').isDisabled(), false);
    await page.locator('#prepare-adoc').click();
    assert.match(await page.locator('#feedback').innerText(), /Simulation/);
    await page.locator('#post-body').fill('b'.repeat(2001));
    await page.locator('#save-post').click();
    await validate();
    assert.equal((await saved()).body.length, 2001);
    assert.equal(await page.locator('#adoc-preview-body').innerText(), 'b'.repeat(2001));
    assert.equal(await page.locator('#prepare-adoc').isDisabled(), true);
    assert.match(await page.locator('#adoc-help').innerText(), /dépasse 2 000/);
    assert.equal(await page.locator('#copy-whatsapp').isDisabled(), false);
    assert.equal(await page.evaluate(value => TCLCommunication.publicPosts().some(post => post.title === value), title), true);
    await page.locator('#adoc-preview').screenshot({ path: path.join(output, 'adoc-limite-2001.png') });
    checks.push('2 000 caractères autorisés ; 2 001 conservés sans troncature et bloqués pour ADOC uniquement. L’actualité reste validée pour les autres aperçus.');

    await page.locator('#post-body').fill('');
    await page.locator('#save-post').click();
    await validate();
    assert.equal(await page.locator('#prepare-adoc').isDisabled(), false);
    assert.equal(await page.locator('#adoc-preview-image').getAttribute('src'), sourceImage);
    assert.equal(await page.locator('#adoc-preview-body').innerText(), '');
    await page.locator('#prepare-adoc').click();
    assert.match(await page.locator('#feedback').innerText(), /et l’affiche/);
    assert.deepEqual(await page.evaluate(() => window.__adocEffects), { clipboard: 0, opened: 0 });
    checks.push('Actualité composée d’un titre et d’une affiche, sans message supplémentaire : préparation ADOC simulée possible après validation.');

    assert.deepEqual(errors, []); assert.deepEqual(external, []);
    const results = { date: new Date().toISOString(), status: 'passed', browser: browser.version(), checks, errors, external, externalEffects: await page.evaluate(() => window.__adocEffects) };
    await writeFile(path.join(output, 'results.json'), JSON.stringify(results, null, 2) + '\n');
    console.log(JSON.stringify(results, null, 2));
  } finally {
    if (browser) await browser.close();
    server.closeAllConnections(); await new Promise(resolve => server.close(resolve));
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
