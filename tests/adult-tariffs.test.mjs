import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { tariffs, renderTariffs } from '../scripts/tariffs.mjs';
import { adultTariffCorrection, adultTariffPatch, correctedTariffs, officialTariffs, patchAdultTariffTemplate } from '../scripts/adult-tariffs.mjs';

test('Le correctif approuvé sépare les cours 150/170 et préserve exactement les huit autres lignes', () => {
  const source = JSON.stringify(tariffs);
  const current = correctedTariffs();
  const rows = current.groups.flatMap(group => group.rows);
  assert.deepEqual(rows.filter(row => row.id.startsWith('cours-adultes')), [
    { id: 'cours-adultes', label: 'Cours Adultes', priceLabel: '150,00 €', amountCents: 15000 },
    { id: 'cours-adultes-competition', label: 'Cours adultes compétition', priceLabel: '170,00 €', amountCents: 17000 },
  ]);
  for (const old of tariffs.groups.flatMap(group => group.rows).filter(row => row.id !== 'cours-adultes')) assert.deepEqual(rows.find(row => row.id === old.id), old);
  assert.equal(rows.find(row => row.id === 'adulte-competition').amountCents, 10000);
  assert.equal(rows.length, 10);
  assert.equal(JSON.stringify(tariffs), source);
  for (const [index, note] of tariffs.notes.entries()) if (index !== adultTariffCorrection.noteIndex) assert.equal(current.notes[index], note);
});

test('Les sources documentaires et démonstrations conservent les tarifs PDF historiques', () => {
  assert.match(renderTariffs(), /125,00 € ou 150 €/);
  assert.doesNotMatch(renderTariffs(officialTariffs), /125,00 € ou 150 €|Cours adultes : la fiche indique/);
  assert.throws(() => correctedTariffs({ ...tariffs, season: '2027-2028' }), /Saison/);
  assert.throws(() => correctedTariffs({ ...tariffs, currency: 'USD' }), /devise/);
  assert.throws(() => correctedTariffs(officialTariffs), /Ancienne formule/);
  const changed = structuredClone(tariffs);
  changed.groups.flatMap(group => group.rows).find(row => row.id === 'adulte-competition').amountCents = 17000;
  assert.throws(() => correctedTariffs(changed), /Adhesion competition/);
});

test('Le delta du modèle bêta ne touche que la ligne attendue et refuse les doubles applications ou une dérive', async () => {
  const html = await readFile(new URL('../config/launch-beta/source/drupal/site-pages/index.html', import.meta.url), 'utf8');
  const patch = adultTariffPatch();
  const next = patchAdultTariffTemplate(html);
  assert.equal(next.replace(patch.newRows, patch.oldRow), html);
  assert.equal((next.match(/scope="row"/g) || []).length, 10);
  assert.ok(next.includes(patch.oldNote), 'La note du modèle conserve son empreinte Drupal ; sa valeur éditoriale doit être rapprochée séparément.');
  assert.throws(() => patchAdultTariffTemplate(next), /deja corrige/);
  assert.throws(() => patchAdultTariffTemplate(html + patch.oldRow), /inattendu/);
  assert.throws(() => patchAdultTariffTemplate(html.replace('125,00 € ou 150 €', '126,00 €')), /inattendu/);
});

const php = process.env.TCL_PHP_BINARY || 'php';
const phpProbe = spawnSync(php, ['-r', 'echo extension_loaded("mbstring") ? "ready" : "missing";'], { encoding: 'utf8' });
test('Le rendu Drupal garde son empreinte, les textes personnalisés et ne montre plus la note obsolète', { skip: phpProbe.stdout !== 'ready' ? 'PHP avec mbstring requis pour ce contrôle natif' : false }, () => {
  const result = spawnSync(php, [fileURLToPath(new URL('adult-tariffs-editor.php', import.meta.url))], { input: JSON.stringify(adultTariffPatch()), encoding: 'utf8' });
  assert.equal(result.status, 0, result.stderr);
  assert.deepEqual(JSON.parse(result.stdout), { blocksDigestPreserved: true, titleIntroPreserved: true, customTextPreserved: true, oldNoteRemoved: true, tariffRows: 10 });
});
