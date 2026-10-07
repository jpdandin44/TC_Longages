import { readFile } from 'node:fs/promises';

export const officialPages = ['index.html','competitions.html','calendrier.html','disponibilites.html','equipes.html','espace.html','contact.html'];
export const escapeHTML = value => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[char]);
const fail = message => { throw new Error('Configuration officiel : ' + message); };
function text(value, label) { if (typeof value !== 'string' || !value.trim() || value.length > 500 || /[\u0000-\u001f]/.test(value)) fail(label); }
function https(value, label) {
  let url; try { url = new URL(value); } catch { fail(label); }
  if (url.protocol !== 'https:' || url.username || url.password || /\s/.test(value)) fail(label);
  return url;
}
export function validateOfficialConfig(config) {
  if (config.mode !== 'local-review-only' || config.betaMode !== true || config.noindex !== true) fail('cet aperçu doit rester en préparation et non indexable.');
  if (config.contact?.mode !== 'preview-only' || config.authentication?.status !== 'decision-pending' || config.authentication.technology !== null) fail('aucune activation de collecte ou de connexion dans ce lot.');
  if (config.domain !== null && (typeof config.domain !== 'string' || !/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/i.test(config.domain))) fail('nom de domaine seul attendu.');
  for (const key of ['name','wordmark','email','address','postalCity']) text(config.club?.[key], 'club.' + key);
  if (!/^[^\s@<>]+@[^\s@<>]+\.[^\s@<>]+$/.test(config.club.email)) fail('e-mail officiel invalide.');
  text(config.contact.testSupport?.email, 'adresse de support des tests');
  if (!/^[^\s@<>]+@[^\s@<>]+\.[^\s@<>]+$/.test(config.contact.testSupport.email) || !['planned', 'mailbox-created'].includes(config.contact.testSupport.status)) fail('adresse de support prévue ou boîte créée, sans activation de messagerie dans cet aperçu.');
  for (const key of ['primary','dark','accent','soft','ink','muted','background','surface','border']) if (!/^#[a-f0-9]{6}$/i.test(config.theme?.[key])) fail('couleur ' + key);
  for (const key of ['tenup','offers','facebook','map']) https(config.links?.[key], 'lien ' + key);
  if (!Array.isArray(config.teams) || !Array.isArray(config.google?.forms)) fail('listes équipes/formulaires attendues.');
  const ids = new Set();
  for (const team of config.teams) {
    if (!/^[a-z0-9-]{1,50}$/.test(team.id) || ids.has(team.id)) fail('identifiant d’équipe invalide ou doublon.');
    ids.add(team.id); text(team.name, 'nom équipe'); text(team.category, 'catégorie équipe');
    if (!Array.isArray(team.tags) || team.tags.some(tag => typeof tag !== 'string' || tag.length > 80)) fail('tags équipe.');
    if (Object.keys(team).some(key => !['id','name','category','tags'].includes(key))) fail('aucun joueur, coordonnée ou effectif dans une équipe publique.');
  }
  if (config.google.calendarUrl !== null) {
    const url = https(config.google.calendarUrl, 'calendrier');
    if (url.hostname !== 'calendar.google.com' || config.google.calendarSharingReviewed !== true) fail('calendrier Google et revue de partage requis.');
  }
  if (config.google.calendarEmbedId != null) {
    const id = config.google.calendarEmbedId;
    if (typeof id !== 'string' || id.length > 254 || !/^[A-Za-z0-9._+%-]+@[A-Za-z0-9.-]+$/.test(id) || config.google.calendarSharingReviewed !== true) fail('identifiant d’agenda public et revue de partage requis.');
  }
  const formIds = new Set();
  for (const form of config.google.forms) {
    if (!ids.has(form.teamId) || !/^[a-z0-9-]{1,60}$/.test(form.id) || formIds.has(form.id)) fail('formulaire sans équipe ou identifiant unique.');
    formIds.add(form.id); text(form.label, 'libellé de formulaire');
    const url = https(form.url, 'formulaire');
    const respondentForm = url.hostname === 'docs.google.com' && /^\/forms\/(?:u\/\d+\/)?d\/(?:e\/)?[A-Za-z0-9_-]+\/viewform\/?$/.test(url.pathname);
    const shortForm = url.hostname === 'forms.gle' && /^\/[A-Za-z0-9_-]+\/?$/.test(url.pathname);
    if (!(respondentForm || shortForm) || url.hash || form.sharingReviewed !== true) fail('lien Google destiné aux répondants et revue de confidentialité requis.');
    if (Object.keys(form).some(key => !['id','teamId','label','url','sharingReviewed'].includes(key))) fail('aucune liste de joueurs ou feuille de réponses dans la configuration des formulaires.');
  }
  if (config.quickAccess?.length !== 6) fail('six raccourcis attendus.');
  for (const item of config.quickAccess) {
    text(item.label,'libellé raccourci'); text(item.description,'description raccourci');
    const [page,anchor] = String(item.href).replace(/^\.\//,'').split('#');
    if (!item.href.startsWith('./') || !officialPages.includes(page) || (anchor && !/^[a-z-]+$/.test(anchor))) fail('destination raccourci.');
  }
  return config;
}
export async function loadOfficialConfig() {
  return validateOfficialConfig(JSON.parse(await readFile(new URL('../config/officiel.json',import.meta.url),'utf8')));
}
