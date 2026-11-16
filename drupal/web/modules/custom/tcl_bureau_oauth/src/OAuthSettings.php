<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau_oauth;

use Drupal\Core\Site\Settings;

/** Private, explicit TC client configuration; never exported as Drupal config. */
final class OAuthSettings {
  public static function load(): array {
    $value = Settings::get('tcl_bureau_oauth', []);
    if (!is_array($value) || ($value['enabled'] ?? FALSE) !== TRUE) {
      throw new \RuntimeException('oauth_unconfigured');
    }
    foreach (['issuer', 'authorize_url', 'token_url', 'userinfo_url', 'redirect_uri', 'client_id', 'client_secret', 'public_key_path'] as $key) {
      if (!is_string($value[$key] ?? NULL) || $value[$key] === '') {
        throw new \RuntimeException('oauth_unconfigured');
      }
    }
    $local = ($value['local_fixture'] ?? FALSE) === TRUE || ($value['local_provider'] ?? FALSE) === TRUE;
    foreach (['issuer', 'authorize_url', 'token_url', 'userinfo_url', 'redirect_uri'] as $key) {
      $url = parse_url($value[$key]);
      $loopback = $local && ($url['scheme'] ?? '') === 'http' && ($url['host'] ?? '') === '127.0.0.1';
      if (!$url || (!$loopback && ($url['scheme'] ?? '') !== 'https') || empty($url['host']) || isset($url['user']) || isset($url['pass']) || isset($url['fragment']) || isset($url['query'])) {
        throw new \RuntimeException('oauth_url_invalid');
      }
    }
    $issuer = parse_url($value['issuer']);
    foreach (['authorize_url', 'token_url', 'userinfo_url'] as $key) {
      $url = parse_url($value[$key]);
      if ($url['host'] !== $issuer['host'] || $url['scheme'] !== $issuer['scheme'] || ($url['port'] ?? NULL) !== ($issuer['port'] ?? NULL)) {
        throw new \RuntimeException('oauth_origin_invalid');
      }
    }
    if (strlen($value['client_secret']) < 32 || !is_array($value['identities'] ?? NULL)) {
      throw new \RuntimeException('oauth_unconfigured');
    }
    $subjects = $uids = [];
    foreach ($value['identities'] as $identity) {
      if (!is_array($identity) || ($identity['issuer'] ?? NULL) !== $value['issuer'] || !is_string($identity['subject'] ?? NULL) || $identity['subject'] === '' || strlen($identity['subject']) > 191 || !is_int($identity['uid'] ?? NULL) || $identity['uid'] < 1 || isset($subjects[$identity['subject']]) || isset($uids[$identity['uid']])) {
        throw new \RuntimeException('oauth_identity_configuration_invalid');
      }
      $subjects[$identity['subject']] = $uids[$identity['uid']] = TRUE;
    }
    $keyPath = realpath($value['public_key_path']);
    $webPath = realpath(DRUPAL_ROOT);
    if ($keyPath === FALSE || !is_readable($keyPath) || $webPath === FALSE || str_starts_with(str_replace('\\', '/', $keyPath), str_replace('\\', '/', $webPath) . '/')) {
      throw new \RuntimeException('oauth_key_invalid');
    }
    $value['public_key_path'] = $keyPath;
    $value['scope'] = 'openid profile email';
    return $value;
  }

  public static function identityUid(array $settings, string $subject): ?int {
    foreach ($settings['identities'] as $identity) {
      if (is_array($identity) && ($identity['issuer'] ?? NULL) === $settings['issuer'] && ($identity['subject'] ?? NULL) === $subject && is_int($identity['uid'] ?? NULL) && $identity['uid'] > 0) {
        return $identity['uid'];
      }
    }
    if (ctype_digit($subject) && (int) $subject > 1 && \Drupal::moduleHandler()->moduleExists('tcl_identity')) {
      try { $provider = \Drupal\tcl_identity\IdentitySettings::load(); }
      catch (\RuntimeException $error) { return NULL; }
      foreach (['issuer', 'client_id', 'redirect_uri'] as $key) { if ($provider[$key] !== $settings[$key]) { return NULL; } }
      $entry = \Drupal::keyValue('tcl_bureau.invited_identities')->get($subject);
      $user = \Drupal::entityTypeManager()->getStorage('user')->load((int) $subject);
      if ($entry && $user && $user->isActive() && ($entry['issuer'] ?? '') === $settings['issuer'] && ($entry['client_id'] ?? '') === $settings['client_id'] && ($entry['uuid'] ?? '') === $user->uuid()
        && count(array_diff($user->getRoles(), ['authenticated'])) === 1 && count(array_intersect($user->getRoles(), ['tcl_bureau', 'tcl_capitaine'])) === 1) { return (int) $subject; }
    }
    return NULL;
  }

  public static function fingerprint(array $settings): string {
    return hash('sha256', serialize([$settings['issuer'], $settings['client_id'], $settings['client_secret'], hash_file('sha256', $settings['public_key_path']), $settings['identities']]));
  }
}
