<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** Separate grant, restricted to secondary calendars created by this app. */
final class GoogleCalendarConnection {
  public const NAME = 'TC Longages — Recette V2';
  public const SCOPE = 'https://www.googleapis.com/auth/calendar.app.created';
  public const STORE = 'tcl_bureau.google_calendar';
  public static function allowed(AccountInterface $actor): bool { return GoogleContactsConnection::allowed($actor); }
  private static function config(): array {
    $s = Settings::get('tcl_member_contacts', []);
    if (!SeasonCatalogue::recipeEnabled() || \Drupal::request()->getSchemeAndHttpHost() !== 'https://preprod.tclongages.fr' || ($s['enabled'] ?? FALSE) !== TRUE || empty($s['client_id']) || empty($s['client_secret']) || ($s['account'] ?? '') !== 'tclongages@gmail.com') { throw new \RuntimeException('Connexion Google réservée à la préproduction configurée du club.'); }
    return $s;
  }
  private static function fingerprint(array $s): string { return hash('sha256', $s['client_id'] . "\0" . $s['client_secret'] . "\0calendar-app-created\0" . $s['account']); }
  private static function key(): string { return hash('sha256', Settings::getHashSalt() . ':tcl-google-calendar', TRUE); }
  public static function target(): ?array {
    if (!SeasonCatalogue::recipeEnabled()) { return NULL; }
    return \Drupal::keyValue(self::STORE)->get('calendar');
  }
  public static function connected(): bool { return SeasonCatalogue::recipeEnabled() && (bool) \Drupal::keyValue(self::STORE)->get('grant'); }
  public static function pending(string $state): bool {
    $pending = \Drupal::request()->getSession()->get('tcl_google_calendar_pending');
    return $pending && hash_equals($pending['state'], hash('sha256', $state));
  }
  public static function begin(AccountInterface $actor): string {
    if (!self::allowed($actor)) { throw new AccessDeniedHttpException(); }
    $s = self::config(); $state = bin2hex(random_bytes(32)); $verifier = bin2hex(random_bytes(32));
    \Drupal::request()->getSession()->set('tcl_google_calendar_pending', ['state' => hash('sha256', $state), 'verifier' => $verifier, 'actor' => (int) $actor->id(), 'expires' => time() + 600, 'fingerprint' => self::fingerprint($s)]);
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query(['client_id' => $s['client_id'], 'redirect_uri' => GoogleContactsConnection::CALLBACK, 'response_type' => 'code', 'scope' => 'openid email ' . self::SCOPE, 'state' => $state, 'access_type' => 'offline', 'prompt' => 'consent', 'include_granted_scopes' => 'false', 'login_hint' => $s['account'], 'code_challenge_method' => 'S256', 'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, TRUE)), '+/', '-_'), '=')], '', '&', PHP_QUERY_RFC3986);
  }
  public static function finish(AccountInterface $actor, string $state, string $code): void {
    if (!self::allowed($actor)) { throw new AccessDeniedHttpException(); }
    $s = self::config(); $session = \Drupal::request()->getSession(); $pending = $session->get('tcl_google_calendar_pending'); $session->remove('tcl_google_calendar_pending');
    if (!$pending || $pending['expires'] < time() || $pending['actor'] !== (int) $actor->id() || !hash_equals($pending['state'], hash('sha256', $state)) || !hash_equals($pending['fingerprint'], self::fingerprint($s)) || $code === '' || strlen($code) > 4096) { throw new \RuntimeException('Demande Google expirée ou non reconnue. Reconnectez l’agenda depuis le Bureau.'); }
    try {
      $client = \Drupal::httpClient(); $options = ['timeout' => 15, 'allow_redirects' => FALSE];
      $token = json_decode((string) $client->post('https://oauth2.googleapis.com/token', $options + ['form_params' => ['client_id' => $s['client_id'], 'client_secret' => $s['client_secret'], 'code' => $code, 'redirect_uri' => GoogleContactsConnection::CALLBACK, 'grant_type' => 'authorization_code', 'code_verifier' => $pending['verifier']]])->getBody(), TRUE, 32, JSON_THROW_ON_ERROR);
      $scopes = explode(' ', $token['scope'] ?? '');
      if (empty($token['refresh_token']) || empty($token['access_token']) || !in_array(self::SCOPE, $scopes, TRUE) || array_diff($scopes, ['openid','email','https://www.googleapis.com/auth/userinfo.email',self::SCOPE])) { throw new \RuntimeException(); }
      $identity = json_decode((string) $client->get('https://openidconnect.googleapis.com/v1/userinfo', $options + ['headers' => ['Authorization' => 'Bearer ' . $token['access_token']]])->getBody(), TRUE, 32, JSON_THROW_ON_ERROR);
      if (($identity['email_verified'] ?? FALSE) !== TRUE || strtolower($identity['email'] ?? '') !== $s['account']) { throw new \RuntimeException(); }
      $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
      \Drupal::keyValue(self::STORE)->set('grant', ['fingerprint' => self::fingerprint($s), 'sealed' => base64_encode($nonce . sodium_crypto_secretbox($token['refresh_token'], $nonce, self::key())), 'at' => time()]);
    } catch (\Throwable) { throw new \RuntimeException('Autorisation agenda non enregistrée. Vérifiez le compte du club et le droit limité aux calendriers créés par l’application.'); }
  }
  public static function token(): string {
    $s = self::config(); $grant = \Drupal::keyValue(self::STORE)->get('grant');
    if (!$grant || !hash_equals(self::fingerprint($s), $grant['fingerprint'])) { throw new \RuntimeException('Reconnectez Google Agenda depuis le Bureau.'); }
    try {
      $raw = base64_decode($grant['sealed'], TRUE);
      if ($raw === FALSE || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) { throw new \RuntimeException(); }
      $refresh = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), self::key());
      if (!$refresh) { throw new \RuntimeException(); }
      $token = json_decode((string) \Drupal::httpClient()->post('https://oauth2.googleapis.com/token', ['timeout' => 15, 'allow_redirects' => FALSE, 'form_params' => ['grant_type' => 'refresh_token', 'client_id' => $s['client_id'], 'client_secret' => $s['client_secret'], 'refresh_token' => $refresh]])->getBody(), TRUE, 32, JSON_THROW_ON_ERROR);
      if (empty($token['access_token'])) { throw new \RuntimeException(); }
      return $token['access_token'];
    } catch (\Throwable) { throw new \RuntimeException('Google Agenda inaccessible. Reconnectez son autorisation ; aucun succès d’enregistrement n’est confirmé.'); }
  }
  public static function createCalendar(AccountInterface $actor): void {
    if (!self::allowed($actor)) { throw new AccessDeniedHttpException(); }
    $store = \Drupal::keyValue(self::STORE); $lock = \Drupal::lock();
    if (!$lock->acquire('tcl.google.calendar.create', 60)) { throw new \RuntimeException('Création déjà en cours.'); }
    try {
      if (self::target()) { return; }
      if ($store->get('creation_pending')) { throw new \RuntimeException('Une création Google doit être vérifiée avant de réessayer, pour éviter un doublon.'); }
      $token = self::token(); $marker = 'TC_PREPROD_V2_' . bin2hex(random_bytes(16));
      $store->set('creation_pending', ['marker' => $marker, 'at' => time()]);
      try {
        $calendar = json_decode((string) \Drupal::httpClient()->post('https://www.googleapis.com/calendar/v3/calendars', ['timeout' => 20, 'allow_redirects' => FALSE, 'headers' => ['Authorization' => 'Bearer ' . $token], 'json' => ['summary' => self::NAME, 'timeZone' => 'Europe/Paris', 'description' => 'Calendrier privé de recette TC Longages. ' . $marker]])->getBody(), TRUE, 32, JSON_THROW_ON_ERROR);
        if (!preg_match('/^[a-zA-Z0-9_-]+@group\.calendar\.google\.com$/D', $calendar['id'] ?? '') || ($calendar['summary'] ?? '') !== self::NAME) { throw new \RuntimeException(); }
        $store->set('calendar', ['id' => $calendar['id'], 'marker' => $marker, 'name' => self::NAME, 'at' => time()]); $store->delete('creation_pending');
      } catch (\Throwable) { throw new \RuntimeException('Création Google non confirmée. Vérifiez son résultat avant toute nouvelle tentative ; le calendrier officiel n’a pas été utilisé.'); }
    } finally { $lock->release('tcl.google.calendar.create'); }
  }
}
