import http from 'node:http';
import { randomBytes, createHash } from 'node:crypto';
import { readFile } from 'node:fs/promises';
import { createOidcClient } from './oidc-client.mjs';
import { validateSettings, loadAccounts, validateAccounts } from './settings.mjs';

const pages = new Map(['bureau', 'communication', 'inscriptions', 'actualites-bureau'].map(name => [`/${name}.html`, new URL(`../dist/${name}.html`, import.meta.url)]));
const token = () => randomBytes(32).toString('base64url');
const key = value => createHash('sha256').update(value).digest('hex');
const esc = value => String(value).replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
const style = `body{margin:0;background:#f7f7f0;color:#153c32;font:17px/1.6 Arial,sans-serif}main{max-width:760px;margin:7vh auto;padding:24px}h1{font:42px/1.15 Georgia,serif}h2{font:28px Georgia,serif}a{color:inherit}button{border:0;border-radius:6px;background:#153c32;color:white;padding:14px 20px;font:inherit;cursor:pointer}code{overflow-wrap:anywhere}table{width:100%;border-collapse:collapse}td,th{text-align:left;padding:12px;border-bottom:1px solid #ccd4cd}.tcl-session{display:flex;gap:16px;align-items:center;justify-content:space-between;flex-wrap:wrap;padding:12px 24px;background:#153c32;color:white;font:14px/1.5 Arial,sans-serif}.tcl-session a{color:white}.tcl-session form{margin:0}.tcl-session button{border:1px solid #a7bdad;padding:6px 12px}.tcl-session small{display:block}`;
const shell = (title, content) => `<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>${esc(title)} — TC Longages</title><style>${style}table{table-layout:fixed}td,th{overflow-wrap:anywhere;padding:8px}.tcl-session{overflow-wrap:anywhere}.tcl-session>*{min-width:0;max-width:100%}</style></head><body><main><small>TENNIS CLUB DE LONGAGES · ESPACE BUREAU</small><h1>${esc(title)}</h1>${content}</main></body></html>`;
const cookies = header => {
  const result = new Map();
  for (const item of (header || '').split(';')) {
    const split = item.indexOf('=');
    if (split < 0) continue;
    const name = item.slice(0, split).trim(), value = item.slice(split + 1).trim();
    // Duplicate cookies are ambiguous and must not authenticate a request.
    result.set(name, result.has(name) ? '' : value);
  }
  return result;
};

export function createBureauServer({ settings: suppliedSettings, getAccounts = loadAccounts, clientFactory = createOidcClient, now = Date.now } = {}) {
  const settings = suppliedSettings ? validateSettings(suppliedSettings) : null;
  const secure = settings?.localHttp !== true;
  const sessionCookie = secure ? '__Host-tcl-session' : 'tcl-session-local';
  const transactionCookie = secure ? '__Host-tcl-login' : 'tcl-login-local';
  const sessions = new Map(), transactions = new Map(), attempts = new Map();
  const cookie = (name, value, age) => `${name}=${value}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${age}${secure ? '; Secure' : ''}`;
  let clientPromise;
  const client = () => clientPromise ||= clientFactory(settings).catch(error => { clientPromise = null; throw error; });
  const accounts = async () => validateAccounts({ version: 1, issuer: settings.issuer, accounts: await getAccounts(settings.issuer) }, settings.issuer);
  function purge() {
    const time = now();
    for (const [id, session] of sessions) if (session.expires <= time || session.idle <= time) sessions.delete(id);
    for (const [id, transaction] of transactions) if (transaction.expires <= time) transactions.delete(id);
    for (const [id, attempt] of attempts) if (attempt.until <= time) attempts.delete(id);
  }
  const server = http.createServer(async (req, res) => {
    const headers = {
      'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store', 'Pragma': 'no-cache',
      'X-Content-Type-Options': 'nosniff', 'X-Frame-Options': 'DENY', 'Referrer-Policy': 'same-origin',
      'Content-Security-Policy': "default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; img-src data:; connect-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'",
      'X-Robots-Tag': 'noindex, nofollow'
    };
    const send = (status, body = '', extra = {}) => { res.writeHead(status, { ...headers, 'Content-Length': Buffer.byteLength(body), ...extra }); res.end(req.method === 'HEAD' ? undefined : body); };
    const redirect = (location, extra = {}) => send(303, '', { Location: location, ...extra });
    const fail = (status, title, message, extra = {}) => send(status, shell(title, `<p>${esc(message)}</p><p><a href="/login">Retour à la connexion</a></p>`), extra);
    try {
      purge();
      if (!req.url?.startsWith('/') || req.url.startsWith('//') || req.url.length > 8192) return send(400);
      if (!settings) return fail(503, 'Connexion en préparation', 'Le fournisseur de connexion et les deux accès doivent encore être configurés. Aucun accès au bureau n’est ouvert.');
      const origin = new URL(settings.origin);
      if (req.headers.host !== origin.host) return send(403, 'Hôte non autorisé');
      const localPeer = ['127.0.0.1', '::1', '::ffff:127.0.0.1'].includes(req.socket.remoteAddress);
      const secureProxy = settings.trustLocalProxy && localPeer && req.headers['x-forwarded-proto'] === 'https';
      if (!settings.localHttp && !req.socket.encrypted && !secureProxy) return send(426, 'HTTPS requis. Vérifier la terminaison TLS et le proxy local.');
      // Origin comes only from configuration. Forwarded host/protocol never select a redirect destination.
      const url = new URL(req.url, settings.origin);
      const path = url.pathname;
      if (path === '/auth/callback') headers['Referrer-Policy'] = 'no-referrer';
      if (path === '/login' || path === '/auth/login') headers['Content-Security-Policy'] = headers['Content-Security-Policy'].replace("form-action 'self'", `form-action 'self' ${new URL(settings.issuer).origin}`);
      if (url.origin !== settings.origin) return send(400);
      const known = pages.has(path) || ['/', '/index.html', '/login', '/auth/login', '/auth/callback', '/auth/logout', '/admin/acces'].includes(path);
      if (!known) return send(404, 'Page introuvable');
      const expectedMethods = ['/auth/login', '/auth/logout'].includes(path) ? ['POST'] : ['GET', 'HEAD'];
      if (!expectedMethods.includes(req.method)) return send(405, '', { Allow: expectedMethods.join(', ') });
      if (req.method === 'HEAD' && ['/auth/callback'].includes(path)) return send(405, '', { Allow: 'GET' });
      const jar = cookies(req.headers.cookie);
      const sessionId = jar.get(sessionCookie) || '';
      const session = /^[\w-]{43}$/.test(sessionId) ? sessions.get(key(sessionId)) : undefined;
      if (path === '/index.html') return send(200, await readFile(new URL('../release/index.html', import.meta.url)));
      if (path === '/login') {
        // Permit the form's redirect to the configured provider only. Other pages keep form-action self.
        return send(200, shell('Bienvenue au bureau', '<p>Connectez-vous avec votre compte autorisé. Seuls l’administrateur et le compte du bureau peuvent entrer.</p><form action="/auth/login" method="post"><button type="submit">Se connecter</button></form><p><a href="/index.html">Voir le site du club</a></p>'));
      }
      if (path === '/auth/login') {
        if (req.headers.origin !== settings.origin) return send(403, 'Origine non autorisée');
        const peer = req.socket.remoteAddress;
        const attempt = attempts.get(peer) || { count: 0, until: now() + 60000 };
        if (attempt.count >= 10 || transactions.size >= 100 || attempts.size >= 500) return send(429, 'Veuillez patienter.', { 'Retry-After': '60' });
        attempt.count++; attempts.set(peer, attempt);
        if (sessionId) sessions.delete(key(sessionId));
        const previous = jar.get(transactionCookie); if (previous) transactions.delete(key(previous));
        const transaction = await (await client()).begin();
        if (transactions.size >= 100) return send(429);
        const id = token();
        transactions.set(key(id), { ...transaction, expires: now() + 5 * 60000 });
        return redirect(transaction.url, { 'Set-Cookie': [cookie(transactionCookie, id, 300), cookie(sessionCookie, '', 0)] });
      }
      if (path === '/auth/callback') {
        const id = jar.get(transactionCookie) || '';
        const transaction = transactions.get(key(id));
        // Consume before the network exchange so concurrent callbacks cannot reuse the transaction.
        transactions.delete(key(id));
        const clear = { 'Set-Cookie': cookie(transactionCookie, '', 0) };
        if (!transaction || transaction.expires <= now()) return fail(400, 'Connexion expirée', 'Recommencez la connexion depuis ce navigateur.', clear);
        let claims;
        try { claims = await (await client()).finish(url, transaction); }
        catch { return fail(400, 'Connexion non validée', 'La connexion a été refusée ou n’a pas pu être vérifiée. Vous pouvez réessayer.', clear); }
        if (claims.iss !== settings.issuer || typeof claims.sub !== 'string' || !claims.sub || claims.sub.length > 255) return send(403, 'Identité non valide', clear);
        let list;
        try { list = await accounts(); } catch { list = []; }
        const account = list.find(item => item.enabled && item.subject === claims.sub);
        if (!account) return send(403, shell('Compte non autorisé', `<p>Votre connexion a été vérifiée, mais ce compte n’a pas accès au bureau. Transmettez ces deux identifiants au responsable pour préparer son autorisation ; ce ne sont pas des mots de passe.</p><p>Fournisseur : <code>${esc(claims.iss)}</code></p><p>Identifiant du compte : <code>${esc(claims.sub)}</code></p><p><a href="/login">Utiliser un autre compte</a></p>`), clear);
        if (sessions.size >= 100) return fail(503, 'Veuillez patienter', 'Le nombre de connexions temporaires est atteint.', clear);
        const newId = token();
        sessions.set(key(newId), { subject: claims.sub, issuer: claims.iss, csrf: token(), expires: now() + 8 * 3600000, idle: now() + 30 * 60000 });
        return redirect('/bureau.html', { 'Set-Cookie': [cookie(transactionCookie, '', 0), cookie(sessionCookie, newId, 8 * 3600)] });
      }
      if (!session) return redirect('/login', { 'Set-Cookie': cookie(sessionCookie, '', 0) });
      let list;
      try { list = await accounts(); } catch { sessions.delete(key(sessionId)); return fail(503, 'Accès temporairement fermé', 'La configuration des accès est indisponible.', { 'Set-Cookie': cookie(sessionCookie, '', 0) }); }
      const account = list.find(item => item.enabled && item.subject === session.subject && session.issuer === settings.issuer);
      if (!account) { sessions.delete(key(sessionId)); return fail(403, 'Accès retiré', 'Ce compte n’est plus autorisé.', { 'Set-Cookie': cookie(sessionCookie, '', 0) }); }
      if (path === '/auth/logout') {
        if (req.headers.origin !== settings.origin || !req.headers['content-type']?.startsWith('application/x-www-form-urlencoded')) return send(403);
        let body = '';
        for await (const chunk of req) { body += chunk; if (Buffer.byteLength(body) > 1024) return send(413); }
        if (new URLSearchParams(body).get('csrf') !== session.csrf) return send(403);
        sessions.delete(key(sessionId));
        return send(200, shell('Vous êtes déconnecté', '<p>La session du site est fermée. Votre compte chez le fournisseur de connexion peut rester connecté sur cet appareil.</p><p><a href="/login">Retour à la connexion</a></p>'), { 'Set-Cookie': cookie(sessionCookie, '', 0) });
      }
      session.idle = now() + 30 * 60000;
      if (path === '/') return redirect('/bureau.html');
      const bar = `<aside class="tcl-session" aria-label="Session du bureau"><span>${esc(account.label)} · ${account.role === 'admin' ? 'Administrateur' : 'Bureau'}<small>Accès réservé · Prototype</small></span>${account.role === 'admin' ? '<a href="/admin/acces">Les deux accès</a>' : ''}<form method="post" action="/auth/logout"><input type="hidden" name="csrf" value="${session.csrf}"><button type="submit">Se déconnecter</button></form></aside>`;
      if (path === '/admin/acces') {
        if (account.role !== 'admin') return send(403, shell('Accès administrateur requis', '<p>Cette page est réservée au compte administrateur.</p><a href="/bureau.html">Retour au bureau</a>'));
        return send(200, shell('Les deux accès', bar + `<table><tr><th>Compte</th><th>Rôle</th><th>Accès</th></tr>${list.map(item => `<tr><td>${esc(item.label)}</td><td>${esc(item.role)}</td><td>${item.enabled ? 'Autorisé' : 'Fermé'}</td></tr>`).join('')}</table><p>La modification des accès se fait dans la configuration privée du serveur. Les actions du compte partagé sont attribuées collectivement au bureau.</p><p><a href="/bureau.html">Retour au bureau</a></p>`));
      }
      const html = await readFile(pages.get(path), 'utf8');
      return send(200, html.replace('</head>', `<style>${style.slice(style.indexOf('.tcl-session'))}</style></head>`).replace('<body>', '<body>' + bar));
    } catch {
      if (!res.headersSent) fail(503, 'Connexion indisponible', 'Le service de connexion est temporairement indisponible. Aucun accès supplémentaire n’a été ouvert.');
      else res.end();
    }
  });
  server.requestTimeout = 15000;
  server.headersTimeout = 10000;
  return server;
}
