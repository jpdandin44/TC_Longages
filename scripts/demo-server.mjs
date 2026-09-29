import http from 'node:http';
import { readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const pages = new Set(['index.html','parcours.html','adherer.html','bureau.html','inscriptions.html','communication.html','actualites-bureau.html']);
export function createDemoServer({ directory = new URL('../prototype/', import.meta.url) } = {}) {
  const server = http.createServer(async (req, res) => {
    const port = server.address().port;
    const head = { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store', 'X-Content-Type-Options': 'nosniff', 'Referrer-Policy': 'no-referrer', 'X-Frame-Options': 'DENY', 'Content-Security-Policy': "default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; img-src data:; connect-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'" };
    const send = (status, body = '', extra = {}) => { res.writeHead(status, { ...head, ...extra }); res.end(req.method === 'HEAD' ? undefined : body); };
    if (![`127.0.0.1:${port}`, `localhost:${port}`].includes(req.headers.host)) return send(403, 'Hôte non autorisé');
    if (!['GET','HEAD'].includes(req.method)) return send(405, '', { Allow: 'GET, HEAD' });
    try {
      const pathname = new URL(req.url, `http://127.0.0.1:${port}`).pathname;
      const name = pathname === '/' ? 'parcours.html' : pathname.slice(1);
      if (!pages.has(name)) return send(404, 'Page introuvable');
      send(200, await readFile(new URL(name, directory)));
    } catch { send(503, 'Démonstration à régénérer.'); }
  });
  return server;
}
if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  // In the archive this same file sits next to the standalone HTML pages.
  const directory = path.basename(fileURLToPath(import.meta.url)) === 'serveur-demo.mjs' ? new URL('./', import.meta.url) : new URL('../prototype/', import.meta.url);
  const server = createDemoServer({ directory });
  server.on('error', error => { console.error(error.code === 'EADDRINUSE' ? 'Le port 4174 est occupé. Vérifiez le service existant sans arrêter un autre projet.' : 'La démonstration ne peut pas démarrer.'); process.exitCode = 1; });
  server.listen(4174, '127.0.0.1', () => console.log('Visite complète : http://127.0.0.1:4174/\nDémonstration fictive seulement. Aucun compte réel ni diffusion. Ctrl+C pour arrêter.'));
}
