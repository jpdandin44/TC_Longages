<?php
declare(strict_types=1);
// Pure signature/claim checks with a generated key, no account or provider access.
require dirname(__DIR__) . '/drupal/web/modules/custom/tcl_bureau_oauth/src/IdTokenVerifier.php';
use Drupal\tcl_bureau_oauth\IdTokenVerifier;
$opensslConfig = dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf';
if (!getenv('OPENSSL_CONF') && is_file($opensslConfig)) { putenv('OPENSSL_CONF=' . $opensslConfig); }
$keyOptions = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
if (is_file($opensslConfig)) { $keyOptions['config'] = $opensslConfig; }
$key = openssl_pkey_new($keyOptions);
if ($key === FALSE) { throw new RuntimeException('Local OpenSSL key generation failed.'); }
$file = tempnam(sys_get_temp_dir(), 'tcl-oauth-key-');
file_put_contents($file, openssl_pkey_get_details($key)['key']);
$config = ['public_key_path' => $file, 'issuer' => 'https://issuer.invalid', 'client_id' => 'tcl-test'];
$now = time();
$base = ['iss' => $config['issuer'], 'aud' => $config['client_id'], 'sub' => 'stable-test-subject', 'iat' => $now, 'exp' => $now + 300, 'nonce' => 'test-nonce'];
function token(array $claims, string $alg = 'RS256'): string {
  global $key;
  $encode = static fn($value) => rtrim(strtr(base64_encode(json_encode($value)), '+/', '-_'), '=');
  $input = $encode(['alg' => $alg]) . '.' . $encode($claims);
  openssl_sign($input, $signature, $key, OPENSSL_ALGO_SHA256);
  return $input . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
}
$checks = 0;
try {
  if (IdTokenVerifier::verify(token($base), $config, 'test-nonce')['sub'] !== $base['sub']) { throw new RuntimeException('Valid token refused.'); } $checks++;
  foreach ([['iss' => 'https://wrong.invalid'], ['aud' => 'wrong'], ['exp' => $now - 1], ['iat' => $now + 100], ['nbf' => $now + 100], ['nonce' => 'wrong'], ['sub' => ''], ['sub' => 1], ['exp' => (string) ($now + 300)], ['aud' => ['tcl-test', 'other']], ['azp' => 'wrong'], ['iat' => $now + 400]] as $changes) {
    $denied = FALSE;
    try { IdTokenVerifier::verify(token(array_replace($base, $changes)), $config, 'test-nonce'); } catch (Throwable) { $denied = TRUE; }
    if (!$denied) { throw new RuntimeException('Invalid claim accepted.'); } $checks++;
  }
  $missing = $base; unset($missing['nonce']);
  try { IdTokenVerifier::verify(token($missing), $config, 'test-nonce'); throw new LogicException('Missing nonce accepted.'); } catch (RuntimeException $error) { if ($error instanceof LogicException) { throw $error; } } $checks++;
  IdTokenVerifier::verify(token($missing), $config + ['allow_missing_nonce' => TRUE], 'test-nonce'); $checks++;
  IdTokenVerifier::verify(token($base + ['azp' => 'tcl-test']), $config, 'test-nonce'); $checks++;
  foreach ([token($base, 'none'), token($base, 'HS256'), 'malformed', substr(token($base), 0, -10) . 'AAAAAAAAAA'] as $bad) {
    $denied = FALSE; try { IdTokenVerifier::verify($bad, $config, 'test-nonce'); } catch (Throwable) { $denied = TRUE; }
    if (!$denied) { throw new RuntimeException('Invalid signature/format accepted.'); } $checks++;
  }
  echo json_encode(['passed' => TRUE, 'checks' => $checks, 'scope' => 'generated local signatures and claims only']) . "\n";
} finally { unlink($file); }
