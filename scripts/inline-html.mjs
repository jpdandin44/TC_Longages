import { readFile } from 'node:fs/promises';
import { compileDemoData, renderTariffs } from './tariffs.mjs';
import { loadOfficialConfig, escapeHTML } from './official-config.mjs';
const source = new URL('../src/', import.meta.url);
export async function inline(html) {
  // Une seule adresse de contact alimente toutes les variantes générées.
  if (html.includes('{{CLUB_EMAIL}}')) {
    const config = await loadOfficialConfig();
    html = html.replaceAll('{{CLUB_EMAIL}}', () => escapeHTML(config.club.email));
  }
  html = html.replace('<!-- TCL_TARIFF_TABLES -->', () => renderTariffs());
  // Le JPEG fourni reste la source exacte ; seule sa fenêtre d’affichage est cadrée.
  // Centraliser les marques évite de dupliquer l’image et son cadrage dans les pages.
  const oldBrand = /<span class="brand-mark"(?: aria-hidden="true")?>TC<span>L<\/span><\/span>/g;
  const oldAvatar = /<span class="avatar" aria-hidden="true">TCL<\/span>/g;
  const logoMarker = /<span class="club-logo( club-logo-avatar)?"(?: aria-hidden="true")?><\/span>/g;
  const hasLogo = /class="brand-mark"|class="avatar" aria-hidden="true">TCL|class="club-logo(?: club-logo-avatar)?"/.test(html);
  if (hasLogo) {
    const logo = await readFile(new URL('../Images_Photos/Logo.jpeg', source));
    const image = `<img class="club-logo-image" src="data:image/jpeg;base64,${logo.toString('base64')}" width="1131" height="1600" alt="" decoding="async">`;
    const mark = (avatar = false) => `<span class="club-logo${avatar ? ' club-logo-avatar' : ''}" aria-hidden="true">${image}</span>`;
    html = html.replace(oldBrand, () => mark()).replace(oldAvatar, () => mark(true)).replace(logoMarker, (_, avatar) => mark(Boolean(avatar)));
    const branding = await readFile(new URL('brand.css', source), 'utf8');
    html = html.replace('</head>', () => `<style data-club-brand>\n${branding}\n</style>\n</head>`);
  }
  for (const match of [...html.matchAll(/<link rel="stylesheet" href="\.\/([\w.-]+\.css)">/g)]) {
    const css = await readFile(new URL(match[1], source), 'utf8');
    html = html.replace(match[0], () => `<style>\n${css}\n</style>`);
  }
  for (const match of [...html.matchAll(/<script (?:defer )?src="\.\/([\w.-]+\.js)"(?: defer)?><\/script>/g)]) {
    let js = await readFile(new URL(match[1], source), 'utf8');
    if (match[1] === 'demo-data.js') js = compileDemoData(js);
    html = html.replace(match[0], '');
    html = html.replace('</body>', () => `<script>\n${js}\n</script>\n</body>`);
  }
  for (const match of [...html.matchAll(/src="\.\/assets\/([\w.-]+\.webp)"/g)]) {
    const photo = await readFile(new URL(`assets/${match[1]}`, source));
    html = html.replace(match[0], `src="data:image/webp;base64,${photo.toString('base64')}"`);
  }
  for (const [filename, mime] of [['Image_terrain.jpg', 'image/jpeg'], ['Image_terrain_OK.png', 'image/png']]) {
    const marker = `src="../Images_Photos/${filename}"`;
    if (!html.includes(marker)) continue;
    const court = await readFile(new URL('../Images_Photos/' + filename, source));
    html = html.replaceAll(marker, `src="data:${mime};base64,${court.toString('base64')}"`);
  }
  return html.replaceAll('\r\n', '\n');
}
