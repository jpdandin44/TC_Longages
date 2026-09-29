// Recette navigateur locale des véritables HTML générés. Aucun build ni envoi.
// Le serveur Node n'interprète pas .htaccess ; sa recette relève d'Apache.
const assert = require('node:assert/strict');
const { createHash } = require('node:crypto');
const { once } = require('node:events');
const { mkdir, readFile, writeFile } = require('node:fs/promises');
const path = require('node:path');
const { pathToFileURL } = require('node:url');
const { chromium } = require('playwright');

(async () => {
  const root = path.resolve(__dirname, '..');
  const output = path.join(root, 'docs/recette/officiel');
  const captureDirectory = path.join(output, 'captures');
  await mkdir(captureDirectory, { recursive: true });
  const { createOfficialServer } = await import(pathToFileURL(path.join(__dirname, 'official-server.mjs')).href);
  const { officialPages, loadOfficialConfig } = await import(pathToFileURL(path.join(__dirname, 'official-config.mjs')).href);
  const config = await loadOfficialConfig();
  const tariffs = JSON.parse(await readFile(path.join(root, 'data/tarifs-inscription.json'), 'utf8'));
  const hash = bytes => createHash('sha256').update(bytes).digest('hex');
  const files = {};
  for (const name of officialPages) files[name] = hash(await readFile(path.join(root, 'officiel', name)));
  const logo = `data:image/jpeg;base64,${(await readFile(path.join(root, 'Images_Photos/Logo.jpeg'))).toString('base64')}`;
  const court = `data:image/jpeg;base64,${(await readFile(path.join(root, 'Images_Photos/Image_terrain.jpg'))).toString('base64')}`;
  const secondary = `data:image/webp;base64,${(await readFile(path.join(root, 'src/assets/tennis-life.webp'))).toString('base64')}`;
  const checks = [], layouts = [], captures = [], errors = [], consoleErrors = [], externalRequests = [], writes = [], sideEffects = [];
  const server = createOfficialServer({ directory: path.join(root, 'officiel') });
  server.listen(0, '127.0.0.1');
  await once(server, 'listening');
  const origin = `http://127.0.0.1:${server.address().port}`;
  let browser;
  const saveResult = async (status, failure) => {
    const result = {
      date: new Date().toISOString(), status,
      scope: 'Sept HTML officiel/ servis sur 127.0.0.1 à port éphémère par createOfficialServer. Aucune publication. .htaccess non interprété dans cette recette navigateur.',
      files, checks, layouts, captures, errors, consoleErrors, externalRequests, writes, sideEffects,
      ...(failure ? { failure } : {})
    };
    await writeFile(path.join(output, 'results.json'), JSON.stringify(result, null, 2) + '\n');
    console.log(JSON.stringify(result, null, 2));
  };
  try {
    browser = await chromium.launch({ channel: process.env.TCL_BROWSER_CHANNEL || 'chrome', headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.exposeBinding('__officialAudit', (_source, event) => sideEffects.push(event));
    await context.addInitScript(() => {
      for (const method of ['getItem', 'setItem', 'removeItem', 'clear', 'key']) {
        const original = Storage.prototype[method];
        Storage.prototype[method] = function (...args) {
          void window.__officialAudit({ type: 'storage', method });
          return original.apply(this, args);
        };
      }
      window.open = (...args) => { void window.__officialAudit({ type: 'window.open', url: String(args[0]) }); return null; };
      document.addEventListener('click', event => {
        const anchor = event.target.closest?.('a[href^="mailto:"]');
        if (anchor) { event.preventDefault(); void window.__officialAudit({ type: 'mailto', url: anchor.href }); }
      }, true);
      window.__officialInjected = false;
    });
    await context.route('**/*', route => {
      const request = route.request(), url = request.url();
      if (!['GET', 'HEAD'].includes(request.method())) { writes.push({ method: request.method(), url }); return route.abort(); }
      if (url.startsWith(origin + '/') || url.startsWith('data:')) return route.continue();
      externalRequests.push(url); return route.abort();
    });
    const page = await context.newPage();
    page.on('pageerror', error => errors.push(error.message));
    page.on('console', message => { if (message.type() === 'error') consoleErrors.push(message.text()); });
    async function visit(name) {
      const response = await page.goto(`${origin}/${name}`, { waitUntil: 'load' });
      assert.equal(response.status(), 200, name);
      // Déclencher le chargement différé par un vrai défilement de visite.
      if (await page.locator('.life-photo').count()) await page.locator('.life-photo').scrollIntoViewIfNeeded();
      await page.waitForFunction(() => [...document.images].every(image => image.complete && image.naturalWidth > 0));
      await page.evaluate(() => window.scrollTo({ top: 0, left: 0, behavior: 'instant' }));
      assert.equal(await page.locator('h1').count(), 1, `${name} : titre principal unique`);
      assert.equal(await page.locator('.official-ribbon').count(), 1);
      assert.equal(await page.locator('meta[name="robots"]').getAttribute('content'), 'noindex,nofollow');
      const integrations = await page.locator('a[href]').evaluateAll(links => links.map(a => a.href).filter(href => /^https:\/\/(?:calendar\.google\.com|forms\.gle|docs\.google\.com\/forms)(?:\/|$)/.test(href)));
      assert.deepEqual(integrations, [], `${name} : aucun calendrier ou formulaire Google inventé`);
      assert.equal(await page.locator('.club-logo-image').count(), 2);
      assert.equal(await page.locator('.club-logo-image').evaluateAll((images, expected) => images.every(image => image.src === expected && image.naturalWidth === 1131 && image.naturalHeight === 1600), logo), true);
    }
    async function capture(name) {
      // La feuille commune active le défilement doux. Une capture pleine page
      // pendant son animation déplacerait visuellement le header sticky.
      await page.evaluate(() => { if (document.activeElement instanceof HTMLElement) document.activeElement.blur(); window.scrollTo({ top: 0, left: 0, behavior: 'instant' }); });
      await page.waitForFunction(() => window.scrollY === 0);
      await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
      await page.screenshot({ path: path.join(captureDirectory, name), fullPage: true });
      captures.push('captures/' + name);
    }
    async function layout(name, width) {
      const observed = await page.evaluate(() => ({
        width: innerWidth,
        pageWidth: document.documentElement.scrollWidth,
        menuToggle: getComputedStyle(document.querySelector('.menu-toggle')).display,
        quickColumns: document.querySelector('.quick-grid') ? getComputedStyle(document.querySelector('.quick-grid')).gridTemplateColumns.split(' ').length : null,
        outside: [...document.querySelectorAll('main *, .header-inner > *, .official-ribbon .container')].filter(el => {
          if (!el.getClientRects().length) return false;
          const box = el.getBoundingClientRect(); return box.width && (box.left < -1 || box.right > innerWidth + 1);
        }).map(el => el.id || el.className || el.tagName)
      }));
      assert.ok(observed.pageWidth <= width + 1, `${name} déborde à ${width}px`);
      assert.deepEqual(observed.outside, [], `${name} éléments hors largeur à ${width}px`);
      assert.equal(observed.menuToggle, width <= 1100 ? 'flex' : 'none');
      if (name === 'index.html') assert.equal(observed.quickColumns, width <= 640 ? 1 : width <= 1060 ? 2 : 3);
      layouts.push({ page: name, ...observed });
    }

    await visit('index.html');
    assert.equal(await page.locator('.hero-photo img').getAttribute('src'), court);
    assert.equal(await page.locator('.life-photo img').getAttribute('src'), secondary);
    assert.match(await page.locator('.hero-figure figcaption').innerText(), /photo fournie par le club/);
    assert.equal(await page.locator('.quick-card').count(), 6);
    const actualDestinations = await page.locator('.quick-card').evaluateAll(nodes => nodes.map(node => node.getAttribute('href')));
    assert.deepEqual(actualDestinations, config.quickAccess.map(item => item.href));
    for (const [index, item] of config.quickAccess.entries()) {
      await visit('index.html');
      await page.locator('.quick-card').nth(index).click();
      const wanted = new URL(item.href, origin + '/index.html');
      assert.equal(page.url(), wanted.href);
      if (wanted.hash) assert.equal(await page.locator(wanted.hash).count(), 1);
    }
    checks.push('Six raccourcis de l’accueil vérifiés par un clic chacun vers les destinations configurées ; ancre club existante.');

    await visit('index.html');
    await page.locator('#faq-tarifs summary').click();
    const tariffText = await page.locator('#faq-tarifs').innerText();
    assert.equal(await page.locator('.tariff-table tbody tr').count(), tariffs.groups.reduce((n, group) => n + group.rows.length, 0));
    const normalize = value => value.replace(/\s+/g, ' ').trim();
    for (const group of tariffs.groups) for (const row of group.rows) {
      assert.ok(normalize(tariffText).includes(normalize(row.label)), `Libellé tarif ${row.id}`);
      assert.ok(normalize(tariffText).includes(normalize(row.priceLabel)), `Prix tarif ${row.id}`);
    }
    assert.ok(normalize(tariffText).includes(tariffs.season));
    await capture('tarifs-1440.png');
    checks.push('Logo original exact chargé sur les sept pages ; photo du court et illustration secondaire exactes ; neuf tarifs et saison identiques au JSON canonique.');

    for (const width of [1440, 390, 320]) {
      await page.setViewportSize({ width, height: width === 1440 ? 1000 : 844 });
      for (const name of officialPages) {
        await visit(name);
        await layout(name, width);
        await capture(name.replace('.html', '') + '-' + width + '.png');
      }
    }
    checks.push('Sept pages contrôlées à 1440, 390 et 320 px : aucun débordement horizontal, un titre principal, images chargées et bandeau de préparation.');

    await page.setViewportSize({ width: 1060, height: 900 });
    await visit('index.html');
    await layout('index.html', 1060);
    const menu = page.locator('.menu-toggle'), nav = page.locator('#main-nav');
    assert.equal(await nav.locator('a').count(), 6);
    assert.equal(await nav.isVisible(), false);
    await menu.click();
    assert.equal(await menu.getAttribute('aria-expanded'), 'true');
    assert.equal(await nav.isVisible(), true);
    assert.equal(await nav.evaluate(el => { const box = el.getBoundingClientRect(); return box.left >= 0 && box.right <= innerWidth && el.scrollWidth <= el.clientWidth; }), true);
    await capture('navigation-1060.png');
    await page.keyboard.press('Escape');
    assert.equal(await menu.getAttribute('aria-expanded'), 'false');
    assert.equal(await menu.evaluate(el => el === document.activeElement), true);
    await menu.click();
    await page.setViewportSize({ width: 1101, height: 900 });
    await page.waitForFunction(() => document.querySelector('.menu-toggle').getAttribute('aria-expanded') === 'false');
    assert.equal(await menu.isVisible(), false);
    assert.equal(await nav.isVisible(), true);
    await layout('index.html', 1101);
    await page.setViewportSize({ width: 1060, height: 900 });
    assert.equal(await nav.isVisible(), false);
    await menu.click();
    await nav.getByRole('link', { name: 'Calendrier', exact: true }).click();
    assert.equal(new URL(page.url()).pathname, '/calendrier.html');
    assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'), 'false');
    checks.push('Navigation à 1060 px : six liens, ouverture, Échap et focus, clic de destination ; passage à 1101 px ferme le menu mobile puis retour replié.');

    await page.setViewportSize({ width: 390, height: 844 });
    await visit('contact.html');
    const name = page.locator('#contact-name'), reply = page.locator('#contact-reply'), message = page.locator('#contact-message');
    const prepare = page.locator('#contact-prepare'), preview = page.locator('#contact-preview'), feedback = page.locator('#contact-feedback');
    await prepare.click();
    assert.equal(await name.evaluate(el => el.checkValidity()), false);
    assert.equal(await preview.isVisible(), false);
    await name.fill('   '); await reply.fill('invalide'); await message.fill('   '); await prepare.click();
    assert.equal(await name.evaluate(el => el.checkValidity()), false);
    assert.equal(await message.evaluate(el => el.checkValidity()), false);
    await name.fill('Camille Démonstration'); await message.fill('Message fictif pour vérifier le formulaire.');
    for (const invalid of ['12345', '+1234567890123456', 'personne@exemple', 'bonjour', '06 12 lettres']) {
      await reply.fill(invalid); await prepare.click();
      assert.equal(await reply.evaluate(el => el.checkValidity()), false, invalid);
      assert.equal(await preview.isVisible(), false);
    }
    await reply.fill('+33 6 12 34 56 78'); await prepare.click();
    assert.equal(await preview.isVisible(), true);
    assert.match(await preview.innerText(), /\+33 6 12 34 56 78/);
    assert.match(await feedback.innerText(), /Aucun message envoyé/);
    assert.equal(await feedback.evaluate(el => el === document.activeElement), true);
    await message.fill('Message modifié après l’aperçu.');
    assert.equal(await preview.isVisible(), false);
    assert.equal(await feedback.isVisible(), false);
    const inertName = '<img src=x onerror="window.__officialInjected=true"> & Élodie';
    const inertMessage = '<script>window.__officialInjected=true</script>\n<a href="https://example.invalid">Texte seulement</a> & accents : été.';
    await name.fill(inertName); await reply.fill('essai@example.invalid'); await message.fill(inertMessage); await prepare.click();
    assert.equal(await page.locator('#contact-preview-text').textContent(), `Nom : ${inertName}\nRéponse à : essai@example.invalid\n\n${inertMessage}`);
    assert.equal(await preview.locator('script,img,a').count(), 0);
    assert.equal(await page.evaluate(() => window.__officialInjected), false);
    await layout('contact.html', 390);
    await capture('contact-apercu-390.png');
    await page.setViewportSize({ width: 320, height: 844 });
    await layout('contact.html', 320);
    await capture('contact-apercu-320.png');
    await page.getByRole('button', { name: 'Effacer', exact: true }).click();
    assert.deepEqual(await Promise.all([name.inputValue(), reply.inputValue(), message.inputValue()]), ['', '', '']);
    assert.equal(await preview.isVisible(), false);
    assert.equal(await feedback.isVisible(), false);
    await name.fill('Camille Démonstration'); await reply.fill('essai@example.invalid'); await message.fill('Nouveau message fictif.');
    await prepare.click();
    assert.equal(await preview.isVisible(), true);
    await page.reload();
    assert.deepEqual(await Promise.all([name.inputValue(), reply.inputValue(), message.inputValue()]), ['', '', '']);
    checks.push('Contact : champs vides/blancs, e-mail et téléphones invalides refusés ; téléphone et e-mail valides acceptés ; HTML affiché comme texte inerte ; modification masque l’aperçu ; effacement et rechargement vident les champs.');
    assert.deepEqual(errors, []);
    assert.deepEqual(consoleErrors, []);
    assert.deepEqual(externalRequests, []);
    assert.deepEqual(writes, []);
    assert.deepEqual(sideEffects, []);
    for (const file of officialPages) assert.equal(hash(await readFile(path.join(root, 'officiel', file))), files[file], 'Le build a changé pendant la recette : ' + file);
    checks.push('Aucune erreur JavaScript/console, tentative de requête externe, requête d’écriture, ouverture de fenêtre, transmission mailto ou utilisation de Storage pendant cette recette. Aucun lien Google Calendar/Forms inventé.');
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
