import { randomBytes } from 'node:crypto';
import { once } from 'node:events';
import { createPreviewServer } from '../scripts/preview-server.mjs';
import { passwordRecord } from '../scripts/bureau-auth.mjs';

// Isolated in-memory account: never saved to .local, never logged or used on live preview.
export async function startBrowserServer() {
  const password = randomBytes(24).toString('base64url');
  const user = { login: 'recette-ephemere', role: 'bureau', enabled: true, ...await passwordRecord(password) };
  const server = createPreviewServer({ getUsers: async () => [user] });
  server.listen(0, '127.0.0.1'); await once(server, 'listening');
  return {
    origin: `http://127.0.0.1:${server.address().port}`,
    httpCredentials: { username: user.login, password },
    stop: () => new Promise(resolve => { server.close(resolve); server.closeAllConnections(); })
  };
}
