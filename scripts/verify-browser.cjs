// Recette optionnelle : Playwright fourni par l'environnement (aucune dépendance de production).
const { chromium } = require('playwright');
const { mkdir, writeFile, readFile } = require('node:fs/promises');
const path = require('node:path');
const assert = require('node:assert/strict');

(async () => {
  const { startBrowserServer } = await import('../tests/browser-server.mjs');
  const local = await startBrowserServer();
  const origin = local.origin;
  const browser = await chromium.launch({ channel: process.env.TCL_BROWSER_CHANNEL || 'chrome', headless: true });
  const context = await browser.newContext({ httpCredentials: local.httpCredentials, viewport: { width: 1440, height: 1000 } });
  const page = await context.newPage();
  const errors = [];
  const external = [];
  page.on('pageerror', error => errors.push(error.message));
  context.on('request', request => { if (!request.url().startsWith(origin + '/' )) external.push(request.url()); });
  const output = path.resolve(__dirname, '../docs/recette');
  await mkdir(output, { recursive: true });
  try {
    await page.goto(origin + '/' );
    assert.equal(await page.evaluate(() => typeof window.TCLCommunication), 'undefined');
    await page.locator('.life-photo').scrollIntoViewIfNeeded();
    await page.waitForFunction(() => [...document.images].every(img => img.complete && img.naturalWidth > 0));
    assert.equal(await page.locator('a[href^="mailto:tclongages@gmail.com"]').count(), 7);
    assert.equal(await page.locator('img').count(), 2);
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({ path: path.join(output, 'vitrine-bureau.png') });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.locator('.menu-toggle').click();
    assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'), 'true');
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'), 'false');
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
    await page.screenshot({ path: path.join(output, 'vitrine-mobile.png') });
    await page.setViewportSize({ width: 1440, height: 1000 });
    for (const name of ['bureau', 'inscriptions']) {
      await page.goto(origin + '/' + name + '.html');
      if (name === 'inscriptions') {
        assert.equal(await page.locator('form,input,textarea,select').count(), 0);
        assert.match(await page.locator('.status-label').textContent(), /CONTENU À VENIR/);
      }
      await page.screenshot({ path: path.join(output, name + '-bureau.png'), fullPage: true });
      await page.setViewportSize({ width: 390, height: 844 });
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
      await page.screenshot({ path: path.join(output, name + '-mobile.png'), fullPage: true });
      await page.setViewportSize({ width: 320, height: 700 });
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
      await page.setViewportSize({ width: 1440, height: 1000 });
    }
    await page.goto(origin + '/communication.html');
    await page.locator('#post-title').fill('Actualité de recette — sans publication');
    await page.locator('#post-body').fill('Ce contenu sert uniquement au contrôle du prototype.\n<img src=x onerror=alert(1)>');
    await page.locator('#post-link').fill('https://tenup.fft.fr/club/60310230');
    await page.locator('#save-post').click();
    await page.waitForFunction(() => window.TCLCommunication.read().length === 1);
    assert.equal(await page.evaluate(() => window.TCLCommunication.publicPosts().length), 0);
    assert.equal(await page.locator('#preview-body img').count(), 0);
    await page.locator('#review-post').click();
    await page.locator('#cancel-approval').click();
    assert.equal(await page.evaluate(() => window.TCLCommunication.publicPosts().length), 0);
    await page.locator('#review-post').click();
    await page.locator('#confirm-approval').click();
    await page.waitForFunction(() => window.TCLCommunication.publicPosts().length === 1);
    assert.equal(await page.evaluate(() => window.TCLCommunication.read()[0].facebookStatus), 'simulated');
    const vitrine = await context.newPage();
    await vitrine.goto(origin + '/actualites-bureau.html');
    assert.equal(await vitrine.locator('.news-card h3').textContent(), 'Actualité de recette — sans publication');
    assert.equal(await vitrine.locator('.news-card img').count(), 0);
    await page.locator('#post-title').fill('Révision encore à valider');
    await page.locator('#save-post').click();
    await vitrine.waitForFunction(() => document.querySelectorAll('.news-card').length === 0);
    assert.equal(await page.evaluate(() => window.TCLCommunication.publicPosts().length), 0);
    await page.locator('.backup summary').click();
    const downloaded = page.waitForEvent('download');
    await page.locator('#export-posts').click();
    const download = await downloaded;
    assert.match(download.suggestedFilename(), /\.json$/);
    const exported = await readFile(await download.path());
    assert.equal(JSON.parse(exported.toString('utf8')).posts.length, 1);
    await page.evaluate(() => localStorage.clear());
    await page.reload();
    await page.locator('.backup summary').click();
    await page.locator('#import-posts').setInputFiles({ name: 'recette.json', mimeType: 'application/json', buffer: exported });
    await page.waitForFunction(() => window.TCLCommunication.read().length === 1);
    assert.equal(await page.evaluate(() => window.TCLCommunication.publicPosts().length), 0);
    // The browser context is disposable. Screenshots show the clean initial screen.
    await page.evaluate(() => localStorage.clear());
    await page.reload();
    await page.evaluate(() => { document.activeElement.blur(); window.scrollTo({ top: 0, behavior: 'instant' }); });
    await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
    await page.screenshot({ path: path.join(output, 'communication-bureau.png'), fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
    await page.screenshot({ path: path.join(output, 'communication-mobile.png'), fullPage: true });
    await page.setViewportSize({ width: 320, height: 700 });
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
    assert.deepEqual(errors, []);
    assert.deepEqual(external.filter(url => !url.startsWith('data:') && !url.startsWith('blob:')), []);
    const result = { date: new Date().toISOString(), browser: 'Chromium / ' + (process.env.TCL_BROWSER_CHANNEL || 'chrome'), access: 'Compte bureau éphémère en mémoire sur serveur de recette isolé', checks: ['Vitrine sans lecteur de brouillons', 'Photos décodées', 'Sept contacts FFT', 'Menu mobile et Échap', 'Espace bureau et inscriptions sans formulaire', 'Pas de débordement mobile 390 et 320 px', 'Brouillon invisible dans aperçu', 'Annulation de validation', 'Validation simulée', 'Aperçu réservé au bureau', 'Révision retirée en temps réel', 'Texte utilisateur non interprété en HTML', 'Export et réimport JSON', 'Aucun appel externe', 'Aucune erreur JavaScript'], status: 'passed' };
    await writeFile(path.join(output, 'browser-results.json'), JSON.stringify(result, null, 2) + '\n');
    console.log(JSON.stringify(result, null, 2));
  } finally { await browser.close(); await local.stop(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
