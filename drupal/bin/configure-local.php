<?php

declare(strict_types=1);

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

if (PHP_SAPI !== 'cli') {
  http_response_code(404);
  exit;
}
$root = dirname(__DIR__);
$autoloader = require $root . '/web/autoload.php';
chdir($root . '/web');
$request = Request::create('http://127.0.0.1:4182/');
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->preHandle($request);
\Drupal::service('module_installer')->install(['tcl_site']);
\Drupal::service('theme_installer')->install(['claro']);
\Drupal::configFactory()->getEditable('system.theme')->set('admin', 'claro')->save();
\Drupal::configFactory()->getEditable('system.site')
  ->set('name', 'TC Longages — démonstration locale')
  ->set('page.front', '/club')->save();
\Drupal::configFactory()->getEditable('user.settings')->set('register', 'admin_only')->save();
\Drupal::configFactory()->getEditable('system.cron')->set('threshold.autorun', 0)->save();
\Drupal::configFactory()->getEditable('system.maintenance')
  ->set('message', 'Le site du Tennis Club de Longages est en préparation. Cette démonstration locale est actuellement en maintenance.')->save();
\Drupal::state()->set('system.maintenance_mode', TRUE);
\Drupal::service('router.builder')->rebuild();
$result = [
  'checkedAt' => gmdate('c'),
  'drupalVersion' => \Drupal::VERSION,
  'phpVersion' => PHP_VERSION,
  'maintenanceEnabled' => (bool) \Drupal::state()->get('system.maintenance_mode'),
  'mailPlugin' => get_class(\Drupal::service('plugin.manager.mail')->getInstance(['module' => 'system', 'key' => 'local_check'])),
  'registration' => \Drupal::config('user.settings')->get('register'),
  'scope' => 'local-loopback-only',
  'deployed' => FALSE,
];
file_put_contents(dirname($root) . '/.local/drupal-result.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "Drupal local configuré ; maintenance activée et envoi de courriels neutralisé.\n";
