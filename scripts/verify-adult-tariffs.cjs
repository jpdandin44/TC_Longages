const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const { writeFile } = require('node:fs/promises');
const { resolve } = require('node:path');
const { pathToFileURL } = require('node:url');

(async () => {
  const output = resolve(__dirname, '../.local/tarifs-adultes');
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const results = [];
  try {
    for (const width of [1440, 390]) {
      const page = await browser.newPage({ viewport: { width, height: 1000 } });
      await page.route(/^https?:/, route => route.abort());
      await page.goto(pathToFileURL(resolve(output, 'apercu-tarifs.html')).href);
      await page.locator('#faq-tarifs').evaluate(node => { node.open = true; });
      assert.equal(await page.locator('.tariff-table tbody tr').count(), 10);
      for (const [label, price] of [['Cours Adultes', '150,00 €'], ['Cours adultes compétition', '170,00 €'], ['Adhésion Adulte compétition', '100,00 €']]) {
        const row = page.getByRole('row').filter({ has: page.getByRole('rowheader', { name: label, exact: true }) });
        assert.equal(await row.getByRole('cell').innerText(), price);
      }
      assert.doesNotMatch(await page.locator('#faq-tarifs').innerText(), /125,00 € ou 150 €|Cours adultes : la fiche indique/);
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
      const capture = `tarifs-${width}.png`;
      await page.locator('.tariff-table').nth(1).screenshot({ path: resolve(output, capture), style: 'header, .skip-link { visibility: hidden !important; }' });
      results.push({ width, rows: 10, overflow: false, capture });
      await page.close();
    }
    await writeFile(resolve(output, 'recette.json'), JSON.stringify({ scope: 'Local frozen-beta preview only; no hosted mutation', results }, null, 2) + '\n');
    console.log(JSON.stringify(results));
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
