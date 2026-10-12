<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau_oauth;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/** TC adapter of the AVEREO CONNECT/Drupal authorization-code/PKCE contract. */
final class BureauOAuth {
  public function __construct(private RequestStack $requests, private ClientInterface $http, private EntityTypeManagerInterface $entities, private AccountProxyInterface $account) {}

  private function session(): SessionInterface {
    return $this->requests->getCurrentRequest()->getSession();
  }

  public function valid(): bool {
    try {
      $settings = OAuthSettings::load();
      $proof = $this->session()->get('tcl_bureau_oauth_proof');
      $now = time();
      if (!is_array($proof) || ($proof['configuration'] ?? NULL) !== OAuthSettings::fingerprint($settings)) {
        return FALSE;
      }
      if (!is_array($proof) || !$this->account->isAuthenticated() || ($proof['uid'] ?? NULL) !== (int) $this->account->id() || ($proof['issuer'] ?? NULL) !== $settings['issuer'] || OAuthSettings::identityUid($settings, (string) ($proof['subject'] ?? '')) !== $proof['uid'] || !is_int($proof['until'] ?? NULL) || $proof['until'] <= $now || !is_int($proof['last'] ?? NULL) || $proof['last'] < $now - 1800) {
        return FALSE;
      }
      $storage = $this->entities->getStorage('user');
      $storage->resetCache([$proof['uid']]);
      $user = $storage->load($proof['uid']);
      $this->entities->getStorage('user_role')->resetCache();
      if (!$user || !$user->isActive() || !$user->hasPermission('access tcl bureau')) {
        return FALSE;
      }
      $proof['last'] = $now;
      $this->session()->set('tcl_bureau_oauth_proof', $proof);
      return TRUE;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  public function begin(): string {
    $settings = OAuthSettings::load();
    $this->assertCallback($settings);
    $session = $this->session();
    $state = bin2hex(random_bytes(32));
    $nonce = bin2hex(random_bytes(32));
    $verifier = self::base64url(random_bytes(64));
    $session->remove('tcl_bureau_oauth_proof');
    $session->set('tcl_bureau_oauth_transaction', ['state' => $state, 'nonce' => $nonce, 'verifier' => $verifier, 'created' => time()]);
    return $settings['authorize_url'] . '?' . http_build_query([
      'response_type' => 'code', 'client_id' => $settings['client_id'], 'redirect_uri' => $settings['redirect_uri'],
      'scope' => $settings['scope'], 'state' => $state, 'nonce' => $nonce,
      'code_challenge' => self::base64url(hash('sha256', $verifier, TRUE)), 'code_challenge_method' => 'S256',
    ], '', '&', PHP_QUERY_RFC3986);
  }

  public function complete(): void {
    $request = $this->requests->getCurrentRequest();
    $session = $this->session();
    $transaction = $session->remove('tcl_bureau_oauth_transaction');
    $session->remove('tcl_bureau_oauth_proof');
    $state = $request->query->get('state');
    $code = $request->query->get('code');
    if (!is_array($transaction) || !is_string($state) || strlen($state) > 256 || !hash_equals($transaction['state'], $state) || $transaction['created'] < time() - 300 || $request->query->has('error') || !is_string($code) || $code === '' || strlen($code) > 2048) {
      throw new \RuntimeException('oauth_transaction_invalid');
    }
    $settings = OAuthSettings::load();
    $this->assertCallback($settings);
    $tokens = $this->json($settings['token_url'], ['form_params' => [
      'grant_type' => 'authorization_code', 'client_id' => $settings['client_id'], 'client_secret' => $settings['client_secret'],
      'redirect_uri' => $settings['redirect_uri'], 'code' => $code, 'code_verifier' => $transaction['verifier'],
    ]]);
    if (!is_string($tokens['access_token'] ?? NULL) || $tokens['access_token'] === '' || strlen($tokens['access_token']) > 8192 || !is_string($tokens['id_token'] ?? NULL) || strcasecmp((string) ($tokens['token_type'] ?? ''), 'Bearer') !== 0) {
      throw new \RuntimeException('oauth_token_invalid');
    }
    $claims = IdTokenVerifier::verify($tokens['id_token'], $settings, $transaction['nonce']);
    $profile = $this->json($settings['userinfo_url'], ['headers' => ['Authorization' => 'Bearer ' . $tokens['access_token']]]);
    if (!is_string($profile['sub'] ?? NULL) || !hash_equals($claims['sub'], $profile['sub'])) {
      throw new \RuntimeException('oauth_subject_mismatch');
    }
    $uid = OAuthSettings::identityUid($settings, $claims['sub']);
    if ($uid === NULL) {
      throw new \RuntimeException('oauth_identity_unapproved');
    }
    $storage = $this->entities->getStorage('user');
    $storage->resetCache([$uid]);
    $this->entities->getStorage('user_role')->resetCache();
    $user = $storage->load($uid);
    if (!$user || !$user->isActive() || !$user->hasPermission('access tcl bureau')) {
      throw new \RuntimeException('oauth_identity_unapproved');
    }
    // Native finalization rotates the session id and applies Drupal login hooks.
    user_login_finalize($user);
    $session->set('tcl_bureau_oauth_proof', ['uid' => $uid, 'issuer' => $settings['issuer'], 'subject' => $claims['sub'], 'configuration' => OAuthSettings::fingerprint($settings), 'until' => min(time() + 28800, $claims['exp']), 'last' => time()]);
    // Access/ID tokens are discarded; no tokens reach the browser or Drupal config.
  }

  private function assertCallback(array $settings): void {
    $request = $this->requests->getCurrentRequest();
    if ($settings['redirect_uri'] !== $request->getSchemeAndHttpHost() . $request->getBaseUrl() . '/bureau/oauth/retour' || ((($settings['local_fixture'] ?? FALSE) === TRUE || ($settings['local_provider'] ?? FALSE) === TRUE) && $request->getHost() !== '127.0.0.1')) {
      throw new \RuntimeException('oauth_callback_invalid');
    }
  }

  private function json(string $url, array $options): array {
    $response = $this->http->request(isset($options['form_params']) ? 'POST' : 'GET', $url, $options + [
      'headers' => ['Accept' => 'application/json'], 'allow_redirects' => FALSE, 'http_errors' => FALSE,
      'verify' => TRUE, 'connect_timeout' => 5, 'timeout' => 15,
    ]);
    if ($response->getStatusCode() !== 200) {
      throw new \RuntimeException('oauth_provider_unavailable');
    }
    $body = $response->getBody()->read(65537);
    if (strlen($body) > 65536 || !is_array($decoded = json_decode($body, TRUE, 32, JSON_THROW_ON_ERROR))) {
      throw new \RuntimeException('oauth_response_invalid');
    }
    return $decoded;
  }

  private static function base64url(string $value): string {
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
  }
}
