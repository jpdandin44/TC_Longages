<?php

declare(strict_types=1);

// This helper is stored in a private attempt directory, never in the web root.
// It invokes Drupal's non-interactive installer, without command-line passwords.
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

if (PHP_SAPI !== 'cli' || PHP_OS_FAMILY !== 'Linux' || PHP_VERSION_ID < 80300) {
  exit(1);
}
ini_set('zend.exception_ignore_args', '1');
ini_set('display_errors', '0');
$stage = 'private-input';
try {
  $profile = json_decode(file_get_contents(__DIR__ . '/profile.json'), TRUE, 512, JSON_THROW_ON_ERROR);
  $root = $profile['composerRoot'];
  $private = dirname(__DIR__);
  if (realpath($private) !== $profile['home'] . '/tcl-preproduction/private'
      || realpath($root) !== $profile['home'] . '/tcl-preproduction/drupal'
      || $profile['environment'] !== 'preproduction'
      || !in_array($argv[1] ?? '', ['install', 'check'], TRUE)) {
    throw new RuntimeException('Scope');
  }
  $operation = $argv[1];
  $lock = fopen($private . '/first-install.lock', 'c');
  chmod($private . '/first-install.lock', 0600);
  if ($lock === FALSE || !flock($lock, LOCK_EX | LOCK_NB)) {
    throw new RuntimeException('Concurrent operation');
  }
  $inputFile = $private . '/hosting-input.json';
  if ($operation === 'install') {
    if (!is_file($inputFile) || is_link($inputFile)) {
      throw new RuntimeException('Private input absent');
    }
    chmod($inputFile, 0600);
    $input = json_decode(file_get_contents($inputFile), TRUE, 512, JSON_THROW_ON_ERROR);
    foreach (['databasePassword', 'adminName', 'adminMail', 'adminPassword'] as $key) {
      if (!isset($input[$key]) || !is_string($input[$key]) || $input[$key] === ''
          || str_contains($input[$key], "\0")) {
        throw new RuntimeException('Invalid private input');
      }
    }
    if (strlen($input['adminPassword']) < 16 || strlen($input['adminName']) > 60
        || !filter_var($input['adminMail'], FILTER_VALIDATE_EMAIL)) {
      throw new RuntimeException('Invalid administrator input');
    }
    $database = ['name' => $profile['database'], 'user' => $profile['databaseUser'],
                 'password' => $input['databasePassword'], 'host' => 'localhost', 'port' => '3306'];
  }
  else {
    $database = json_decode(file_get_contents(__DIR__ . '/database.json'), TRUE, 512, JSON_THROW_ON_ERROR);
  }
  $stage = 'sql-preflight';
  $pdo = new PDO('mysql:host=localhost;port=3306;dbname=' . $profile['database'] . ';charset=utf8mb4',
                 $profile['databaseUser'], $database['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  $sql = $pdo->query('SELECT DATABASE() AS db, CURRENT_USER() AS account, VERSION() AS version,
                     @@character_set_database AS charset, @@collation_database AS collation,
                     @@default_storage_engine AS engine')->fetch(PDO::FETCH_ASSOC);
  if ($sql['db'] !== $profile['database'] || explode('@', $sql['account'])[0] !== $profile['databaseUser']
      || $sql['charset'] !== 'utf8mb4' || $sql['collation'] !== 'utf8mb4_unicode_ci'
      || strcasecmp($sql['engine'], 'InnoDB') !== 0) {
    throw new RuntimeException('SQL scope or format differs');
  }
  $minimum = str_contains($sql['version'], 'MariaDB') ? '10.6.0' : '8.0.0';
  if (version_compare($sql['version'], $minimum, '<')) {
    throw new RuntimeException('SQL version');
  }
  $tables = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?');
  $tables->execute([$profile['database']]);
  if ($operation === 'install' && (int) $tables->fetchColumn() !== 0) {
    throw new RuntimeException('SQL target is not empty');
  }
  $stage = 'private-settings';
  if ($operation === 'install') {
    $settingsFile = $root . '/web/sites/default/settings.php';
    if (file_exists($settingsFile) || is_link($settingsFile)
        || file_exists(__DIR__ . '/database.json') || file_exists(__DIR__ . '/runtime-settings.php')) {
      throw new RuntimeException('Existing configuration');
    }
    $writePrivate = static function (string $path, string $contents): void {
      $file = fopen($path, 'x');
      if ($file === FALSE) {
        throw new RuntimeException('Exclusive private creation failed');
      }
      chmod($path, 0600);
      if (fwrite($file, $contents) !== strlen($contents)) {
        throw new RuntimeException('Private write incomplete');
      }
      fclose($file);
    };
    $database['hashSalt'] = bin2hex(random_bytes(32));
    $writePrivate(__DIR__ . '/database.json', json_encode($database, JSON_THROW_ON_ERROR));
    $runtime = <<<'PHP'
<?php
declare(strict_types=1);
$tclProfile = json_decode(file_get_contents(__DIR__ . '/profile.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$tclDatabase = json_decode(file_get_contents(__DIR__ . '/database.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$tclPrivate = dirname(__DIR__);
foreach (['NAME' => 'name', 'USER' => 'user', 'PASSWORD' => 'password', 'HOST' => 'host', 'PORT' => 'port'] as $key => $field) {
  putenv('TCL_DB_' . $key . '=' . $tclDatabase[$field]);
}
putenv('TCL_HOSTING_ENABLE=1');
putenv('TCL_ENVIRONMENT=preproduction');
putenv('TCL_PUBLIC_INDEXING=0');
putenv('TCL_HASH_SALT=' . $tclDatabase['hashSalt']);
putenv('TCL_PRIVATE_FILES=' . $tclPrivate . '/files');
putenv('TCL_TEMP_FILES=' . $tclPrivate . '/temp');
putenv('TCL_CONFIG_SYNC=' . $tclPrivate . '/config-sync');
require $tclProfile['composerRoot'] . '/config/settings.hosting.example.php';
$settings['trusted_host_patterns'] = ['^' . preg_quote($tclProfile['targetHost'], '/') . '$'];
ini_set('session.cookie_secure', '1');
if (PHP_SAPI === 'cli' && getenv('TCL_FIRST_INSTALLING') === '1') {
  // Core collector exists before tcl_site; no real transport during installation.
  $config['system.mail']['interface']['default'] = 'test_mail_collector';
}
unset($tclProfile, $tclDatabase, $tclPrivate, $key, $field);
PHP;
    $writePrivate(__DIR__ . '/runtime-settings.php', $runtime . "\n");
    $writePrivate($settingsFile, "<?php\nrequire " . var_export(__DIR__ . '/runtime-settings.php', TRUE) . ";\n");
  }
  $_SERVER['HTTP_HOST'] = $profile['targetHost'];
  $_SERVER['SERVER_NAME'] = $profile['targetHost'];
  $_SERVER['SERVER_PORT'] = '443';
  $_SERVER['HTTPS'] = 'on';
  $_SERVER['SCRIPT_NAME'] = '/index.php';
  $_SERVER['SCRIPT_FILENAME'] = $root . '/web/index.php';
  $_SERVER['REQUEST_URI'] = '/';
  $_SERVER['REQUEST_METHOD'] = 'GET';
  $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
  $autoloader = require $root . '/web/autoload.php';
  chdir($root . '/web');
  if ($operation === 'install') {
    $stage = 'drupal-install';
    putenv('TCL_FIRST_INSTALLING=1');
    require_once 'core/includes/install.core.inc';
    install_drupal($autoloader, [
      'interactive' => FALSE,
      'site_path' => 'sites/default',
      'parameters' => ['profile' => 'minimal', 'langcode' => 'en'],
      'forms' => ['install_configure_form' => [
        'site_name' => 'Tennis Club de Longages', 'site_mail' => $input['adminMail'],
        'account' => ['name' => $input['adminName'], 'mail' => $input['adminMail'],
                      'pass' => ['pass1' => $input['adminPassword'], 'pass2' => $input['adminPassword']]],
        'enable_update_status_module' => NULL, 'enable_update_status_emails' => NULL,
      ]],
    ]);
    // Apache is still denying HTTP throughout installation and configuration.
    \Drupal::state()->set('system.maintenance_mode', TRUE);
    $stage = 'drupal-configure';
    \Drupal::service('module_installer')->install(['tcl_site']);
    \Drupal::service('theme_installer')->install(['claro']);
    \Drupal::configFactory()->getEditable('system.theme')->set('admin', 'claro')->save();
    \Drupal::configFactory()->getEditable('system.site')->set('page.front', '/club')->save();
    \Drupal::configFactory()->getEditable('system.mail')->set('interface.default', 'tcl_null_mail')->save();
    \Drupal::configFactory()->getEditable('user.settings')->set('register', 'admin_only')->save();
    \Drupal::configFactory()->getEditable('system.cron')->set('threshold.autorun', 0)->save();
    \Drupal::configFactory()->getEditable('system.maintenance')
      ->set('message', 'Le site du Tennis Club de Longages est en préparation.')->save();
    \Drupal::service('router.builder')->rebuild();
    // The following check runs in a fresh process; do not expose a login link/password.
    echo 'TCL_RESULT:', json_encode(['installationExecuted' => TRUE, 'publicOpeningExecuted' => FALSE]), "\n";
  }
  else {
    $stage = 'drupal-check';
    $request = Request::create('https://' . $profile['targetHost'] . '/');
    $kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
    $kernel->boot();
    $kernel->preHandle($request);
    $mail = \Drupal::service('plugin.manager.mail')->getInstance(['module' => 'system', 'key' => 'hosting_check']);
    $result = [
      'checkedAt' => gmdate('c'), 'drupalVersion' => \Drupal::VERSION, 'phpVersion' => PHP_VERSION,
      'environment' => 'preproduction', 'maintenanceEnabled' => (bool) \Drupal::state()->get('system.maintenance_mode'),
      'tclSiteEnabled' => \Drupal::moduleHandler()->moduleExists('tcl_site'),
      'mailNeutralized' => $mail instanceof \Drupal\tcl_site\Plugin\Mail\NullMail,
      'registration' => \Drupal::config('user.settings')->get('register'),
      'cronAutorunDisabled' => \Drupal::config('system.cron')->get('threshold.autorun') === 0,
      'databaseConnectionVerified' => TRUE, 'sqlVersion' => $sql['version'],
      'installationExecuted' => TRUE, 'publicOpeningExecuted' => FALSE,
    ];
    if (!$result['maintenanceEnabled'] || !$result['tclSiteEnabled'] || !$result['mailNeutralized']
        || $result['registration'] !== 'admin_only' || !$result['cronAutorunDisabled']) {
      throw new RuntimeException('Drupal checks');
    }
    echo 'TCL_RESULT:', json_encode($result, JSON_THROW_ON_ERROR), "\n";
  }
}
catch (Throwable $error) {
  // Deliberately omit the exception message/trace: form and PDO errors may carry inputs.
  echo 'TCL_RESULT:', json_encode(['success' => FALSE, 'failedStage' => $stage, 'errorType' => get_class($error)]), "\n";
  exit(1);
}
