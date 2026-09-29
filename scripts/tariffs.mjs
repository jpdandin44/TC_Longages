import { readFile } from 'node:fs/promises';

export const tariffs = JSON.parse(await readFile(new URL('../data/tarifs-inscription.json', import.meta.url), 'utf8'));
const escapeHTML = value => String(value).replace(/[&<>"']/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' })[char]);
const needsClarification = row => row.amountCents === null;
export const demoFormulas = tariffs.groups.flatMap(group => group.rows.map(row => ({
  id: row.id, label: row.label,
  priceLabel: row.priceLabel + (needsClarification(row) ? ' · conditions à confirmer' : '')
})));

// Les pages sont autonomes : le JSON est intégré à la construction, sans requête au serveur.
export function compileDemoData(source) {
  if (!source.includes('/* TCL_TARIFFS */ []') || !source.includes("/* TCL_SEASON */ ''")) throw new Error('Repère des tarifs de démonstration absent.');
  return source.replace('/* TCL_TARIFFS */ []', JSON.stringify(demoFormulas).replaceAll('<', '\\u003c'))
    .replace("/* TCL_SEASON */ ''", JSON.stringify(tariffs.season));
}

export function renderTariffs() {
  const tables = tariffs.groups.map(group => `<table class="tariff-table"><caption>${escapeHTML(group.label)}</caption><thead><tr><th scope="col">Formule</th><th scope="col">Tarif</th></tr></thead><tbody>${group.rows.map(row => `<tr><th scope="row">${escapeHTML(row.label)}</th><td>${escapeHTML(row.priceLabel)}${needsClarification(row) ? '<small>Conditions à confirmer</small>' : ''}</td></tr>`).join('')}</tbody></table>`).join('\n');
  return `<div class="tariff-grid"><p class="tariff-season">Saison ${escapeHTML(tariffs.season)}</p><p>Tarifs indiqués dans les fiches d’inscription du club.</p>${tables}${tariffs.publicNoteIndexes.map(index => tariffs.notes[index]).map(note => `<p class="tariff-note">${escapeHTML(note)}</p>`).join('')}</div>`;
}
