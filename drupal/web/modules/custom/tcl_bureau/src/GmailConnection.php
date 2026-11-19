<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** Separate, encrypted Gmail send-only grant; never reads the mailbox. */
final class GmailConnection {
  public const ACCOUNT = 'tclongages@gmail.com';
  public const SCOPE = 'https://www.googleapis.com/auth/gmail.send';
  public const STORE = 'tcl_bureau.gmail';
  public static function enabled(): bool {
    return (Settings::get('tcl_gmail', [])['enabled'] ?? FALSE) === TRUE
      && in_array(\Drupal::request()->getSchemeAndHttpHost(), ['https://preprod.tclongages.fr', 'https://tclongages.fr'], TRUE);
  }
  public static function allowed(AccountInterface $actor): bool {
    return self::enabled() && GoogleContactsConnection::allowed($actor);
  }
  private static function config(): array {
    $s = Settings::get('tcl_member_contacts', []);
    if (!self::enabled() || empty($s['client_id']) || empty($s['client_secret']) || ($s['account'] ?? '') !== self::ACCOUNT) {
      throw new \RuntimeException('Gmail reste à configurer pour cet environnement.');
    }
    return $s;
  }
  private static function callback(): string { return \Drupal::request()->getSchemeAndHttpHost() . '/bureau/contacts-google/retour'; }
  private static function fingerprint(array $s): string {
    return hash('sha256', self::callback() . "\0gmail-send\0" . $s['client_id'] . "\0" . $s['client_secret'] . "\0" . self::ACCOUNT);
  }
  private static function key(): string { return hash('sha256', Settings::getHashSalt() . ':tcl-gmail', TRUE); }
  public static function connected(): bool {
    try { $s = self::config(); $grant = \Drupal::keyValue(self::STORE)->get('grant'); return $grant && hash_equals(self::fingerprint($s), $grant['fingerprint'] ?? ''); }
    catch (\Throwable) { return FALSE; }
  }
  public static function pending(string $state): bool {
    $pending = \Drupal::request()->getSession()->get('tcl_gmail_pending');
    return $pending && hash_equals($pending['state'], hash('sha256', $state));
  }
  public static function begin(AccountInterface $actor): string {
    if (!self::allowed($actor)) { throw new AccessDeniedHttpException(); }
    $s = self::config(); $state = bin2hex(random_bytes(32)); $verifier = bin2hex(random_bytes(32));
    \Drupal::request()->getSession()->set('tcl_gmail_pending', ['state' => hash('sha256', $state), 'verifier' => $verifier, 'actor' => (int) $actor->id(), 'expires' => time() + 600, 'fingerprint' => self::fingerprint($s)]);
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query(['client_id' => $s['client_id'], 'redirect_uri' => self::callback(), 'response_type' => 'code', 'scope' => 'openid email ' . self::SCOPE, 'state' => $state, 'access_type' => 'offline', 'prompt' => 'consent', 'include_granted_scopes' => 'false', 'login_hint' => self::ACCOUNT, 'code_challenge_method' => 'S256', 'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, TRUE)), '+/', '-_'), '=')], '', '&', PHP_QUERY_RFC3986);
  }
  public static function finish(AccountInterface $actor, string $state, string $code): void {
    if (!self::allowed($actor)) { throw new AccessDeniedHttpException(); }
    $s = self::config(); $session = \Drupal::request()->getSession(); $pending = $session->get('tcl_gmail_pending'); $session->remove('tcl_gmail_pending');
    if (!$pending || $pending['expires'] < time() || $pending['actor'] !== (int) $actor->id() || !hash_equals($pending['state'], hash('sha256', $state)) || !hash_equals($pending['fingerprint'], self::fingerprint($s)) || $code === '' || strlen($code) > 4096) { throw new \RuntimeException('Demande Gmail expirée ou non reconnue. Reconnectez Gmail depuis le Bureau.'); }
    try {
      $client = \Drupal::httpClient(); $options = ['timeout' => 15, 'allow_redirects' => FALSE];
      $token = json_decode((string) $client->post('https://oauth2.googleapis.com/token', $options + ['form_params' => ['client_id' => $s['client_id'], 'client_secret' => $s['client_secret'], 'code' => $code, 'redirect_uri' => self::callback(), 'grant_type' => 'authorization_code', 'code_verifier' => $pending['verifier']]])->getBody(), TRUE, 32, JSON_THROW_ON_ERROR);
      $scopes = explode(' ', $token['scope'] ?? '');
      if (empty($token['refresh_token']) || empty($token['access_token']) || !in_array(self::SCOPE, $scopes, TRUE) || array_diff($scopes, ['openid', 'email', 'https://www.googleapis.com/auth/userinfo.email', self::SCOPE])) { throw new \RuntimeException(); }
      $identity = json_decode((string) $client->get('https://openidconnect.googleapis.com/v1/userinfo', $options + ['headers' => ['Authorization' => 'Bearer ' . $token['access_token']]])->getBody(), TRUE, 32, JSON_THROW_ON_ERROR);
      if (($identity['email_verified'] ?? FALSE) !== TRUE || strtolower($identity['email'] ?? '') !== self::ACCOUNT) { throw new \RuntimeException(); }
      $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
      \Drupal::keyValue(self::STORE)->set('grant', ['fingerprint' => self::fingerprint($s), 'sealed' => base64_encode($nonce . sodium_crypto_secretbox($token['refresh_token'], $nonce, self::key())), 'at' => time(), 'actor' => (int) $actor->id()]);
    } catch (\Throwable) { throw new \RuntimeException('Autorisation Gmail non enregistrée. Vérifiez le compte du club et le droit d’envoi. Aucun courriel envoyé.'); }
  }
  public static function token(): string {
    $s = self::config(); $grant = \Drupal::keyValue(self::STORE)->get('grant');
    if (!self::connected()) { throw new \RuntimeException('Connectez Gmail depuis le Bureau avant tout envoi.'); }
    try {
      $raw = base64_decode($grant['sealed'], TRUE);
      if ($raw === FALSE || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) { throw new \RuntimeException(); }
      $refresh = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), self::key());
      if ($refresh === FALSE) { throw new \RuntimeException(); }
      $result = json_decode((string) \Drupal::httpClient()->post('https://oauth2.googleapis.com/token', ['timeout' => 15, 'allow_redirects' => FALSE, 'form_params' => ['client_id' => $s['client_id'], 'client_secret' => $s['client_secret'], 'refresh_token' => $refresh, 'grant_type' => 'refresh_token']])->getBody(), TRUE, 32, JSON_THROW_ON_ERROR);
      if (empty($result['access_token']) || (isset($result['scope']) && !in_array(self::SCOPE, explode(' ', $result['scope']), TRUE))) { throw new \RuntimeException(); }
      return $result['access_token'];
    } catch (\Throwable) { throw new \RuntimeException('Gmail indisponible ou autorisation expirée. Reconnectez Gmail ; aucun basculement vers un autre expéditeur.'); }
  }
}
