<?php

declare(strict_types=1);

// CLI-only helper, uploaded outside webroot. No credential or login URL output.
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\tcl_site\PublicPageEditor;
use Symfony\Component\HttpFoundation\Request;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$stage = 'target';
try {
  [$script, $operation, $account, $root] = $argv;
  if (!preg_match('/^daje[0-9]{4}$/', $account) || realpath($root) !== $root) { throw new RuntimeException('Target'); }
  $private = '/home2/' . $account . '/tcl-preproduction/private';
  $installed = '/home2/' . $account . '/tcl-preproduction/drupal';
  if ($operation === 'restore-check') {
    if (!str_starts_with($root, $private . '/restorations/restore-') || !str_ends_with($root, '/drupal')
        || !preg_match('/^sr_[a-f0-9]{12}_$/', getenv('TCL_SUPPORT_RESTORE_PREFIX') ?: '')) { throw new RuntimeException('Restore scope'); }
  }
  elseif ($root !== $installed || !in_array($operation, ['check', 'initialize', 'mail-test'], TRUE)) { throw new RuntimeException('Installed scope'); }
  if ($operation === 'mail-test') { putenv('TCL_SUPPORT_REAL_PROBE=1'); }
  $stage = 'bootstrap';
  $autoloader = require $root . '/web/autoload.php';
  chdir($root . '/web');
  $request = Request::create('https://preprod.tclongages.fr/');
  $kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
  $kernel->boot();
  $kernel->preHandle($request);
  $database = \Drupal::database();
  $options = $database->getConnectionOptions();
  $expectedPrefix = $operation === 'restore-check' ? getenv('TCL_SUPPORT_RESTORE_PREFIX') : '';
  if (getenv('TCL_ENVIRONMENT') !== 'preproduction' || $options['driver'] !== 'mysql'
      || $options['database'] !== $account . '_tclpreprod' || $options['username'] !== $account . '_tcl'
      || ($options['prefix'] ?? '') !== $expectedPrefix || getenv('TCL_PUBLIC_INDEXING') === '1'
      || !\Drupal::state()->get('system.maintenance_mode')) { throw new RuntimeException('Environment'); }
  if ($operation === 'initialize') {
    $stage = 'additive-install';
    if (!\Drupal::moduleHandler()->moduleExists('node') || !\Drupal::moduleHandler()->moduleExists('field')) { throw new RuntimeException('Dependencies'); }
    \Drupal::service('module_installer')->install(['tcl_support']);
    \Drupal::service('plugin.manager.field.widget')->clearCachedDefinitions();
    PublicPageEditor::initialize();
    \Drupal::keyValue('system.schema')->set('tcl_site', 11001);
    \Drupal::service('router.builder')->rebuild();
    drupal_flush_all_caches();
  }
  $result = ['checkedAt' => gmdate('c'), 'operation' => $operation, 'environment' => getenv('TCL_ENVIRONMENT'),
    'databaseConnectionVerified' => TRUE, 'maintenanceEnabled' => (bool) \Drupal::state()->get('system.maintenance_mode'),
    'drupalVersion' => \Drupal::VERSION, 'phpVersion' => PHP_VERSION,
    'registration' => \Drupal::config('user.settings')->get('register'),
    'supportEnabled' => \Drupal::moduleHandler()->moduleExists('tcl_support'),
    'mailMode' => Settings::get('tcl_support_mail_mode', 'disabled'),
    'publicOpeningExecuted' => FALSE, 'restorationPrefixVerified' => $operation === 'restore-check'];
  if (\Drupal::config('tcl_site.public_pages')->get('pages')) {
    $keys = array_keys(\Drupal::config('tcl_site.public_pages')->get('pages'));
    if ($keys !== PublicPageEditor::PAGES) { throw new RuntimeException('Page map'); }
    foreach ($keys as $key) {
      $node = PublicPageEditor::node($key);
      if (!$node || !$node->hasField(PublicPageEditor::TEXTS)) { throw new RuntimeException('Page content'); }
    }
    $result['editablePages'] = count($keys);
  }
  if ($operation === 'mail-test') {
    $stage = 'one-test-mail';
    if (!\Drupal::moduleHandler()->moduleExists('tcl_support') || Settings::get('tcl_support_mail_mode') !== 'transport') { throw new RuntimeException('Mail scope'); }
    $repository = \Drupal::service('tcl_support.repository');
    $ticket = $repository->create(['submission_id' => hash('sha256', random_bytes(32)), 'category' => 'problem',
      'subject' => 'Recette préproduction V1 — transport support', 'description' => 'Demande fictive de qualification. Aucun adhérent ni renseignement personnel. Vérifier la réception dans la boîte support du club.',
      'email' => '', 'page' => '/club']);
    $notification = \Drupal::service('tcl_support.notifier')->send($ticket['id'], 1);
    if ($notification !== 'submitted_transport') { throw new RuntimeException('Transport refused'); }
    $result['testTicketId'] = $ticket['id'];
    $result['testRecipient'] = 'support@tclongages.fr';
    $result['transportAccepted'] = TRUE;
    $result['inboxReceptionVerified'] = FALSE;
  }
  echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
}
catch (Throwable $error) {
  echo json_encode(['success' => FALSE, 'stage' => $stage, 'errorType' => get_class($error)]), "\n";
  exit(1);
}
