import * as oidc from 'openid-client';

const timeoutSeconds = 10;
const transactionValue = /^[A-Za-z0-9_-]{43,128}$/;

function failure(code, message) {
  return Object.assign(new Error(message), { code });
}
function httpsUrl(value) {
  const url = new URL(value);
  if (url.protocol !== 'https:' || url.username || url.password || url.hash) throw new Error();
  return url;
}
function callback(value) {
  const url = new URL(value);
  const local = url.protocol === 'http:' && ['127.0.0.1', 'localhost', '[::1]'].includes(url.hostname);
  if ((!local && url.protocol !== 'https:') || url.username || url.password || url.search || url.hash) throw new Error();
  return url;
}

// The optional transport is an injected Fetch implementation for isolated tests.
// Configuration files cannot enable insecure HTTP or disable TLS/signature checks.
export async function createOidcClient({ issuer, clientId, clientSecret, redirectUri } = {}, { transport } = {}) {
  let issuerUrl, redirect;
  try {
    if (typeof issuer !== 'string' || issuer !== issuer.trim()
      || typeof clientId !== 'string' || !clientId.trim() || clientId.length > 1024
      || typeof clientSecret !== 'string' || !clientSecret || clientSecret.length > 8192
      || typeof redirectUri !== 'string' || (transport !== undefined && typeof transport !== 'function')) throw new Error();
    issuerUrl = httpsUrl(issuer);
    if (issuerUrl.search || issuerUrl.pathname.includes('/.well-known/')) throw new Error();
    redirect = callback(redirectUri);
  } catch {
    throw failure('OIDC_CONFIGURATION', 'La configuration de connexion est invalide.');
  }

  let config;
  try {
    const discovered = await oidc.discovery(issuerUrl, clientId, clientSecret, undefined, {
      timeout: timeoutSeconds,
      ...(transport ? { [oidc.customFetch]: transport } : {})
    });
    const metadata = discovered.serverMetadata();
    // An exact issuer also prevents accepting multi-tenant issuer substitutions.
    if (metadata.issuer !== issuer || !metadata.response_types_supported?.includes('code')) throw new Error();
    for (const endpoint of ['authorization_endpoint', 'token_endpoint', 'jwks_uri']) httpsUrl(metadata[endpoint]);
    // The login page permits form redirects only to the configured issuer origin.
    if (new URL(metadata.authorization_endpoint).origin !== issuerUrl.origin) throw new Error();
    const methods = metadata.token_endpoint_auth_methods_supported ?? ['client_secret_basic'];
    const authentication = methods.includes('client_secret_basic') ? oidc.ClientSecretBasic(clientSecret)
      : methods.includes('client_secret_post') ? oidc.ClientSecretPost(clientSecret) : null;
    if (!authentication) throw new Error();
    config = new oidc.Configuration(metadata, clientId, { client_secret: clientSecret }, authentication);
    config.timeout = timeoutSeconds;
    if (transport) config[oidc.customFetch] = transport;
    oidc.enableNonRepudiationChecks(config);
  } catch {
    // Never attach provider response bodies, tokens, credentials or URL parameters.
    throw failure('OIDC_DISCOVERY_FAILED', 'Le fournisseur de connexion est indisponible ou incompatible.');
  }

  return Object.freeze({
    async begin() {
      try {
        const state = oidc.randomState();
        const nonce = oidc.randomNonce();
        const verifier = oidc.randomPKCECodeVerifier();
        const url = oidc.buildAuthorizationUrl(config, {
          response_type: 'code',
          redirect_uri: redirect.href,
          scope: 'openid email profile',
          prompt: 'select_account',
          state,
          nonce,
          code_challenge_method: 'S256',
          code_challenge: await oidc.calculatePKCECodeChallenge(verifier)
        });
        return { url: url.href, state, nonce, verifier };
      } catch {
        throw failure('OIDC_START_FAILED', 'La connexion ne peut pas démarrer.');
      }
    },
    async finish(callbackUrl, transaction) {
      try {
        const current = new URL(callbackUrl);
        if (current.origin !== redirect.origin || current.pathname !== redirect.pathname
          || current.username || current.password || current.hash
          || !transaction || !['state', 'nonce', 'verifier'].every(key => typeof transaction[key] === 'string' && transactionValue.test(transaction[key]))) throw new Error();
        const tokens = await oidc.authorizationCodeGrant(config, current, {
          pkceCodeVerifier: transaction.verifier,
          expectedState: transaction.state,
          expectedNonce: transaction.nonce,
          idTokenExpected: true
        });
        const claims = tokens.claims();
        if (!claims || claims.iss !== issuer || typeof claims.sub !== 'string' || !claims.sub || claims.sub.length > 255) throw new Error();
        // Tokens are discarded after verification; return only the identity needed
        // by the server-side allowlist and optional account display.
        return Object.freeze({
          iss: claims.iss,
          sub: claims.sub,
          ...(typeof claims.email === 'string' && claims.email.length <= 320 ? { email: claims.email } : {}),
          email_verified: claims.email_verified === true,
          ...(typeof claims.name === 'string' ? { name: claims.name.slice(0, 200) } : {})
        });
      } catch {
        throw failure('OIDC_AUTHENTICATION_FAILED', 'La connexion n’a pas pu être vérifiée. Recommencez depuis le site du club.');
      }
    }
  });
}
