<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau_oauth;

/** Adapts CONNECT/Drupal's RS256 contract; no key obtained from the token. */
final class IdTokenVerifier {
  public static function verify(string $jwt, array $settings, string $nonce, ?int $now = NULL): array {
    $now ??= time();
    if (strlen($jwt) > 16384 || count($parts = explode('.', $jwt)) !== 3) {
      throw new \RuntimeException('oauth_token_invalid');
    }
    [$headerPart, $payloadPart, $signaturePart] = $parts;
    $header = json_decode(self::decode($headerPart), TRUE, 16, JSON_THROW_ON_ERROR);
    $claims = json_decode(self::decode($payloadPart), TRUE, 16, JSON_THROW_ON_ERROR);
    if (!is_array($header) || !is_array($claims) || ($header['alg'] ?? NULL) !== 'RS256' || isset($header['crit'])) {
      throw new \RuntimeException('oauth_algorithm_invalid');
    }
    $key = openssl_pkey_get_public(file_get_contents($settings['public_key_path']));
    $details = $key === FALSE ? FALSE : openssl_pkey_get_details($key);
    if (!$details || $details['type'] !== OPENSSL_KEYTYPE_RSA || $details['bits'] < 2048 || openssl_verify($headerPart . '.' . $payloadPart, self::decode($signaturePart), $key, OPENSSL_ALGO_SHA256) !== 1) {
      throw new \RuntimeException('oauth_signature_invalid');
    }
    if (($claims['iss'] ?? NULL) !== $settings['issuer']) {
      throw new \RuntimeException('oauth_issuer_invalid');
    }
    $audience = $claims['aud'] ?? NULL;
    $audiences = is_string($audience) ? [$audience] : (is_array($audience) ? $audience : []);
    if (!in_array($settings['client_id'], $audiences, TRUE) || (count($audiences) > 1 && ($claims['azp'] ?? NULL) !== $settings['client_id']) || (isset($claims['azp']) && $claims['azp'] !== $settings['client_id'])) {
      throw new \RuntimeException('oauth_audience_invalid');
    }
    if (!is_int($claims['exp'] ?? NULL) || $claims['exp'] <= $now || !is_int($claims['iat'] ?? NULL) || $claims['iat'] > $now + 30 || $claims['iat'] > $claims['exp'] || (isset($claims['nbf']) && (!is_int($claims['nbf']) || $claims['nbf'] > $now + 30))) {
      throw new \RuntimeException('oauth_time_invalid');
    }
    // CONNECT observed Simple OAuth 6.1.1 without nonce: an explicit setting is
    // required to accept that provider behavior, never a global silent fallback.
    if (!isset($claims['nonce']) && ($settings['allow_missing_nonce'] ?? FALSE) !== TRUE) {
      throw new \RuntimeException('oauth_nonce_missing');
    }
    if (isset($claims['nonce']) && (!is_string($claims['nonce']) || !hash_equals($nonce, $claims['nonce']))) {
      throw new \RuntimeException('oauth_nonce_invalid');
    }
    if (!is_string($claims['sub'] ?? NULL) || $claims['sub'] === '' || strlen($claims['sub']) > 191) {
      throw new \RuntimeException('oauth_subject_invalid');
    }
    return $claims;
  }

  private static function decode(string $part): string {
    if ($part === '' || !preg_match('/^[A-Za-z0-9_-]+$/D', $part)) {
      throw new \RuntimeException('oauth_encoding_invalid');
    }
    $result = base64_decode(strtr($part, '-_', '+/'), TRUE);
    if ($result === FALSE) {
      throw new \RuntimeException('oauth_encoding_invalid');
    }
    return $result;
  }
}
