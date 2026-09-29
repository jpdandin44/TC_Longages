// Cible exclusivement un Apache local déjà ouvert, jamais un hébergement distant.
const assert = require('node:assert/strict');
const { readFile, writeFile, mkdir } = require('node:fs/promises');
const path = require('node:path');
const { chromium } = require('playwright');

(async () => {
  const origin = process.argv[2];
  assert.match(origin || '', /^http:\/\/127\.0\.0\.1:\d+$/);
  const root = path.resolve(__dirname, '..');
  const output = path.join(root,'docs/recette/v1-sous-domaine');
  await mkdir(output,{recursive:true});
  const manifest = JSON.parse(await readFile(path.join(root,'data/officiel-hosted-demo-manifest.json'),'utf8'));
  const pages = Object.keys(manifest.files).filter(name=>name.endsWith('.html'));
  const errors=[],externalRequests=[],writes=[],checks=[];
  const browser = await chromium.launch({channel:'chrome',headless:true});
  let status='failed',failure;
  try {
    const context=await browser.newContext();
    await context.route('**/*', route=>{
      const r=route.request();
      if (!['GET','HEAD'].includes(r.method())) {writes.push(r.method()+' '+r.url());return route.abort();}
      if (!r.url().startsWith(origin+'/') && !r.url().startsWith('data:')) {externalRequests.push(r.url());return route.abort();}
      return route.continue();
    });
    const page=await context.newPage();
    page.on('pageerror',e=>errors.push(e.message));
    page.on('console',e=>{if(e.type()==='error')errors.push(e.text());});
    for (const width of [1440,390,320]) {
      await page.setViewportSize({width,height:900});
      for (const name of pages) {
        const response=await page.goto(origin+'/'+name);
        assert.equal(response.status(),200);
        await page.locator('.club-logo-image').first().waitFor();
        for (const img of await page.locator('img').all()) {
          await img.scrollIntoViewIfNeeded();
          await img.evaluate(node => node.decode());
        }
        assert.equal(await page.locator('h1').count(),1);
        assert.match(await page.locator('.official-ribbon').innerText(),/DÉMONSTRATION V1/);
        const state=await page.evaluate(()=>({width:innerWidth,scroll:document.documentElement.scrollWidth,images:[...document.images].every(img=>img.complete&&img.naturalWidth>0)}));
        assert.ok(state.scroll<=width+1,name+' débordement '+width);assert.ok(state.images,name+' images');
        checks.push({page:name,width,status:'passed'});
      }
    }
    await page.setViewportSize({width:390,height:900});
    await page.goto(origin+'/');
    for (const img of await page.locator('img').all()) {
      await img.scrollIntoViewIfNeeded();
      await img.evaluate(node => node.decode());
    }
    await page.evaluate(()=>window.scrollTo({top:0,behavior:'instant'}));
    assert.equal(await page.locator('.quick-card').count(),6);
    await page.screenshot({path:path.join(output,'accueil-mobile.png'),fullPage:true});
    await page.goto(origin+'/contact.html');
    const mailto=await page.locator('a[href^="mailto:"]').evaluateAll(nodes=>nodes.map(n=>n.getAttribute('href').split('?')[0]));
    assert.ok(mailto.every(href=>href==='mailto:tclongages@gmail.com'));
    assert.equal(await page.locator('#test-support-email').innerText(),'support@tclongages.fr');
    assert.equal(await page.locator("a[href^='mailto:support@']").count(), 0);
    await page.locator('#contact-name').fill('Essai démonstration');
    await page.locator('#contact-reply').fill('test@example.invalid');
    await page.locator('#contact-message').fill('Message fictif, sans envoi.');
    await page.locator('#contact-prepare').click();
    assert.equal(await page.locator('#contact-preview').isVisible(),true);
    assert.match(await page.locator('#contact-feedback').innerText(),/Aucun message envoyé/);
    await page.evaluate(()=>window.scrollTo({top:0,behavior:'instant'}));
    await page.screenshot({path:path.join(output,'contact-mobile.png'),fullPage:true});
    assert.deepEqual(errors,[]);assert.deepEqual(externalRequests,[]);assert.deepEqual(writes,[]);
    status='passed';
  } catch (error) {failure=error.message;throw error;}
  finally {
    await browser.close();
    await writeFile(path.join(output,'browser-results.json'),JSON.stringify({date:new Date().toISOString(),status,origin,checks,errors,externalRequests,writes,...(failure?{failure}:{})},null,2)+'\n');
  }
  console.log('Navigateur sous Apache : 7 pages × 3 largeurs, images, contact et support vérifiés, aucun envoi.');
})().catch(error=>{console.error(error);process.exitCode=1;});
