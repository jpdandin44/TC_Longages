import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { once } from 'node:events';
import { fileURLToPath } from 'node:url';
import { loadCalendarPreview, validateCalendarPreview } from '../scripts/calendar-preview.mjs';
import { createOfficialServer } from '../scripts/official-server.mjs';
import { validateOfficialPackage } from '../scripts/package-officiel.mjs';

const root = new URL('../', import.meta.url);
const read = file => readFile(new URL(file, root), 'utf8');
const run = promisify(execFile);

test('L’agenda local conserve une revue de partage en attente et refuse une autre origine ou des paramètres privés', async () => {
  const config = JSON.parse(await read('config/agenda-local.json'));
  const calendar = await loadCalendarPreview();
  assert.equal(new URL(calendar.embedUrl).searchParams.get('mode'), 'AGENDA');
  assert.equal(new URL(calendar.embedUrl).searchParams.get('ctz'), 'Europe/Paris');
  for (const change of [
    c => c.status = 'published', c => c.sharingReviewed = true,
    c => c.embedUrl = c.embedUrl.replace('calendar.google.com', 'calendar.google.com.attacker.invalid'),
    c => c.embedUrl = c.embedUrl.replace('/calendar/embed?', '/calendar/r?'),
    c => c.embedUrl += '&src=another@group.calendar.google.com',
    c => c.embedUrl += '&token=private',
    c => c.embedUrl = c.embedUrl.replace('Europe%2FParis', 'America%2FNew_York'),
    c => c.embedUrl = c.embedUrl.replace('https://', 'https://user:secret@')
  ]) {
    const fixture = structuredClone(config); change(fixture);
    assert.throws(() => validateCalendarPreview(fixture));
  }
});

test('La construction de l’agenda reste isolée : une iframe titrée sur Calendrier, sorties officielles et configuration préservées', async () => {
  const baseline = await read('data/officiel-manifest.json');
  const config = await read('config/officiel.json');
  await run(process.execPath, ['scripts/build-officiel.mjs', '--calendar-preview'], { cwd: fileURLToPath(root) });
  assert.equal(await read('data/officiel-manifest.json'), baseline);
  assert.equal(await read('config/officiel.json'), config);
  assert.doesNotMatch(await read('officiel/calendrier.html'), /<iframe/);
  for (const name of ['index', 'competitions', 'calendrier', 'disponibilites', 'equipes', 'espace', 'contact']) {
    const html = await read('.local/agenda-preview/' + name + '.html');
    assert.equal((html.match(/<iframe\b/g) || []).length, name === 'calendrier' ? 1 : 0);
    assert.match(html, /noindex,nofollow/);
    assert.doesNotMatch(html, /ownerAccount|sharingReviewed|localStorage/);
    if (name === 'calendrier') {
      assert.match(html, /title="Agenda des événements du Tennis Club de Longages"/);
      assert.match(html, /mode=AGENDA&amp;hl=fr/);
      assert.match(html, /width: 100%/);
      assert.match(html, /Ouvrir dans Google Agenda/);
    }
  }
});

test('Seule la route Calendrier de l’aperçu agenda autorise le cadre Google ; les autres protections restent actives', async t => {
  for (const calendarPreview of [false, true]) {
    const server = createOfficialServer({ directory: new URL('../officiel/', import.meta.url), calendarPreview });
    server.listen(0, '127.0.0.1'); await once(server, 'listening');
    t.after(() => new Promise(resolve => { server.close(resolve); server.closeAllConnections(); }));
    const origin = 'http://127.0.0.1:' + server.address().port;
    for (const page of ['calendrier', 'contact']) {
      const response = await fetch(origin + '/' + page + '.html');
      assert.equal(response.status, 200);
      const policy = response.headers.get('content-security-policy');
      assert.equal(policy.includes('frame-src https://calendar.google.com'), calendarPreview && page === 'calendrier');
      assert.match(policy, /connect-src 'none'.*form-action 'none'.*frame-ancestors 'none'/);
      assert.match(response.headers.get('x-robots-tag'), /noindex/);
    }
    assert.equal((await fetch(origin + '/.local/agenda-preview-manifest.json')).status, 404);
    assert.equal((await fetch(origin + '/calendrier.html', { method: 'POST' })).status, 405);
  }
});

test('Le manifeste de l’agenda local est refusé par l’archive officielle', async () => {
  await assert.rejects(validateOfficialPackage({ directory: new URL('../.local/agenda-preview/', import.meta.url), manifestFile: new URL('../.local/agenda-preview-manifest.json', import.meta.url) }), /Manifeste/);
});
