<?php

declare(strict_types=1);

use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

// Helpers are CLI-only, outside web/, and refuse any hosted/MySQL database.
if (PHP_SAPI !== 'cli') {
  http_response_code(404);
  exit;
}
$tclRoot = dirname(__DIR__);
$tclAutoloader = require $tclRoot . '/web/autoload.php';
chdir($tclRoot . '/web');
$tclRequest = Request::create('http://127.0.0.1:4182/');
$tclKernel = DrupalKernel::createFromRequest($tclRequest, $tclAutoloader, 'prod');
$tclKernel->boot();
$tclKernel->preHandle($tclRequest);
$tclOptions = Database::getConnection()->getConnectionOptions();
$tclExpected = realpath(dirname($tclRoot) . '/.local/drupal-runtime/site.sqlite');
if ($tclOptions['driver'] !== 'sqlite' || $tclExpected === FALSE || realpath($tclOptions['database']) !== $tclExpected) {
  throw new RuntimeException('Cet outil accepte uniquement la base SQLite privée de ce checkout local.');
}
