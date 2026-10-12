<?php

declare(strict_types=1);

use Drupal\user\Entity\User;

require __DIR__ . '/local-bootstrap.php';
\Drupal::service('module_installer')->install(['tcl_bureau_oauth', 'tcl_bureau', 'tcl_support']);
\Drupal::state()->set('system.maintenance_mode', FALSE);
\Drupal::configFactory()->getEditable('tcl_bureau.settings')->set('teams_enabled', FALSE)->save();
$private = dirname($tclRoot) . '/.local';
$oldProvider = is_file($private . '/oauth-fixture-provider.json') ? json_decode(file_get_contents($private . '/oauth-fixture-provider.json'), TRUE, 32, JSON_THROW_ON_ERROR) : [];
$accountsFile = $private . '/bureau-oauth-fixtures.json';
$fixtures = is_file($accountsFile) ? json_decode(file_get_contents($accountsFile), TRUE, 32, JSON_THROW_ON_ERROR) : [];
foreach (['bureau' => 'tcl_bureau', 'captain' => 'tcl_capitaine'] as $name => $role) {
  if (isset($fixtures[$name])) {
    continue;
  }
  if (\Drupal::entityQuery('user')->accessCheck(FALSE)->condition('name', 'oauth-local-' . $name)->execute()) {
    throw new RuntimeException('Local account collision; no account changed.');
  }
  $password = bin2hex(random_bytes(24));
  $user = User::create(['name' => 'oauth-local-' . $name, 'mail' => $name . '@example.invalid', 'status' => 1, 'roles' => [$role]]);
  $user->setPassword($password)->save();
  $fixtures[$name] = ['uid' => (int) $user->id(), 'username' => $user->getAccountName(), 'password' => $password];
}
file_put_contents($accountsFile, json_encode($fixtures, JSON_PRETTY_PRINT) . "\n");
if (!is_file($private . '/oauth-fixture-private.pem')) {
  $opensslConfig = dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf';
  if (!getenv('OPENSSL_CONF') && is_file($opensslConfig)) { putenv('OPENSSL_CONF=' . $opensslConfig); }
  $keyOptions = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
  if (is_file($opensslConfig)) { $keyOptions['config'] = $opensslConfig; }
  $key = openssl_pkey_new($keyOptions);
  if ($key === FALSE) { throw new RuntimeException('Local OpenSSL key generation failed.'); }
  if (!openssl_pkey_export($key, $pem, NULL, $keyOptions)) { throw new RuntimeException('Local OpenSSL key export failed.'); }
  file_put_contents($private . '/oauth-fixture-private.pem', $pem);
  file_put_contents($private . '/oauth-fixture-public.pem', openssl_pkey_get_details($key)['key']);
}
$settings = [
  'enabled' => TRUE, 'local_fixture' => TRUE, 'issuer' => 'http://127.0.0.1:4190',
  'authorize_url' => 'http://127.0.0.1:4190/authorize', 'token_url' => 'http://127.0.0.1:4190/token',
  'userinfo_url' => 'http://127.0.0.1:4190/userinfo', 'redirect_uri' => 'http://127.0.0.1:4186/bureau/oauth/retour',
  'client_id' => 'tc-bureau-local-fixture', 'client_secret' => $oldProvider['client_secret'] ?? bin2hex(random_bytes(32)),
  'public_key_path' => $private . '/oauth-fixture-public.pem', 'allow_missing_nonce' => FALSE,
  'identities' => [
    ['issuer' => 'http://127.0.0.1:4190', 'subject' => 'bureau-fixture', 'uid' => $fixtures['bureau']['uid']],
    ['issuer' => 'http://127.0.0.1:4190', 'subject' => 'captain-fixture', 'uid' => $fixtures['captain']['uid']],
  ],
];
file_put_contents($private . '/bureau-oauth-settings.php', '<?php $settings[\'tcl_bureau_oauth\'] = ' . var_export($settings, TRUE) . ';' . "\n");
file_put_contents($private . '/oauth-fixture-provider.json', json_encode($settings) . "\n");
file_put_contents($private . '/oauth-fixture-mode.json', json_encode(['subject' => 'bureau-fixture', 'mode' => 'valid']) . "\n");
\Drupal::service('router.builder')->rebuild();
drupal_flush_all_caches();
echo "Bureau prepared on private local SQLite with fictitious OAuth identities only. No hosted change.\n";
