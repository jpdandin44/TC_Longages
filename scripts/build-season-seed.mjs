import { mkdir, writeFile, readFile } from 'node:fs/promises';
import { officialTariffs } from './adult-tariffs.mjs';

// Derived seed only. The Bureau's saved seasonal catalogue lives in Drupal.
const seed = {
  season: officialTariffs.season,
  formulas: officialTariffs.groups.flatMap(group => group.rows.map(row => ({
    id: row.id, label: row.label, profile: group.id === 'adultes' ? 'adult' : 'minor', amountCents: row.amountCents,
  }))),
  slots: [], notice: '',
};
if (seed.formulas.some(row => !Number.isInteger(row.amountCents))) throw new Error('Tarif ambigu : ne pas initialiser le catalogue.');
const directory = new URL('../drupal/web/modules/custom/tcl_bureau/resources/', import.meta.url);
const file = new URL('season-seed.json', directory);
const content = JSON.stringify(seed, null, 2) + '\n';
if (process.argv.includes('--check')) {
  if (await readFile(file, 'utf8') !== content) throw new Error('Le catalogue initial doit être régénéré.');
} else {
  await mkdir(directory, { recursive: true });
  await writeFile(file, content);
}
console.log('Catalogue initial : 10 formules, brouillon uniquement, aucun créneau inventé.');
