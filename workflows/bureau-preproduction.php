<?php

declare(strict_types=1);

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\tcl_site\PublicPageEditor;
use Drupal\tcl_site\Controller\ClubPageController;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

function writeInitialisation(User $admin, string $identity): void {
  // The native entry handles existing sessions before the one-time login form.
  $url = user_pass_reset_url($admin);
  $temporary = $identity . '/initialisation-admin.html.new';
  $stream = fopen($temporary, 'x');
  if (!$stream) { throw new RuntimeException('Initialisation file collision'); }
  try {
    chmod($temporary, 0600);
    $html = '<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="referrer" content="no-referrer"><title>Initialisation Admin TC Longages</title><h1>Admin TC Longages</h1><p>Lien privé à usage unique. Franchir la protection du site avec l’accès bêta habituel. Dans Drupal, cliquer ensuite sur Se connecter pour définir le mot de passe. Si un autre compte est déjà connecté, le déconnecter avant de reprendre ce lien.</p><p>Choisir une adresse personnelle distincte de celle du club, déjà utilisée. Saisir, confirmer et enregistrer personnellement le nouveau mot de passe.</p><p><a rel="noreferrer" href="' . htmlspecialchars($url, ENT_QUOTES) . '">Définir mon mot de passe</a></p></html>';
    if (fwrite($stream, $html) !== strlen($html)) { throw new RuntimeException('Initialisation file write'); }
    fclose($stream); $stream = NULL;
    if (!rename($temporary, $identity . '/initialisation-admin.html')) { throw new RuntimeException('Initialisation file promotion'); }
  } finally {
    if (is_resource($stream)) { fclose($stream); }
    if (is_file($temporary)) { unlink($temporary); }
  }
}

// Private CLI operator, never a web endpoint. No credential appears on stdout.
$phase = 'guard';
try {
  $active = '/home2/daje5127/tcl-preproduction/drupal';
  $private = '/home2/daje5127/tcl-preproduction/private';
  $stage = $private . '/bureau-702d4e5b697d';
  $operation = $argv[1] ?? '';
  $root = $argv[2] ?? '';
  $shadow = $root !== $active;
  if (PHP_SAPI !== 'cli' || realpath($root) !== $root || is_link($root) || (!in_array($operation, ['inspect', 'install', 'activate', 'rehearse', 'provision', 'provisioncheck', 'initialise', 'verify'], TRUE)) || ($shadow && !preg_match('#^' . preg_quote($private, '#') . '/restorations/restore-[a-zA-Z0-9_-]+/drupal$#D', $root))) {
    throw new RuntimeException('Operator scope');
  }
  $loader = require $root . '/web/autoload.php';
  chdir($root . '/web');
  $request = Request::create('https://preprod.tclongages.fr/');
  $kernel = DrupalKernel::createFromRequest($request, $loader, 'prod');
  $kernel->boot(); $kernel->preHandle($request);
  $options = Drupal::database()->getConnectionOptions();
  if ($options['database'] !== 'daje5127_tclpreprod' || $options['username'] !== 'daje5127_tcl' || ($options['prefix'] ?? '') !== ($shadow ? 'sr_702d4e5b697d_' : '') || getenv('TCL_ENVIRONMENT') !== 'preproduction' || getenv('TCL_PUBLIC_INDEXING') === '1' || Drupal::VERSION !== '11.4.8') {
    throw new RuntimeException('Database or environment scope');
  }
  $phase = $operation;
  $editorial = [];
  foreach (PublicPageEditor::PAGES as $name) {
    $node = PublicPageEditor::node($name);
    if (!$node || !$node->isPublished()) { throw new RuntimeException('Missing public content'); }
    $editorial[$name] = [$node->id(), $node->getRevisionId(), $node->label(), $node->get(PublicPageEditor::FIELD)->value, $node->get(PublicPageEditor::TEXTS)->value];
  }
  $result = ['success' => TRUE, 'operation' => $operation, 'observedAt' => gmdate('c'), 'drupalVersion' => Drupal::VERSION, 'phpVersion' => PHP_VERSION, 'shadow' => $shadow, 'maintenance' => (bool) Drupal::state()->get('system.maintenance_mode'), 'editorialDigest' => hash('sha256', json_encode($editorial, JSON_THROW_ON_ERROR)), 'pages' => 7, 'mailSent' => FALSE, 'productionWritten' => FALSE];
  if ($operation === 'inspect') {
    $result['modules'] = array_values(array_filter(array_keys(Drupal::moduleHandler()->getModuleList()), static fn($name) => str_starts_with($name, 'tcl_') || in_array($name, ['simple_oauth', 'consumers'], TRUE)));
    $result['adminAlreadyExists'] = (bool) user_load_by_name('Admin_TC_Longages');
    $result['users'] = (int) Drupal::entityQuery('user')->accessCheck(FALSE)->count()->execute();
  }
  if (in_array($operation, ['install', 'activate', 'rehearse'], TRUE)) {
    if (($operation === 'activate') === $shadow) { throw new RuntimeException('Installation scope'); }
    Drupal::service('module_installer')->install(['tcl_identity', 'tcl_bureau_oauth', 'tcl_bureau']);
    Drupal::moduleHandler()->loadInclude('tcl_bureau', 'install');
    tcl_bureau_update_11002();
    drupal_flush_all_caches();
  }
  if ($operation === 'rehearse') {
    if (user_load_by_name('tcl-hosting-admin-fixture')) { throw new RuntimeException('Rehearsal collision'); }
    $user = User::create(['name' => 'tcl-hosting-admin-fixture', 'mail' => 'hosting-fixture@example.invalid', 'status' => 1, 'roles' => ['tcl_admin']]);
    $user->setPassword(bin2hex(random_bytes(32)))->save();
    if (!$user->hasPermission('administer tcl bureau accounts') || !$user->hasPermission('edit any tcl_public_page content') || Drupal\user\Entity\Role::load('tcl_admin')->isAdmin()) { throw new RuntimeException('Admin permissions'); }
    $original = Drupal::service('tcl_bureau_oauth.connection');
    if ($original->valid()) { throw new RuntimeException('Native login bypass'); }
    // This double is confined to this CLI-only, non-public, prefixed copy.
    Drupal::getContainer()->set('tcl_bureau_oauth.connection', new class { public function valid(): bool { return TRUE; } });
    $repository = Drupal::service('tcl_bureau.registrations');
    $data = ['profile' => 'adult', 'first_name' => 'Recette', 'last_name' => 'Hébergement fictif', 'birth_date' => '1990-01-01', 'season' => '2026-2027', 'email' => 'fixture@example.invalid', 'phone' => '', 'status' => 'received', 'training' => 'unknown', 'previous_licence' => 'unknown', 'notes' => 'Dossier fictif de copie privée.'];
    $submission = bin2hex(random_bytes(16));
    $id = $repository->save($user, NULL, $data, $submission, 0);
    $data['status'] = 'checked';
    $repository->save($user, $id, $data, $submission, 1);
    if ($repository->get($user, $id)['values']['status'] !== 'checked') { throw new RuntimeException('Registration persistence'); }
    Drupal::getContainer()->set('tcl_bureau_oauth.connection', $original);
    $result['adminRoleAndRegistrationVerified'] = TRUE;
    $result['oauthHostedLoginVerified'] = FALSE;
  }
  if ($operation === 'provision') {
    if ($shadow || user_load_by_name('Admin_TC_Longages') || !Drupal::moduleHandler()->moduleExists('tcl_bureau')) { throw new RuntimeException('Admin provisioning collision'); }
    if (is_dir($private . '/tc-identity')) { throw new RuntimeException('Private identity directory exists'); }
    mkdir($private . '/tc-identity', 0700);
    $identity = $private . '/tc-identity';
    $key = openssl_pkey_new(['private_key_bits' => 4096, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    if (!$key || !openssl_pkey_export($key, $pem)) { throw new RuntimeException('Private key generation'); }
    file_put_contents($identity . '/private.pem', $pem); chmod($identity . '/private.pem', 0600);
    file_put_contents($identity . '/public.pem', openssl_pkey_get_details($key)['key']); chmod($identity . '/public.pem', 0600);
    $admin = User::create(['name' => 'Admin_TC_Longages', 'mail' => '', 'status' => 1, 'roles' => ['tcl_admin']]);
    // Empty password means no password login until the responsible user sets it.
    // The agent never supplies or submits the real Admin's chosen credential.
    $admin->save();
    $secret = bin2hex(random_bytes(32)); $clientId = 'tc-bureau-preproduction';
    if (Drupal::entityQuery('consumer')->accessCheck(FALSE)->condition('client_id', $clientId)->execute()) { throw new RuntimeException('Client collision'); }
    Drupal\consumers\Entity\Consumer::create(['client_id' => $clientId, 'label' => 'Bureau TC Longages', 'secret' => $secret, 'confidential' => TRUE, 'pkce' => TRUE, 'grant_types' => ['authorization_code'], 'redirect' => ['https://preprod.tclongages.fr/bureau/oauth/retour'], 'automatic_authorization' => TRUE, 'remember_approval' => FALSE, 'third_party' => FALSE, 'is_default' => FALSE, 'access_token_expiration' => 28800])->save();
    $issuer = 'https://preprod.tclongages.fr/'; $callback = $issuer . 'bureau/oauth/retour';
    $settings = ['tcl_identity' => ['enabled' => TRUE, 'issuer' => $issuer, 'client_id' => $clientId, 'redirect_uri' => $callback], 'tcl_bureau_oauth' => ['enabled' => TRUE, 'issuer' => $issuer, 'authorize_url' => $issuer . 'oauth/authorize', 'token_url' => $issuer . 'oauth/token', 'userinfo_url' => $issuer . 'oauth/userinfo', 'redirect_uri' => $callback, 'client_id' => $clientId, 'client_secret' => $secret, 'public_key_path' => $identity . '/public.pem', 'allow_missing_nonce' => FALSE, 'identities' => [['issuer' => $issuer, 'subject' => (string) $admin->id(), 'uid' => (int) $admin->id()]]]];
    $text = "<?php\n";
    foreach ($settings as $name => $value) { $text .= '$settings[' . var_export($name, TRUE) . '] = ' . var_export($value, TRUE) . ";\n"; }
    foreach (['private_key' => 'private.pem', 'public_key' => 'public.pem'] as $name => $filename) { $text .= '$config[\'simple_oauth.settings\'][' . var_export($name, TRUE) . '] = ' . var_export($identity . '/' . $filename, TRUE) . ";\n"; }
    file_put_contents($identity . '/settings.php', $text); chmod($identity . '/settings.php', 0600);
    writeInitialisation($admin, $identity);
    $result['adminUid'] = (int) $admin->id(); $result['adminName'] = 'Admin_TC_Longages';
    $result['passwordSetByAgent'] = FALSE; $result['userPasswordSetupPending'] = TRUE;
  }
  if (in_array($operation, ['provisioncheck', 'initialise'], TRUE)) {
    if ($shadow || !is_dir($private . '/tc-identity') || is_link($private . '/tc-identity') || (fileperms($private . '/tc-identity') & 0777) !== 0700) { throw new RuntimeException('Provisioning scope'); }
    $identity = $private . '/tc-identity';
    foreach (['settings.php', 'private.pem', 'public.pem', 'initialisation-admin.html'] as $name) {
      if (!is_file($identity . '/' . $name) || is_link($identity . '/' . $name) || (fileperms($identity . '/' . $name) & 0777) !== 0600) { throw new RuntimeException('Private provisioning files'); }
    }
    $settings = $config = [];
    require $identity . '/settings.php';
    $oauth = $settings['tcl_bureau_oauth']; $provider = $settings['tcl_identity'];
    $admin = user_load_by_name('Admin_TC_Longages');
    if (!$admin || !$admin->isActive() || $admin->getPassword() || !$admin->hasRole('tcl_admin') || !$admin->hasPermission('administer tcl bureau accounts') || Drupal\user\Entity\Role::load('tcl_admin')->isAdmin() || $provider['client_id'] !== 'tc-bureau-preproduction' || $provider['issuer'] !== 'https://preprod.tclongages.fr/' || $oauth['identities'] !== [['issuer' => $provider['issuer'], 'subject' => (string) $admin->id(), 'uid' => (int) $admin->id()]] || $oauth['allow_missing_nonce'] !== FALSE) { throw new RuntimeException('Provisioned identity association'); }
    $clients = Drupal::entityTypeManager()->getStorage('consumer')->loadByProperties(['client_id' => $provider['client_id']]);
    if (count($clients) !== 1) { throw new RuntimeException('Provisioned client'); }
    $client = reset($clients);
    if (!$client->get('confidential')->value || !$client->get('pkce')->value || !Drupal::service('password')->check($oauth['client_secret'], $client->get('secret')->value) || $client->get('redirect')->value !== 'https://preprod.tclongages.fr/bureau/oauth/retour' || array_column($client->get('grant_types')->getValue(), 'value') !== ['authorization_code']) { throw new RuntimeException('Provisioned client binding'); }
    $key = openssl_pkey_get_private(file_get_contents($identity . '/private.pem'));
    if (!$key || openssl_pkey_get_details($key)['key'] !== file_get_contents($identity . '/public.pem')) { throw new RuntimeException('Provisioned key pair'); }
    $result['adminUid'] = (int) $admin->id(); $result['adminName'] = 'Admin_TC_Longages';
    $result['privateProvisioningVerified'] = TRUE; $result['passwordSetByAgent'] = FALSE; $result['userPasswordSetupPending'] = TRUE;
    if ($operation === 'initialise') {
      $receipt = json_decode(file_get_contents($stage . '/apply-receipt.json'), TRUE, 512, JSON_THROW_ON_ERROR);
      if ($receipt['artifactSha256'] !== '702d4e5b697dcae2b388324e0011ad06ef0a30858d8732fea667d332cd9d7431' || $receipt['provisioning']['adminUid'] !== (int) $admin->id()) { throw new RuntimeException('Initialisation candidate binding'); }
      writeInitialisation($admin, $identity);
      $result['nativeResetLinkRefreshed'] = TRUE;
      $result['accountKeysAndClientPreserved'] = TRUE;
    }
  }
  if (in_array($operation, ['rehearse', 'verify'], TRUE)) {
    $switch = Drupal::service('account_switcher');
    $switch->switchTo(new Drupal\Core\Session\AnonymousUserSession());
    try {
      foreach (PublicPageEditor::PAGES as $name) {
        $body = (new ClubPageController())->page($name)->getContent();
        if (substr_count($body, '>Bureau</a>') !== 1 || str_contains($body, 'tcl-edit-bar') || ($name === 'calendrier' && !str_contains($body, 'mode=MONTH')) || ($name === 'index' && !str_contains($body, 'data:image/png;base64,')) || ($name === 'contact' && (!str_contains($body, 'tcl-club-contact') || !str_contains($body, 'Écrire avec Gmail')))) { throw new RuntimeException('Public page preservation'); }
      }
    } finally { $switch->switchBack(); }
    $result['menuAndV1Preserved'] = TRUE;
    if (!$shadow) {
      $admin = user_load_by_name('Admin_TC_Longages');
      $connection = Drupal::service('tcl_bureau_oauth.connection');
      Drupal\tcl_bureau_oauth\OAuthSettings::load();
      if (!$admin || !$admin->isActive() || !$admin->hasRole('tcl_admin') || !$admin->hasPermission('administer tcl bureau accounts') || Drupal\user\Entity\Role::load('tcl_admin')->isAdmin() || $connection->valid()) { throw new RuntimeException('Active OAuth or Admin verification'); }
      $result['adminUid'] = (int) $admin->id();
      $result['adminHasPassword'] = (bool) $admin->getPassword();
      $result['oauthConfigured'] = TRUE;
      $result['oauthHostedLoginVerified'] = FALSE;
    }
  }
  echo json_encode($result, JSON_THROW_ON_ERROR), "\n";
} catch (Throwable $error) {
  echo json_encode(['success' => FALSE, 'phase' => $phase, 'errorType' => get_class($error), 'errorFile' => basename($error->getFile()), 'errorLine' => $error->getLine()]), "\n";
  exit(1);
}
