// Recette locale des visuels : aucun appel ou envoi vers un service externe.
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const http = require('node:http');
const { readFile, writeFile, mkdir } = require('node:fs/promises');
const path = require('node:path');

(async () => {
  const root = path.resolve(__dirname, '..');
  const output = path.join(root, 'docs/recette/communication-images');
  await mkdir(output, { recursive: true });
  const names = new Set(['index', 'parcours', 'adherer', 'bureau', 'inscriptions', 'communication', 'actualites-bureau'].map(x => x + '.html'));
  const server = http.createServer(async (req, res) => {
    const filename = new URL(req.url, 'http://127.0.0.1').pathname.slice(1) || 'index.html';
    if (!names.has(filename)) { res.writeHead(404); res.end(); return; }
    res.setHeader('Cache-Control', 'no-store');
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    res.end(await readFile(path.join(root, 'demo-o2switch', filename)));
  });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const origin = 'http://127.0.0.1:' + server.address().port;
  const checks = [], errors = [], external = [];
  let browser;
  try {
    browser = await chromium.launch({ channel: 'chrome', headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.route('**/*', route => {
      const url = route.request().url();
      if (url.startsWith(origin + '/') || url.startsWith('data:') || url.startsWith('blob:' + origin + '/')) return route.continue();
      external.push(url); return route.abort();
    });
    const page = await context.newPage();
    page.on('pageerror', error => errors.push(error.message));
    page.on('dialog', dialog => dialog.accept());
    const selectors = { file: '#post-image', alt: '#post-image-alt', remove: '#remove-post-image', preview: '#preview-image', confirmation: '#confirm-image' };
    const screenshot = async name => {
      await page.evaluate(() => window.scrollTo(0, 0));
      await page.screenshot({ path: path.join(output, name), fullPage: true });
    };
    const saved = () => page.evaluate(() => TCLCommunication.read().find(p => p.title === 'Affiche de recette'));

    await page.goto(origin + '/communication.html');
    await page.locator('#use-example-poster').click();
    await page.waitForFunction(selector => { const image = document.querySelector(selector); return image && !image.hidden && image.complete && image.naturalWidth > 0; }, selectors.preview);
    await page.locator('#post-title').fill('Affiche de recette');
    await page.locator('#post-body').fill('');
    await page.locator('#save-post').click();
    await page.waitForFunction(() => TCLCommunication.read().some(p => p.title === 'Affiche de recette' && p.image));
    const original = await saved();
    assert.equal(original.body, '');
    assert.equal(original.status, 'draft');
    assert.match(original.image.dataUrl, /^data:image\/jpeg;base64,/);
    assert.ok(original.image.alt.length > 0);
    await screenshot('affiche-edition-1440.png');
    for (const mode of ['facebook', 'whatsapp', 'site']) {
      await page.locator('#preview-' + mode).click();
      assert.equal(await page.locator(selectors.preview).isVisible(), true);
    }
    await page.locator('#review-post').click();
    assert.equal(await page.locator(selectors.confirmation).getAttribute('src'), original.image.dataUrl);
    await page.locator('#cancel-approval').click();
    assert.equal((await saved()).status, 'draft');
    await page.locator('#review-post').click();
    await page.locator('#confirm-approval').click();
    assert.equal((await saved()).status, 'validated');
    await page.goto(origin + '/actualites-bureau.html');
    assert.equal(await page.locator('#news-list img').count(), 1);
    assert.equal(await page.locator('#news-list img').getAttribute('src'), original.image.dataUrl);
    await page.getByRole('button', { name: 'Agrandir l’affiche' }).click();
    assert.equal(await page.locator('dialog[open] img').getAttribute('src'), original.image.dataUrl);
    await page.keyboard.press('Escape');
    await screenshot('affiche-validee-1440.png');
    checks.push('Affiche exemple sans texte obligatoire : édition, trois aperçus, annulation de validation, validation puis aperçu bureau avec image entière.');

    await page.goto(origin + '/communication.html');
    await page.getByRole('button', { name: /Affiche de recette/ }).click();
    assert.equal(await page.locator(selectors.preview).getAttribute('src'), original.image.dataUrl);
    await page.locator(selectors.alt).fill('Affiche de test, description modifiée après validation.');
    assert.equal(await page.locator('#review-post').isDisabled(), true);
    assert.equal(await page.locator('#share-whatsapp').isDisabled(), true);
    await page.locator('#save-post').click();
    assert.equal((await saved()).status, 'draft');
    assert.equal(await page.evaluate(() => TCLCommunication.publicPosts().some(p => p.title === 'Affiche de recette')), false);
    checks.push('Image conservée après rechargement ; modification de sa description remet le contenu en brouillon et demande une nouvelle validation.');

    const beforeFailure = await saved();
    await page.evaluate(() => {
      const originalModule = window.TCLCommunicationImages;
      window.imageImportTrace = [];
      window.TCLCommunicationImages = { ...originalModule, prepare: async file => {
        const trace = { name: file.name, type: file.type, size: file.size };
        window.imageImportTrace.push(trace);
        try { const image = await originalModule.prepare(file); trace.result = 'prepared'; return image; }
        catch (error) { trace.result = error.name + ': ' + error.message; throw error; }
      } };
    });
    const chooseImage = async file => {
      const index = await page.evaluate(() => window.imageImportTrace.length);
      await page.locator(selectors.file).setInputFiles(file);
      // Observe this upload's completion, never an error left by the preceding file.
      await page.waitForFunction(index => Boolean(window.imageImportTrace[index]?.result), index);
      await page.waitForFunction(() => !document.querySelector('#image-status').textContent.includes('Préparation'));
      return page.evaluate(index => ({ ...window.imageImportTrace[index], feedback: document.querySelector('#feedback').textContent, failed: document.querySelector('#feedback').classList.contains('error') }), index);
    };
    const falseJpeg = await chooseImage({ name: 'faux.jpg', mimeType: 'image/jpeg', buffer: Buffer.from('ceci n’est pas une image') });
    assert.notEqual(falseJpeg.result, 'prepared'); assert.equal(falseJpeg.failed, true);
    assert.equal(await page.locator(selectors.preview).getAttribute('src'), beforeFailure.image.dataUrl);
    const svg = await chooseImage({ name: 'interdit.svg', mimeType: 'image/svg+xml', buffer: Buffer.from('<svg xmlns="http://www.w3.org/2000/svg"></svg>') });
    assert.notEqual(svg.result, 'prepared'); assert.equal(svg.failed, true);
    assert.equal(await page.locator(selectors.preview).getAttribute('src'), beforeFailure.image.dataUrl);
    const poster = await chooseImage(path.join(root, 'Affiche.jpeg'));
    assert.equal(poster.result, 'prepared', JSON.stringify(poster));
    assert.equal(poster.failed, false, poster.feedback);
    assert.match(poster.feedback, /Affiche ajoutée/);
    await page.locator(selectors.alt).fill('Affiche choisie depuis un fichier local.');
    await page.locator('#save-post').click();
    checks.push('Choix du fichier Affiche.jpeg réussi ; faux JPEG et SVG refusés sans perdre l’image précédente.');

    const beforeQuota = await page.evaluate(() => localStorage.getItem(TCLCommunication.key));
    await page.evaluate(() => {
      window.restoreStorageWrite = Storage.prototype.setItem;
      Storage.prototype.setItem = function (key, value) { if (key === TCLCommunication.key) throw new DOMException('Test quota', 'QuotaExceededError'); return window.restoreStorageWrite.call(this, key, value); };
    });
    await page.locator('#post-body').fill('Texte non sauvegardé car quota simulé.');
    await page.locator('#save-post').click();
    assert.match(await page.locator('#feedback').innerText(), /espace insuffisant|stockage/i);
    assert.equal(await page.evaluate(() => localStorage.getItem(TCLCommunication.key)), beforeQuota);
    assert.equal(await page.locator('#post-body').inputValue(), 'Texte non sauvegardé car quota simulé.');
    await page.evaluate(() => { Storage.prototype.setItem = window.restoreStorageWrite; });
    await page.locator('#save-post').click();
    checks.push('Quota simulé : dernière sauvegarde intacte et saisie conservée ; sauvegarde possible après rétablissement.');

    const formats = await page.evaluate(async () => {
      const result = [];
      const canvas = document.createElement('canvas'); canvas.width = 100; canvas.height = 200;
      const ctx = canvas.getContext('2d'); ctx.fillStyle = '#d6e798'; ctx.fillRect(0, 0, 100, 200);
      for (const type of ['image/png', 'image/webp']) {
        const blob = await new Promise(resolve => canvas.toBlob(resolve, type));
        const image = await TCLCommunicationImages.prepare(new File([blob], 'fixture.' + type.split('/')[1], { type }));
        result.push(image.dataUrl.startsWith('data:image/jpeg;base64,'));
      }
      return result;
    });
    assert.deepEqual(formats, [true, true]);
    await page.evaluate(() => {
      window.imageModuleOriginal = TCLCommunicationImages;
      window.TCLCommunicationImages = { ...TCLCommunicationImages, prepare: () => new Promise(resolve => { window.completeImageTest = resolve; }) };
      window.pendingImageTest = TCLCommunicationEditor.attachImage(new File(['fixture'], 'delayed.jpeg'));
    });
    await page.locator('#new-post').click();
    const pendingResult = await page.evaluate(async image => {
      window.completeImageTest(image);
      const applied = await window.pendingImageTest;
      window.TCLCommunicationImages = window.imageModuleOriginal;
      return applied;
    }, original.image);
    assert.equal(pendingResult, false);
    assert.equal(await page.locator(selectors.preview).isVisible(), false);
    assert.equal(await page.locator('#post-title').inputValue(), '');
    checks.push('PNG et WebP décodés ; résultat d’un ajout encore en cours ignoré après changement d’actualité.');

    for (const width of [390, 320]) {
      await page.setViewportSize({ width, height: 844 });
      for (const name of ['index', 'communication', 'actualites-bureau']) {
        await page.goto(origin + '/' + name + '.html');
        if (name === 'communication') await page.getByRole('button', { name: /Affiche de recette/ }).click();
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false, name + ' à ' + width);
        if (name !== 'actualites-bureau') await screenshot(name + '-' + width + '.png');
      }
    }
    checks.push('Logo et page de communication avec affiche vérifiés à 1440, 390 et 320 pixels, sans débordement horizontal.');
    await page.goto(origin + '/communication.html');
    await page.getByRole('button', { name: /Affiche de recette/ }).click();
    await page.locator(selectors.remove).click();
    await page.locator('#save-post').click();
    assert.equal((await saved()).image ?? null, null);
    checks.push('Retrait volontaire de l’image enregistré.');
    assert.deepEqual(errors, []); assert.deepEqual(external, []);
    await writeFile(path.join(output, 'results.json'), JSON.stringify({ date: new Date().toISOString(), status: 'passed', checks, errors, external }, null, 2) + '\n');
    console.log(JSON.stringify({ status: 'passed', checks, errors, external }, null, 2));
  } finally {
    if (browser) await browser.close();
    server.closeAllConnections(); await new Promise(resolve => server.close(resolve));
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
