import { readFile } from 'node:fs/promises';

export function validateSettings(input) {
  const origin = new URL(input.origin);
  const issuer = new URL(input.issuer);
  const local = input.localHttp === true && origin.protocol === 'http:' && ['127.0.0.1', 'localhost'].includes(origin.hostname);
  if ((!local && origin.protocol !== 'https:') || origin.origin !== input.origin || origin.username || origin.password) throw new Error('Origine HTTPS requise (HTTP autorisé uniquement en recette locale explicite).');
  if (issuer.protocol !== 'https:' || issuer.username || issuer.password || issuer.search || issuer.hash) throw new Error('Émetteur OIDC HTTPS non valide.');
  if (typeof input.clientId !== 'string' || !input.clientId.trim() || typeof input.clientSecret !== 'string' || !input.clientSecret.trim()) throw new Error('Configuration OIDC incomplète.');
  return Object.freeze({ origin: origin.origin, issuer: input.issuer, clientId: input.clientId, clientSecret: input.clientSecret, redirectUri: origin.origin + '/auth/callback', localHttp: local, trustLocalProxy: input.trustLocalProxy === true });
}

export function settingsFromEnv(env = process.env) {
  return validateSettings({ origin: env.TCL_PUBLIC_BASE_URL, issuer: env.TCL_OIDC_ISSUER, clientId: env.TCL_OIDC_CLIENT_ID, clientSecret: env.TCL_OIDC_CLIENT_SECRET, localHttp: env.TCL_OIDC_LOCAL_HTTP === 'true', trustLocalProxy: env.TCL_OIDC_TRUST_LOCAL_PROXY === 'true' });
}

export function validateAccounts(value, issuer) {
  if (value?.version !== 1 || value.issuer !== issuer || !Array.isArray(value.accounts) || value.accounts.length !== 2) throw new Error('Liste des deux comptes indisponible.');
  const roles = new Set(), subjects = new Set();
  for (const account of value.accounts) {
    if (!account || !['admin', 'bureau'].includes(account.role) || roles.has(account.role) || typeof account.enabled !== 'boolean' || typeof account.label !== 'string' || account.label.length < 1 || account.label.length > 100 || typeof account.subject !== 'string' || account.subject.length > 255 || /[\x00-\x1f\x7f]/.test(account.subject)) throw new Error('Compte non valide.');
    if (account.enabled && !account.subject) throw new Error('Identité requise avant activation.');
    if (account.subject && subjects.has(account.subject)) throw new Error('Les deux comptes doivent être distincts.');
    roles.add(account.role);
    if (account.subject) subjects.add(account.subject);
  }
  return value.accounts.map(account => ({ role: account.role, enabled: account.enabled, label: account.label, subject: account.subject }));
}

export async function loadAccounts(issuer, filename = process.env.TCL_OIDC_ACCOUNTS_FILE || new URL('../.local/oidc-accounts.json', import.meta.url)) {
  const data = await readFile(filename, 'utf8');
  if (Buffer.byteLength(data) > 8192) throw new Error('Configuration trop volumineuse.');
  return validateAccounts(JSON.parse(data), issuer);
}
