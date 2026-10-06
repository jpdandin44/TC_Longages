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
$request = Request::create('http://127.0.0.1:4183/');
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->preHandle($request);
$options = \Drupal::database()->getConnectionOptions();
$expected = realpath(dirname($root) . '/.local/drupal-runtime/site.sqlite');
if ($options['driver'] !== 'sqlite' || $expected === FALSE || realpath($options['database']) !== $expected
  || \Drupal\Core\Site\Settings::get('tcl_support_mail_mode') !== 'capture') {
  throw new \RuntimeException('Ce script exige la base SQLite privée de ce checkout et le transport capturé.');
}
\Drupal::service('module_installer')->install(['tcl_site', 'tcl_support']);
\Drupal::service('plugin.manager.field.widget')->clearCachedDefinitions();
\Drupal\tcl_site\PublicPageEditor::initialize();
\Drupal::service('theme_installer')->install(['claro']);
\Drupal::configFactory()->getEditable('system.theme')->set('admin', 'claro')->save();
\Drupal::configFactory()->getEditable('system.site')->set('page.front', '/club')
  ->set('name', 'TC Longages — recette support V1 locale')->save();
\Drupal::state()->set('system.maintenance_mode', FALSE);
\Drupal::service('router.builder')->rebuild();
echo "Support V1 installé sur cette seule base locale ; courriels capturés.\n";
