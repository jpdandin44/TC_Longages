import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import { adultTariffPatch, patchAdultTariffTemplate } from './adult-tariffs.mjs';

const root = new URL('../', import.meta.url);
const source = await readFile(new URL('config/launch-beta/source/drupal/site-pages/index.html', root), 'utf8');
const patch = adultTariffPatch();
const template = patchAdultTariffTemplate(source);
const note = `<p class="tariff-note">${patch.oldNote}</p>`;
if (template.split(note).length !== 2) throw new Error('Note tarifaire inattendue.');
const output = new URL('.local/tarifs-adultes/', root);
await mkdir(output, { recursive: true });
const delta = {
  id: 'tcl-tarifs-adultes-20261017',
  scope: 'One homepage table row and one existing Drupal editorial note only',
  productionAuthorized: false,
  hostedQualification: 'not_run',
  frozenTemplateSha256: createHash('sha256').update(source).digest('hex'),
  templatePatch: { before: patch.oldRow, after: patch.newRows, expectedOccurrences: 1 },
  editorialNote: { before: patch.oldNote, after: patch.newNote, updateThroughDrupalRevision: true },
  safeguards: ['preserve_edits', 'preserve_maintenance', 'no_preproduction_replacement', 'fresh_private_backup_and_restore_required', 'no_historical_database_restore'],
};
await writeFile(new URL('correctif.json', output), JSON.stringify(delta, null, 2) + '\n');
// This preview has no private editorial data and must never replace the hosted page.
await writeFile(new URL('apercu-tarifs.html', output), template.replace(note, `<p class="tariff-note">${patch.newNote}</p>`));
console.log('Correctif et aperçu local préparés dans .local/tarifs-adultes/. Aucun fichier hébergé ni contenu Drupal modifié.');
