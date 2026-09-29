import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createHash, generateKeyPairSync, sign, randomBytes } from 'node:crypto';
import { createOidcClient } from '../server/oidc-client.mjs';

const issuer = 'https://oidc.example.test';
const clientId = 'club-test-client';
const clientSecret = randomBytes(24).toString('base64url');
const redirectUri = 'http://127.0.0.1:4175/auth/callback';
const keys = generateKeyPairSync('rsa', { modulusLength: 2048 });
const incorrectKeys = generateKeyPairSync('rsa', { modulusLength: 2048 });
const json = value => new Response(JSON.stringify(value), { headers: { 'Content-Type': 'application/json' } });
const base64 = value => Buffer.from(JSON.stringify(value)).toString('base64url');
function jwt(claims, key = keys.privateKey) {
  const input = base64({ alg: 'RS256', kid: 'fixture-key', typ: 'JWT' }) + '.' + base64(claims);
  return input + '.' + sign('RSA-SHA256', Buffer.from(input), key).toString('base64url');
}

async function fixture({ changeClaims = value => value, badSignature = false, metadataChanges = {}, failToken = false } = {}) {
  const calls = [];
  let transaction;
  let exchanged = false;
  const transport = async (url, options) => {
    calls.push({ url, method: options.method });
    assert.ok(options.signal instanceof AbortSignal, 'Every outbound call has a timeout signal');
    assert.equal(new URL(url).protocol, 'https:');
    if (url === issuer + '/.well-known/openid-configuration') return json({
      issuer,
      authorization_endpoint: issuer + '/authorize',
      token_endpoint: issuer + '/token',
      jwks_uri: issuer + '/jwks',
      response_types_supported: ['code'],
      subject_types_supported: ['public'],
      id_token_signing_alg_values_supported: ['RS256'],
      token_endpoint_auth_methods_supported: ['client_secret_basic'],
      code_challenge_methods_supported: ['S256'],
      ...metadataChanges
    });
    if (url === issuer + '/jwks') return json({ keys: [{ ...keys.publicKey.export({ format: 'jwk' }), kid: 'fixture-key', alg: 'RS256', use: 'sig' }] });
    if (url === issuer + '/token') {
      if (failToken) return json({ error: 'invalid_grant', error_description: 'sensitive-provider-detail-' + clientSecret });
      const body = new URLSearchParams(options.body);
      assert.equal(options.method, 'POST');
      const auth = new Headers(options.headers).get('authorization');
      assert.ok(auth?.startsWith('Basic '), 'Confidential client authentication is present');
      const [encodedId, encodedSecret] = Buffer.from(auth.slice(6), 'base64').toString('utf8').split(':');
      assert.ok(decodeURIComponent(encodedId) === clientId && decodeURIComponent(encodedSecret) === clientSecret, 'Client credentials match without printing them');
      assert.equal(body.get('grant_type'), 'authorization_code');
      assert.equal(body.get('redirect_uri'), redirectUri);
      if (exchanged || body.get('code') !== 'single-use-code' || body.get('code_verifier') !== transaction.verifier) {
        return new Response(JSON.stringify({ error: 'invalid_grant' }), { status: 400, headers: { 'Content-Type': 'application/json' } });
      }
      exchanged = true;
      const now = Math.floor(Date.now() / 1000);
      const claims = changeClaims({ iss: issuer, sub: 'member-subject', aud: clientId, iat: now, exp: now + 300, nonce: transaction.nonce,
        email: 'member@example.test', email_verified: true, name: 'Membre fictif', roles: ['untrusted-admin'] });
      return json({ access_token: 'unused-access-token', token_type: 'Bearer', expires_in: 300,
        id_token: jwt(claims, badSignature ? incorrectKeys.privateKey : keys.privateKey) });
    }
    throw new Error('Unexpected fixture endpoint');
  };
  const client = await createOidcClient({ issuer, clientId, clientSecret, redirectUri }, { transport });
  transaction = await client.begin();
  const callbackUrl = new URL(redirectUri);
  callbackUrl.searchParams.set('code', 'single-use-code');
  callbackUrl.searchParams.set('state', transaction.state);
  return { client, transaction, callbackUrl, calls };
}

test('OIDC : flux signé avec PKCE, state, nonce et identité seule en retour', async () => {
  const { client, transaction, callbackUrl, calls } = await fixture();
  const authorization = new URL(transaction.url);
  assert.equal(authorization.origin, issuer);
  assert.equal(authorization.searchParams.get('response_type'), 'code');
  assert.equal(authorization.searchParams.get('redirect_uri'), redirectUri);
  assert.equal(authorization.searchParams.get('scope'), 'openid email profile');
  assert.equal(authorization.searchParams.get('prompt'), 'select_account');
  assert.equal(authorization.searchParams.get('state'), transaction.state);
  assert.equal(authorization.searchParams.get('nonce'), transaction.nonce);
  assert.equal(authorization.searchParams.get('code_challenge_method'), 'S256');
  assert.equal(authorization.searchParams.get('code_challenge'), createHash('sha256').update(transaction.verifier).digest('base64url'));
  const identity = await client.finish(callbackUrl, transaction);
  assert.deepEqual(identity, { iss: issuer, sub: 'member-subject', email: 'member@example.test', email_verified: true, name: 'Membre fictif' });
  assert.ok(calls.some(call => call.url.endsWith('/jwks')), 'Signature verification fetched provider public keys');
  assert.doesNotMatch(JSON.stringify(identity), /token|roles|untrusted-admin/);
  await assert.rejects(client.finish(callbackUrl, transaction), { code: 'OIDC_AUTHENTICATION_FAILED' });
});

test('OIDC : chaque connexion renouvelle state, nonce et vérificateur PKCE', async () => {
  const { client, transaction } = await fixture();
  const next = await client.begin();
  for (const key of ['state', 'nonce', 'verifier']) assert.notEqual(next[key], transaction[key]);
});

test('OIDC : state incorrect et URL de retour altérée refusés avant échange du code', async () => {
  for (const change of [url => url.searchParams.set('state', 'invalid-state'), url => { url.host = 'attacker.example.test'; }, url => { url.pathname = '/elsewhere'; }]) {
    const { client, transaction, callbackUrl, calls } = await fixture();
    change(callbackUrl);
    await assert.rejects(client.finish(callbackUrl, transaction), { code: 'OIDC_AUTHENTICATION_FAILED' });
    assert.ok(!calls.some(call => call.url.endsWith('/token')));
  }
});

test('OIDC : nonce, issuer, audience, expiration et signature invalides refusés', async t => {
  const mutations = {
    nonce: { changeClaims: value => ({ ...value, nonce: 'wrong-nonce' }) },
    issuer: { changeClaims: value => ({ ...value, iss: 'https://other.example.test' }) },
    audience: { changeClaims: value => ({ ...value, aud: 'another-client' }) },
    expiration: { changeClaims: value => ({ ...value, exp: Math.floor(Date.now() / 1000) - 120 }) },
    signature: { badSignature: true }
  };
  for (const [name, options] of Object.entries(mutations)) await t.test(name, async () => {
    const { client, transaction, callbackUrl } = await fixture(options);
    await assert.rejects(client.finish(callbackUrl, transaction), { code: 'OIDC_AUTHENTICATION_FAILED' });
  });
});

test('OIDC : vérificateur PKCE incorrect refusé par le fournisseur de recette', async () => {
  const { client, transaction, callbackUrl } = await fixture();
  await assert.rejects(client.finish(callbackUrl, { ...transaction, verifier: randomBytes(32).toString('base64url') }), { code: 'OIDC_AUTHENTICATION_FAILED' });
});

test('OIDC : erreurs nettoyées sans secret ni réponse fournisseur', async () => {
  const { client, transaction, callbackUrl } = await fixture({ failToken: true });
  await assert.rejects(client.finish(callbackUrl, transaction), error => {
    assert.equal(error.code, 'OIDC_AUTHENTICATION_FAILED');
    assert.equal(error.cause, undefined);
    assert.ok(!error.stack.includes(clientSecret));
    assert.doesNotMatch(error.stack, /sensitive-provider-detail|unused-access-token|single-use-code/);
    return true;
  });
});

test('OIDC : issuer HTTP, callback externe HTTP et métadonnées non sûres refusés', async () => {
  for (const changes of [{ issuer: 'http://oidc.example.test' }, { issuer: issuer + '/.well-known/openid-configuration' }, { redirectUri: 'http://club.example.test/auth/callback' }, { redirectUri: redirectUri + '?returnTo=anything' }]) {
    await assert.rejects(createOidcClient({ issuer, clientId, clientSecret, redirectUri, ...changes }, { transport: async () => { throw new Error('No call expected'); } }), { code: 'OIDC_CONFIGURATION' });
  }
  for (const metadataChanges of [{ issuer: issuer + '/other' }, { token_endpoint: 'http://oidc.example.test/token' }, { jwks_uri: 'http://oidc.example.test/jwks' }, { authorization_endpoint: 'http://oidc.example.test/authorize' }]) {
    await assert.rejects(fixture({ metadataChanges }), { code: 'OIDC_DISCOVERY_FAILED' });
  }
});
