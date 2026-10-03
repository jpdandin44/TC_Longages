import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {publicPage,pages} from '../scripts/build-drupal-public-pages.mjs';

test('Les sept pages Drupal publiques dérivent de la V1 sans bandeau ni noindex de revue',async()=>{
  for(const page of pages){
    const source=await readFile(new URL('../officiel/'+page+'.html',import.meta.url),'utf8');
    const output=publicPage(source);
    assert.match(output,/<main/);
    assert.doesNotMatch(output,/SITE EN PRÉPARATION|<meta name="robots" content="noindex/);
    assert.ok(output.includes('tclongages@gmail.com'));
  }
});

test('Une source sans les gardes attendues est refusée',()=>{
  assert.throws(()=>publicPage('<html>inconnu</html>'),/Source V1 inattendue/);
});
