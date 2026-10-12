<?php

declare(strict_types=1);

namespace Drupal\tcl_identity;

use Drupal\Core\Site\Settings;

/** The dedicated provider is disabled until its private settings are qualified. */
final class IdentitySettings {
  public static function load(): array {
    $config = Settings::get('tcl_identity', []);
    if (!is_array($config) || ($config['enabled'] ?? FALSE) !== TRUE) {
      throw new \RuntimeException('identity_unconfigured');
    }
    $issuer = $config['issuer'] ?? '';
    $callback = $config['redirect_uri'] ?? '';
    $local = ($config['local_recipe'] ?? FALSE) === TRUE;
    foreach ([$issuer, $callback] as $value) {
      if (!is_string($value)) { throw new \RuntimeException('identity_url_invalid'); }
      $url = parse_url($value);
      $loopback = $local && ($url['scheme'] ?? '') === 'http' && ($url['host'] ?? '') === '127.0.0.1';
      if (!$url || (!$loopback && ($url['scheme'] ?? '') !== 'https') || empty($url['host']) || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) {
        throw new \RuntimeException('identity_url_invalid');
      }
    }
    if (!is_string($config['client_id'] ?? NULL) || !preg_match('/\A[a-zA-Z0-9_-]{8,128}\z/D', $config['client_id'])) {
      throw new \RuntimeException('identity_client_invalid');
    }
    return $config;
  }
}
