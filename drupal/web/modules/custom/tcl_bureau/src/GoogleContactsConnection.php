<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** Administrative OAuth grant for the isolated preproduction Contacts reader. */
final class GoogleContactsConnection {
  public const CALLBACK = 'https://preprod.tclongages.fr/bureau/contacts-google/retour';
  private const SCOPE = 'openid email https://www.googleapis.com/auth/contacts.readonly';
  public static function allowed(AccountInterface $actor): bool {
    return SeasonCatalogue::recipeEnabled() && \Drupal::service('tcl_bureau.repository')->allowed($actor, 'administer tcl bureau accounts');
  }
  private static function config(): array {
    $s = Settings::get('tcl_member_contacts', []);
    if (!SeasonCatalogue::recipeEnabled() || ($s['enabled'] ?? FALSE) !== TRUE || empty($s['client_id']) || empty($s['client_secret']) || ($s['account'] ?? '') !== 'tclongages@gmail.com' || ($s['label'] ?? '') !== 'Adhérents_2026_2027') { throw new \RuntimeException('Les identifiants Google privés de préproduction restent à configurer.'); }
    return $s;
  }
  private static function fingerprint(array $s): string { return hash('sha256', $s['client_id'] . "\0" . $s['client_secret'] . "\0" . $s['account'] . "\0" . $s['label']); }
  private static function key(): string { return hash('sha256', Settings::getHashSalt() . ':tcl-google-contacts', TRUE); }
  public static function settings(): array {
    $s = Settings::get('tcl_member_contacts', []);
    if (!SeasonCatalogue::recipeEnabled()) { return []; }
    if (!empty($s['refresh_token'])) { return $s; }
    try {
      $s = self::config(); $record = \Drupal::keyValue('tcl_bureau.google_contacts')->get('grant');
      if (!$record || !hash_equals(self::fingerprint($s), $record['fingerprint'])) { return $s; }
      $raw = base64_decode($record['sealed'], TRUE);
      if ($raw === FALSE || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) { return $s; }
      $token = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), self::key());
      if ($token === FALSE) { return $s; }
      return $s + ['refresh_token' => $token, 'group' => $record['group']];
    } catch (\Throwable) { return $s; }
  }
  public static function begin(AccountInterface $actor): string {
    if (!self::allowed($actor) || \Drupal::request()->getSchemeAndHttpHost() !== 'https://preprod.tclongages.fr') { throw new AccessDeniedHttpException(); }
    $s = self::config(); $state = bin2hex(random_bytes(32)); $verifier = bin2hex(random_bytes(32));
    \Drupal::request()->getSession()->set('tcl_google_contacts_pending', ['state' => hash('sha256', $state), 'verifier' => $verifier, 'actor' => (int) $actor->id(), 'expires' => time() + 600, 'fingerprint' => self::fingerprint($s)]);
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query(['client_id' => $s['client_id'], 'redirect_uri' => self::CALLBACK, 'response_type' => 'code', 'scope' => self::SCOPE, 'state' => $state, 'access_type' => 'offline', 'prompt' => 'consent', 'login_hint' => $s['account'], 'code_challenge_method' => 'S256', 'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, TRUE)), '+/', '-_'), '=')], '', '&', PHP_QUERY_RFC3986);
  }
  public static function finish(AccountInterface $actor, string $state, string $code): int {
    if (!self::allowed($actor) || \Drupal::request()->getSchemeAndHttpHost() !== 'https://preprod.tclongages.fr') { throw new AccessDeniedHttpException(); }
    $s = self::config(); $session = \Drupal::request()->getSession(); $pending = $session->get('tcl_google_contacts_pending');
    $session->remove('tcl_google_contacts_pending');
    if (!$pending || $pending['expires'] < time() || $pending['actor'] !== (int) $actor->id() || !hash_equals($pending['state'], hash('sha256', $state)) || !hash_equals($pending['fingerprint'], self::fingerprint($s)) || $code === '' || strlen($code) > 4096) { throw new \RuntimeException('La demande Google a expiré ou ne correspond pas à cette session. Recommencez depuis le Bureau.'); }
    try {
      $client = \Drupal::httpClient(); $options = ['timeout' => 15, 'allow_redirects' => FALSE];
      $token = json_decode((string) $client->post('https://oauth2.googleapis.com/token', $options + ['form_params' => ['client_id' => $s['client_id'], 'client_secret' => $s['client_secret'], 'code' => $code, 'redirect_uri' => self::CALLBACK, 'grant_type' => 'authorization_code', 'code_verifier' => $pending['verifier']]])->getBody(), TRUE, 32, JSON_THROW_ON_ERROR);
      $scopes = explode(' ', $token['scope'] ?? '');
      if (empty($token['refresh_token']) || empty($token['access_token']) || !in_array('https://www.googleapis.com/auth/contacts.readonly', $scopes, TRUE) || array_diff($scopes, ['openid','email','https://www.googleapis.com/auth/userinfo.email','https://www.googleapis.com/auth/contacts.readonly'])) { throw new \RuntimeException(); }
      $options['headers'] = ['Authorization' => 'Bearer ' . $token['access_token']];
      $identity = json_decode((string) $client->get('https://openidconnect.googleapis.com/v1/userinfo', $options)->getBody(), TRUE, 32, JSON_THROW_ON_ERROR);
      if (($identity['email_verified'] ?? FALSE) !== TRUE || strtolower($identity['email'] ?? '') !== $s['account']) { throw new \RuntimeException(); }
      $groups = []; $next = '';
      for ($page = 0; $page < 10; $page++) {
        $result = json_decode((string) $client->get('https://people.googleapis.com/v1/contactGroups?' . http_build_query(['pageSize' => 100, 'groupFields' => 'name,memberCount', 'pageToken' => $next]), $options)->getBody(), TRUE, 64, JSON_THROW_ON_ERROR);
        foreach ($result['contactGroups'] ?? [] as $group) { if (($group['name'] ?? '') === $s['label']) { $groups[] = $group; } }
        $next = $result['nextPageToken'] ?? ''; if ($next === '') { break; }
      }
      if ($next !== '' || count($groups) !== 1 || !preg_match('/^contactGroups\/[a-zA-Z0-9_-]+$/D', $groups[0]['resourceName'] ?? '') || ($groups[0]['memberCount'] ?? 0) > 500) { throw new \RuntimeException(); }
      $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
      \Drupal::keyValue('tcl_bureau.google_contacts')->set('grant', ['fingerprint' => self::fingerprint($s), 'sealed' => base64_encode($nonce . sodium_crypto_secretbox($token['refresh_token'], $nonce, self::key())), 'group' => $groups[0]['resourceName'], 'count' => (int) ($groups[0]['memberCount'] ?? 0), 'at' => time(), 'actor' => (int) $actor->id()]);
      return (int) ($groups[0]['memberCount'] ?? 0);
    } catch (\Throwable) { throw new \RuntimeException('Connexion Google non enregistrée : vérifiez le compte du club, le libellé et les autorisations de lecture. Aucun envoi effectué.'); }
  }
}
