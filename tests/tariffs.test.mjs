import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile, realpath } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import { relative, isAbsolute } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';
import { tariffs, demoFormulas, compileDemoData } from '../scripts/tariffs.mjs';
const root = new URL('../', import.meta.url);

test('Le relevé tarifaire conserve neuf formules et les ambiguïtés des deux PDF fournis', async () => {
  assert.equal(tariffs.season, '2026-2027');
  assert.equal(demoFormulas.length, 9);
  assert.deepEqual(tariffs.groups.flatMap(g => g.rows.map(r => r.amountCents)), [11000,13000,13000,13000,4000,null,10000,7000,5000]);
  const adult = tariffs.groups[1].rows[0];
  assert.deepEqual(adult.alternativeAmountsCents, [12500,15000]);
  assert.equal(tariffs.discounts.amount_cents, null);
  const fixtureRoot = await realpath(fileURLToPath(new URL('tests/fixtures/tarifs/', root)));
  for (const source of tariffs.sources) {
    assert.match(source.file, /^\.\.\/tests\/fixtures\/tarifs\/fiche-(ecole-tennis|adultes)\.pdf$/);
    const sourcePath = await realpath(fileURLToPath(new URL(source.file, new URL('data/tarifs-inscription.json', root))));
    const within = relative(fixtureRoot, sourcePath);
    assert.ok(within && !within.startsWith('..') && !isAbsolute(within), 'La source PDF reste dans les fixtures du dépôt');
    const bytes = await readFile(sourcePath);
    assert.equal(createHash('sha256').update(bytes).digest('hex'), source.sha256);
  }
});

test('FAQ et formulaire intègrent la même source tarifaire sans téléchargement de PDF ni requête JSON', async () => {
  for (const path of ['release/index.html','prototype/index.html','demo-o2switch/index.html']) {
    const html = await readFile(new URL(path, root), 'utf8');
    const section = html.match(/<details id="faq-tarifs">([\s\S]*?)<\/details>/)?.[1];
    assert.ok(section, path);
    assert.equal((section.match(/scope="row"/g) || []).length, 9);
    for (const row of demoFormulas) assert.ok(section.includes(row.label), row.label);
    for (const note of tariffs.publicNoteIndexes.map(index => tariffs.notes[index])) assert.ok(section.includes(note));
    assert.match(section, /Conditions à confirmer/);
    assert.match(section, /Horaires à confirmer/);
    assert.doesNotMatch(section, /TCL_TARIFF|\.pdf|fetch\(/);
  }
  const context = vm.createContext({window:{},localStorage:{getItem:()=>null}});
  vm.runInContext(compileDemoData(await readFile(new URL('src/demo-data.js', root),'utf8')),context);
  assert.deepEqual(JSON.parse(JSON.stringify(context.window.TCLDemo.config.formulas)), demoFormulas);
});
