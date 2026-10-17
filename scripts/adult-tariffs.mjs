import { readFile } from 'node:fs/promises';
import { tariffs, renderTariffRow } from './tariffs.mjs';

export const adultTariffCorrection = JSON.parse(await readFile(new URL('../data/correctif-tarifs-adultes.json', import.meta.url), 'utf8'));

export function correctedTariffs(source = tariffs, correction = adultTariffCorrection) {
  if (source.season !== correction.season || source.currency !== correction.currency) throw new Error('Saison ou devise incompatible.');
  const result = structuredClone(source);
  const rows = result.groups.flatMap(group => group.rows);
  const existing = rows.filter(row => row.id === correction.replacesFormulaId);
  if (existing.length !== 1 || existing[0].amountCents !== null || existing[0].alternativeAmountsCents?.join(',') !== '12500,15000') throw new Error('Ancienne formule inattendue.');
  if (rows.some(row => row.id === 'cours-adultes-competition')) throw new Error('Correctif deja present.');
  if (rows.find(row => row.id === 'adulte-competition')?.amountCents !== 10000) throw new Error('Adhesion competition inattendue.');
  const group = result.groups.find(item => item.rows.some(row => row.id === correction.replacesFormulaId));
  group.rows.splice(group.rows.findIndex(row => row.id === correction.replacesFormulaId), 1, ...structuredClone(correction.rows));
  result.notes[correction.noteIndex] = correction.note;
  return result;
}

export const officialTariffs = correctedTariffs();

export function adultTariffPatch() {
  return {
    oldRow: renderTariffRow(tariffs.groups.flatMap(group => group.rows).find(row => row.id === adultTariffCorrection.replacesFormulaId)),
    newRows: adultTariffCorrection.rows.map(renderTariffRow).join(''),
    oldNote: tariffs.notes[adultTariffCorrection.noteIndex],
    newNote: adultTariffCorrection.note,
  };
}

// Only the table changes here: Drupal's editorial block digest must stay intact.
export function patchAdultTariffTemplate(html) {
  const patch = adultTariffPatch();
  if (html.split(patch.oldRow).length !== 2 || html.includes(patch.newRows)) throw new Error('Modele inattendu ou deja corrige.');
  return html.replace(patch.oldRow, () => patch.newRows);
}
