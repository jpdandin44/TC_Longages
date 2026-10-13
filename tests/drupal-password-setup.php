<?php

declare(strict_types=1);

use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/drupal/bin/local-bootstrap.php';
$operation = $argv[1] ?? '';
$private = dirname($tclRoot) . '/.local/password-setup-fixture.json';
if ($operation === 'prepare') {
  if (is_file($private) || user_load_by_name('tcl-password-setup-fixture')) { throw new RuntimeException('Fixture collision'); }
  $user = User::create(['name' => 'tcl-password-setup-fixture', 'mail' => '', 'status' => 1, 'roles' => ['tcl_admin']]);
  $user->save();
  Drupal::requestStack()->push(Request::create('http://127.0.0.1:4186/'));
  $url = user_pass_reset_url($user);
  file_put_contents($private, json_encode(['uid' => (int) $user->id(), 'url' => $url], JSON_THROW_ON_ERROR));
}
elseif (in_array($operation, ['refresh', 'verify', 'cleanup'], TRUE)) {
  $fixture = json_decode(file_get_contents($private), TRUE, 32, JSON_THROW_ON_ERROR);
  $user = User::load($fixture['uid']);
  if (!$user || $user->getAccountName() !== 'tcl-password-setup-fixture') { throw new RuntimeException('Fixture identity'); }
  if ($operation === 'refresh') {
    if ($user->getPassword()) { throw new RuntimeException('Fixture already initialized'); }
    Drupal::requestStack()->push(Request::create('http://127.0.0.1:4186/'));
    file_put_contents($private, json_encode(['uid' => (int) $user->id(), 'url' => user_pass_reset_url($user)], JSON_THROW_ON_ERROR));
  }
  elseif ($operation === 'verify') {
    if (!$user->getPassword() || $user->getEmail() !== 'password-setup@example.invalid') { throw new RuntimeException('First password not saved'); }
  }
  else { $user->delete(); unlink($private); }
}
else { throw new RuntimeException('Fixture operation'); }
echo json_encode(['success' => TRUE, 'operation' => $operation, 'localOnly' => TRUE]), "\n";
