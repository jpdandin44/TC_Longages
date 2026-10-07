import { readFile } from 'node:fs/promises';

export function validateCalendarPreview(value) {
  if (value?.project !== 'TC_Longages' || value.status !== 'local-preview-only' || value.sharingReviewed !== false) throw new Error('L’agenda de cet aperçu reste local et son partage est à qualifier.');
  const url = new URL(value.embedUrl);
  if (url.protocol !== 'https:' || url.hostname !== 'calendar.google.com' || url.port || url.username || url.password || url.hash || url.pathname !== '/calendar/embed') throw new Error('URL d’intégration Google Agenda invalide.');
  if (url.searchParams.getAll('src').length !== 1 || url.searchParams.getAll('ctz').length !== 1 || [...url.searchParams.keys()].some(key => !['src', 'ctz'].includes(key))) throw new Error('Paramètres de l’agenda invalides.');
  if (!/^[a-zA-Z0-9._+-]+@group\.calendar\.google\.com$/.test(url.searchParams.get('src') || '') || url.searchParams.get('ctz') !== 'Europe/Paris') throw new Error('Agenda partagé et fuseau Europe/Paris requis.');
  url.searchParams.set('mode', 'AGENDA');
  url.searchParams.set('hl', 'fr');
  const open = new URL('https://calendar.google.com/calendar/r');
  open.searchParams.set('cid', Buffer.from(url.searchParams.get('src')).toString('base64'));
  return { embedUrl: url.href, openUrl: open.href };
}

export async function loadCalendarPreview() {
  return validateCalendarPreview(JSON.parse(await readFile(new URL('../config/agenda-local.json', import.meta.url), 'utf8')));
}
