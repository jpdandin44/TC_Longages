<?php

declare(strict_types=1);

/**
 * Inactive hosting example. It is not loaded by the local installation.
 * Copy/adapt only after the target, HTTPS, backup and deployment are approved.
 * Supply environment variables through a private hosting configuration; do not
 * put credentials in this example, in Git or in the public document root.
 * Actual database vendor/version remains TBD until the hosting inventory.
 * Drupal 11 requires MySQL >= 8.0 or MariaDB >= 10.6; verify before installation.
 */
if (getenv('TCL_HOSTING_ENABLE') !== '1') {
  throw new \RuntimeException('Configuration hébergée inactive.');
}

$tclRequired = static function (string $name): string {
  $value = getenv($name);
  if (!is_string($value) || trim($value) === '' || str_contains($value, 'TBD') || str_contains($value, 'À compléter')) {
    throw new \RuntimeException('Configuration hébergée manquante : ' . $name);
  }
  return $value;
};

$tclRoot = realpath(dirname(__DIR__));
$tclWebRoot = realpath(dirname(__DIR__) . '/web');
if ($tclRoot === FALSE || $tclWebRoot === FALSE || PHP_OS_FAMILY === 'Windows') {
  throw new \RuntimeException('Ce modèle nécessite une arborescence hébergée Linux qualifiée.');
}
$tclPrivateDirectory = static function (string $name) use ($tclRequired, $tclWebRoot): string {
  $value = $tclRequired($name);
  $resolved = realpath($value);
  if (!str_starts_with($value, '/') || $resolved === FALSE || !is_dir($resolved)
      || $resolved === $tclWebRoot || str_starts_with($resolved, $tclWebRoot . '/')) {
    throw new \RuntimeException('Le répertoire privé doit exister hors racine publique : ' . $name);
  }
  return $resolved;
};
$tclPort = $tclRequired('TCL_DB_PORT');
if (!ctype_digit($tclPort) || (int) $tclPort < 1 || (int) $tclPort > 65535) {
  throw new \RuntimeException('Port de base de données invalide.');
}
$tclSalt = $tclRequired('TCL_HASH_SALT');
if (strlen($tclSalt) < 32) {
  throw new \RuntimeException('Sel de sécurité absent ou trop court.');
}

$databases['default']['default'] = [
  'database' => $tclRequired('TCL_DB_NAME'),
  'username' => $tclRequired('TCL_DB_USER'),
  'password' => $tclRequired('TCL_DB_PASSWORD'),
  'host' => $tclRequired('TCL_DB_HOST'),
  'port' => $tclPort,
  'prefix' => '',
  'driver' => 'mysql',
  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
  'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
];
$settings['hash_salt'] = $tclSalt;
$tclEnvironment = $tclRequired('TCL_ENVIRONMENT');
if (!in_array($tclEnvironment, ['preproduction', 'production'], TRUE)) {
  throw new \RuntimeException('Environnement hébergé invalide.');
}
if ($tclEnvironment === 'preproduction' && getenv('TCL_PUBLIC_INDEXING') === '1') {
  throw new \RuntimeException('La préproduction ne peut pas activer l’indexation publique.');
}
$settings['trusted_host_patterns'] = $tclEnvironment === 'preproduction'
  ? ['^preprod\\.tclongages\\.fr$']
  : ['^tclongages\\.fr$', '^www\\.tclongages\\.fr$'];
$settings['file_private_path'] = $tclPrivateDirectory('TCL_PRIVATE_FILES');
$settings['file_temp_path'] = $tclPrivateDirectory('TCL_TEMP_FILES');
$settings['config_sync_directory'] = $tclPrivateDirectory('TCL_CONFIG_SYNC');
$settings['update_free_access'] = FALSE;
$settings['rebuild_access'] = FALSE;

// tcl_site must be installed. Missing plugin means failure, never mail fallback.
$config['system.mail']['interface']['default'] = 'tcl_null_mail';
$config['user.settings']['register'] = 'admin_only';
$config['system.logging']['error_level'] = 'hide';

// Maintenance stays a native database state so its Drupal form remains usable.
// Enable it during installation, then verify anonymous access before opening.
// HTTPS, secure sessions, server routing, backup and mail activation are separate
// deployment checks; this example neither enables nor attests to them.
unset($tclSalt, $tclRequired, $tclPrivateDirectory, $tclRoot, $tclWebRoot, $tclPort, $tclEnvironment);
