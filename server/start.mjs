import { createBureauServer } from './bureau-server.mjs';
import { settingsFromEnv } from './settings.mjs';

let settings;
try { settings = settingsFromEnv(); }
catch { console.log('OIDC non configuré : le bureau reste fermé. Aucun compte créé.'); }
const port = Number(process.env.PORT || 4175);
if (!Number.isInteger(port) || port < 1 || port > 65535) throw new Error('Port non valide');
const server = createBureauServer({ settings });
server.listen(port, '127.0.0.1', () => console.log(`Service bureau démarré sur la boucle locale, port ${port}. Publication non effectuée.`));
