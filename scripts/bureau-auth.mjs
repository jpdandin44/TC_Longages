import { readFile } from 'node:fs/promises';
import { scrypt, randomBytes, timingSafeEqual } from 'node:crypto';
import { promisify } from 'node:util';

const derive = promisify(scrypt);
const options = { N: 32768, r: 8, p: 1, maxmem: 64 * 1024 * 1024 };
const configPath = new URL('../.local/bureau-users.json', import.meta.url);
const dummySalt = randomBytes(16);

// No account is installed by this module. Used by isolated tests and future provisioning.
export async function passwordRecord(password) {
  if (typeof password !== 'string' || password.length < 12 || password.length > 256) throw new Error('Mot de passe de 12 à 256 caractères requis.');
  const salt = randomBytes(16);
  return { salt: salt.toString('hex'), passwordHash: (await derive(password, salt, 64, options)).toString('hex') };
}
export function validateUsers(data) {
  if (!data || data.version !== 1 || !Array.isArray(data.users) || data.users.length > 100) throw new Error('Configuration bureau invalide');
  const logins = new Set();
  for (const user of data.users) {
    if (!user || typeof user.login !== 'string' || !/^[a-zA-Z0-9._@-]{1,100}$/.test(user.login)
      || typeof user.enabled !== 'boolean' || typeof user.role !== 'string'
      || typeof user.salt !== 'string' || !/^[a-f0-9]{32}$/.test(user.salt)
      || typeof user.passwordHash !== 'string' || !/^[a-f0-9]{128}$/.test(user.passwordHash)
      || logins.has(user.login)) throw new Error('Configuration bureau invalide');
    logins.add(user.login);
  }
  return data.users;
}
export async function loadUsers() {
  const raw = await readFile(configPath, 'utf8');
  if (Buffer.byteLength(raw) > 65536) throw new Error('Configuration bureau invalide');
  return validateUsers(JSON.parse(raw));
}
export async function authenticate(header, users) {
  if (typeof header !== 'string' || header.length > 2048 || !/^Basic [A-Za-z0-9+/]+={0,2}$/i.test(header)) return false;
  const encoded = header.slice(6);
  const decoded = Buffer.from(encoded, 'base64');
  if (decoded.toString('base64') !== encoded) return false;
  const value = decoded.toString('utf8');
  const separator = value.indexOf(':');
  if (separator < 1) return false;
  const login = value.slice(0, separator), password = value.slice(separator + 1);
  if (password.length > 256) return false;
  const user = users.find(item => item.login === login);
  const actual = await derive(password, user ? Buffer.from(user.salt, 'hex') : dummySalt, 64, options);
  const expected = user ? Buffer.from(user.passwordHash, 'hex') : Buffer.alloc(64);
  const matches = timingSafeEqual(actual, expected);
  return Boolean(matches && user?.enabled === true && user?.role === 'bureau');
}
