const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const http = require('node:http');
const { readFile, writeFile, mkdir } = require('node:fs/promises');
const path = require('node:path');

(async () => {
  const root = path.resolve(__dirname, '..');
  const output = path.join(root, 'docs/recette/court-tarifs');
  await mkdir(output, {recursive:true});
  const html = await readFile(path.join(root,'demo-o2switch/index.html'));
  const server = http.createServer((req,res) => {res.setHeader('Content-Type','text/html; charset=utf-8');res.end(html);});
  await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
  const origin = `http://127.0.0.1:${server.address().port}`;
  const checks=[], errors=[], external=[], captures=[];
  let browser;
  try {
    browser=await chromium.launch({channel:'chrome',headless:true});
    const context=await browser.newContext();
    await context.route('**/*',route => {
      if(route.request().url().startsWith(origin) || route.request().url().startsWith('data:')) return route.continue();
      external.push(route.request().url()); return route.abort();
    });
    const page=await context.newPage();
    page.on('pageerror',error=>errors.push(error.message));
    for(const width of [1440,390,320]) {
      await page.setViewportSize({width,height:1000});
      await page.goto(origin);
      const court=page.locator('.hero-photo img');
      await court.evaluate(img=>img.decode());
      assert.deepEqual(await court.evaluate(img=>[img.naturalWidth,img.naturalHeight]),[2048,1536]);
      await page.screenshot({path:path.join(output,`court-${width}.png`)});
      captures.push(`court-${width}.png`);
      await page.locator('#faq-tarifs summary').click();
      const faq=page.locator('#faq-tarifs');
      assert.equal(await faq.locator('table').count(),2);
      assert.equal(await faq.locator('tbody tr').count(),9);
      assert.match(await faq.innerText(),/125,00 € ou 150 €/);
      assert.match(await faq.innerText(),/Conditions à confirmer/);
      assert.match(await faq.innerText(),/Horaires à confirmer/);
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth);
      assert.equal(overflow,false,`FAQ ouverte ${width}px`);
      await faq.screenshot({path:path.join(output,`tarifs-${width}.png`)});
      captures.push(`tarifs-${width}.png`);
      checks.push(`Photo réelle décodée et FAQ ouverte : deux tableaux, neuf tarifs, conditions et horaires explicites ; sans débordement à ${width}px.`);
    }
    assert.deepEqual(errors,[]); assert.deepEqual(external,[]);
    const result={date:new Date().toISOString(),status:'passed',scope:'Démo o2switch servie sur boucle locale uniquement ; photo et FAQ à 1440/390/320 px.',checks,captures,errors,external};
    await writeFile(path.join(output,'results.json'),JSON.stringify(result,null,2)+'\n');
    console.log(JSON.stringify(result,null,2));
  } finally {if(browser) await browser.close(); await new Promise(resolve=>server.close(resolve));}
})().catch(error=>{console.error(error);process.exitCode=1;});
