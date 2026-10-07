import http from 'node:http';
import { readFile, lstat } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const names = ['index.html', 'competitions.html', 'calendrier.html', 'disponibilites.html', 'equipes.html', 'espace.html', 'contact.html'];
const routes = new Map([...names.map(name => ['/' + name, name]), ['/', 'index.html'], ['/robots.txt', 'robots.txt']]);
const responseHeaders = {
  'Cache-Control': 'no-store',
  'X-Content-Type-Options': 'nosniff',
  'X-Frame-Options': 'DENY',
  'Referrer-Policy': 'no-referrer',
  'X-Robots-Tag': 'noindex, nofollow',
  'Content-Security-Policy': "default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; img-src data:; connect-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'"
};

export function createOfficialServer({ directory = new URL('../officiel/', import.meta.url), calendarPreview = false } = {}) {
  const folder = path.resolve(directory instanceof URL ? fileURLToPath(directory) : directory);
  const server = http.createServer(async (req, res) => {
    const send = (status, body = '', extra = {}) => {
      res.writeHead(status, { ...responseHeaders, 'Content-Type': 'text/html; charset=utf-8', 'Content-Length': Buffer.byteLength(body), ...extra });
      res.end(req.method === 'HEAD' ? undefined : body);
    };
    try {
      const port = server.address()?.port;
      if (!['127.0.0.1', '::1', '::ffff:127.0.0.1'].includes(req.socket.remoteAddress)
        || ![`127.0.0.1:${port}`, `localhost:${port}`].includes(req.headers.host)) return send(403, 'Hôte non autorisé.');
      if (!['GET', 'HEAD'].includes(req.method)) return send(405, '', { Allow: 'GET, HEAD' });
      if (!req.url?.startsWith('/') || req.url.startsWith('//') || req.url.length > 8192 || req.url.includes('#')) return send(400, 'Requête non valide.');
      // Match the raw path exactly. No decoding, directory listing or path joining
      // from a client-controlled path is permitted, including legacy private URLs.
      const name = routes.get(req.url.split('?')[0]);
      if (!name) return send(404, 'Page introuvable.');
      const file = path.join(folder, name);
      const info = await lstat(file);
      if (!info.isFile() || info.isSymbolicLink()) return send(404, 'Page introuvable.');
      const body = await readFile(file);
      const extra = { 'Content-Type': name === 'robots.txt' ? 'text/plain; charset=utf-8' : 'text/html; charset=utf-8' };
      if (name === 'calendrier.html' && (calendarPreview || body.includes('<iframe class="club-calendar"'))) extra['Content-Security-Policy'] = responseHeaders['Content-Security-Policy'] + '; frame-src https://calendar.google.com/calendar/';
      return send(200, body, extra);
    } catch {
      return send(503, 'Aperçu officiel absent ou indisponible. Régénérez les fichiers locaux.');
    }
  });
  server.requestTimeout = 15000;
  server.headersTimeout = 10000;
  return server;
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const server = createOfficialServer();
  server.on('error', error => {
    console.error(error.code === 'EADDRINUSE' ? 'Le port 4180 est occupé. Vérifiez le service existant sans arrêter un autre projet.' : 'L’aperçu officiel ne peut pas démarrer.');
    process.exitCode = 1;
  });
  server.listen(4180, '127.0.0.1', () => console.log('Aperçu officiel local : http://127.0.0.1:4180/\nEspace privé non activé. Aucun envoi, compte ou déploiement. Ctrl+C pour arrêter.'));
}
