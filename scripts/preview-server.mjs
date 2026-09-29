import http from 'node:http';
import { readFile } from 'node:fs/promises';
import { loadUsers, validateUsers, authenticate } from './bureau-auth.mjs';

const publicRoutes = new Map([
  ['/', new URL('../dist/index.html', import.meta.url)],
  ['/index.html', new URL('../dist/index.html', import.meta.url)]
]);
const privateRoutes = new Map(['bureau', 'communication', 'inscriptions', 'actualites-bureau'].map(name => [
  `/${name}.html`, new URL(`../dist/${name}.html`, import.meta.url)
]));
const headers = {
  'Content-Type': 'text/html; charset=utf-8',
  'Cache-Control': 'no-store',
  'X-Content-Type-Options': 'nosniff',
  'X-Frame-Options': 'DENY',
  'Referrer-Policy': 'no-referrer',
  'Content-Security-Policy': "default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; img-src data:; connect-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'"
};
function notice(title, message) {
  return `<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>${title} — TC Longages</title><style>body{margin:0;background:#fcfcf9;color:#153c32;font:17px/1.7 Arial,sans-serif}main{max-width:620px;margin:12vh auto;padding:32px}h1{font:42px/1.15 Georgia,serif}small{letter-spacing:2px}a{color:inherit}</style><main><small>TENNIS CLUB DE LONGAGES · ESPACE BUREAU</small><h1>${title}</h1><p>${message}</p><p><a href="/index.html">Retour au site du club</a></p></main></html>`;
}
export function createPreviewServer({ getUsers = loadUsers } = {}) {
  const failures = new Map();
  let verifications = 0;
  const server = http.createServer(async (req, res) => {
    const send = (status, body = '', extra = {}) => {
      res.writeHead(status, { ...headers, 'Content-Length': Buffer.byteLength(body), ...extra });
      res.end(req.method === 'HEAD' ? undefined : body);
    };
    try {
      const port = server.address().port;
      if (![`127.0.0.1:${port}`, `localhost:${port}`].includes(req.headers.host)) return send(403, 'Hôte non autorisé');
      if (!['GET', 'HEAD'].includes(req.method)) return send(405, '', { Allow: 'GET, HEAD' });
      const pathname = new URL(req.url, `http://127.0.0.1:${port}`).pathname;
      const isPrivate = privateRoutes.has(pathname);
      const file = publicRoutes.get(pathname) || privateRoutes.get(pathname);
      if (!file) return send(404, 'Page introuvable');
      if (isPrivate) {
        let users;
        try {
          users = validateUsers({ version: 1, users: await getUsers() });
          if (!users.some(user => user.enabled && user.role === 'bureau')) throw new Error('No active account');
        } catch {
          return send(503, notice('Accès bureau en préparation', 'Les comptes du bureau ne sont pas encore configurés ou leur configuration est indisponible. Les pages Communication et Inscriptions restent fermées.'));
        }
        const key = req.socket.remoteAddress;
        const now = Date.now();
        let attempt = failures.get(key);
        if (attempt && attempt.until <= now) { failures.delete(key); attempt = null; }
        if ((attempt?.count || 0) >= 10 || verifications >= 4) return send(429, notice('Réessayez dans un instant', 'Trop de tentatives rapprochées. Attendez une minute avant de réessayer.'), { 'Retry-After': '60' });
        let accepted = false;
        verifications++;
        try { accepted = await authenticate(req.headers.authorization, users); }
        finally { verifications--; }
        if (!accepted) {
          if (req.headers.authorization) {
            const finishedAt = Date.now();
            const latest = failures.get(key);
            const current = latest && latest.until > finishedAt ? latest : null;
            failures.set(key, { count: (current?.count || 0) + 1, until: current?.until || finishedAt + 60000 });
          }
          return send(401, notice('Accès réservé au bureau', 'Connectez-vous avec un compte du bureau actif. Si vous ne disposez pas encore d’accès, contactez le responsable du site.'), { 'WWW-Authenticate': 'Basic realm="TC Longages - Bureau", charset="UTF-8"' });
        }
        failures.delete(key);
      }
      try { return send(200, await readFile(file)); }
      catch { return send(503, notice('Page en préparation', 'Le prototype doit être régénéré avant consultation.')); }
    } catch { if (!res.headersSent) send(400, 'Requête non valide'); else res.end(); }
  });
  return server;
}
