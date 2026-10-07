import { createOfficialServer } from './official-server.mjs';
import { loadCalendarPreview } from './calendar-preview.mjs';

await loadCalendarPreview();
const server = createOfficialServer({ directory: new URL('../.local/agenda-preview/', import.meta.url), calendarPreview: true });
server.on('error', error => {
  console.error(error.code === 'EADDRINUSE' ? 'Le port 4184 est occupé ; le service existant est conservé.' : 'L’aperçu local de l’agenda ne peut pas démarrer.');
  process.exitCode = 1;
});
server.listen(4184, '127.0.0.1', () => console.log('Agenda local : http://127.0.0.1:4184/calendrier.html'));
