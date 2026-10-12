<?php

declare(strict_types=1);

use Drupal\consumers\Entity\Consumer;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

require __DIR__ . '/local-bootstrap.php';
\Drupal::service('module_installer')->install(['tcl_identity', 'tcl_bureau_oauth', 'tcl_bureau']);
\Drupal::moduleHandler()->loadInclude('tcl_bureau', 'install');
tcl_bureau_update_11002();
$private = dirname($tclRoot) . '/.local';
$filename = $private . '/tc-identity-local.json';
$recipe = is_file($filename) ? json_decode(file_get_contents($filename), TRUE, 32, JSON_THROW_ON_ERROR) : [];
if (!$recipe) {
  if (\Drupal::entityQuery('user')->accessCheck(FALSE)->condition('name', 'Admin_TC_Longages_local')->execute() || \Drupal::entityQuery('consumer')->accessCheck(FALSE)->condition('client_id', 'tc-bureau-local-dedicated')->execute()) {
    throw new RuntimeException('Collision de recette locale : aucune identité remplacée.');
  }
  $password = bin2hex(random_bytes(24));
  $user = User::create(['name' => 'Admin_TC_Longages_local', 'mail' => 'admin-tc-local@example.invalid', 'status' => 1, 'roles' => ['tcl_admin']]);
  $user->setPassword($password)->save();
  $recipe = ['uid' => (int) $user->id(), 'username' => $user->getAccountName(), 'password' => $password, 'client_secret' => bin2hex(random_bytes(32)), 'localOnly' => TRUE];
  file_put_contents($filename, json_encode($recipe, JSON_PRETTY_PRINT) . "\n");
}
$user = User::load($recipe['uid']);
if (!$user || $user->getAccountName() !== 'Admin_TC_Longages_local' || !$user->hasRole('tcl_admin')) {
  throw new RuntimeException('Compte local inattendu : aucune modification.');
}
$adminRole = Role::load('tcl_admin');
$adminRole->revokePermission('view all revisions')->revokePermission('revert all revisions')->grantPermission('view tcl_public_page revisions')->grantPermission('revert tcl_public_page revisions')->save();
foreach (['tcl_bureau', 'tcl_capitaine'] as $id) {
  if ($role = Role::load($id)) { $role->grantPermission('grant simple_oauth codes')->save(); }
}
if (!is_file($private . '/tc-identity-private.pem')) {
  $config = dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf';
  $options = ['private_key_bits' => 4096, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
  if (is_file($config)) { $options['config'] = $config; }
  $key = openssl_pkey_new($options);
  if (!$key || !openssl_pkey_export($key, $pem, NULL, $options)) { throw new RuntimeException('Génération locale de clés impossible.'); }
  file_put_contents($private . '/tc-identity-private.pem', $pem);
  file_put_contents($private . '/tc-identity-public.pem', openssl_pkey_get_details($key)['key']);
}
$clientId = 'tc-bureau-local-dedicated';
$clients = \Drupal::entityTypeManager()->getStorage('consumer')->loadByProperties(['client_id' => $clientId]);
if (!$clients) {
  Consumer::create(['client_id' => $clientId, 'label' => 'Bureau TC Longages — recette locale', 'secret' => $recipe['client_secret'], 'confidential' => TRUE, 'pkce' => TRUE, 'grant_types' => ['authorization_code'], 'redirect' => ['http://127.0.0.1:4186/bureau/oauth/retour'], 'automatic_authorization' => TRUE, 'remember_approval' => FALSE, 'third_party' => FALSE, 'is_default' => FALSE, 'access_token_expiration' => 28800])->save();
}
$settings = [
  'tcl_identity' => ['enabled' => TRUE, 'local_recipe' => TRUE, 'issuer' => 'http://127.0.0.1:4191/', 'client_id' => $clientId, 'redirect_uri' => 'http://127.0.0.1:4186/bureau/oauth/retour'],
  'tcl_bureau_oauth' => ['enabled' => TRUE, 'local_provider' => TRUE, 'issuer' => 'http://127.0.0.1:4191/', 'authorize_url' => 'http://127.0.0.1:4191/oauth/authorize', 'token_url' => 'http://127.0.0.1:4191/oauth/token', 'userinfo_url' => 'http://127.0.0.1:4191/oauth/userinfo', 'redirect_uri' => 'http://127.0.0.1:4186/bureau/oauth/retour', 'client_id' => $clientId, 'client_secret' => $recipe['client_secret'], 'public_key_path' => $private . '/tc-identity-public.pem', 'allow_missing_nonce' => FALSE, 'identities' => [['issuer' => 'http://127.0.0.1:4191/', 'subject' => (string) $user->id(), 'uid' => (int) $user->id()]]],
];
$text = '<?php' . "\n";
foreach ($settings as $name => $value) { $text .= '$settings[' . var_export($name, TRUE) . '] = ' . var_export($value, TRUE) . ";\n"; }
$text .= '$config[\'simple_oauth.settings\'][\'private_key\'] = ' . var_export($private . '/tc-identity-private.pem', TRUE) . ";\n";
$text .= '$config[\'simple_oauth.settings\'][\'public_key\'] = ' . var_export($private . '/tc-identity-public.pem', TRUE) . ";\n";
file_put_contents($private . '/bureau-oauth-settings.php', $text);
drupal_flush_all_caches();
echo "Fournisseur Simple OAuth TC installé uniquement en local ; premier Admin de recette, clés privées et aucune donnée réelle.\n";
