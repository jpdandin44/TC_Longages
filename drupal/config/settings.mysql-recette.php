<?php

declare(strict_types=1);

// Generated multisite only; never include this file in hosted settings.php.
$tclProject = dirname(__DIR__, 2);
$tclFixture = json_decode(file_get_contents($tclProject . '/.local/mysql-recette/access.json'), TRUE, 512, JSON_THROW_ON_ERROR);
if (($tclFixture['scope'] ?? '') !== 'local-disposable-mysql' || $tclFixture['host'] !== '127.0.0.1' || $tclFixture['port'] !== 33080 || !preg_match('/^tcl_recette_[a-f0-9]{8}$/D', $tclFixture['database'])) {
  throw new RuntimeException('Configuration MariaDB de recette locale invalide.');
}
$databases['default']['default'] = [
  'database' => $tclFixture['database'], 'username' => 'root', 'password' => $tclFixture['password'],
  'host' => '127.0.0.1', 'port' => '33080', 'prefix' => '', 'driver' => 'mysql',
  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
  'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
];
$settings['hash_salt'] = $tclFixture['salt'];
$settings['trusted_host_patterns'] = ['^127\\.0\\.0\\.1$'];
$settings['file_private_path'] = $tclProject . '/.local/mysql-recette/private';
$settings['file_temp_path'] = $tclProject . '/.local/mysql-recette/temp';
$settings['config_sync_directory'] = $tclProject . '/.local/mysql-recette/config-sync';
$settings['update_free_access'] = FALSE;
$settings['rebuild_access'] = FALSE;
$settings['skip_permissions_hardening'] = TRUE;
$config['system.mail']['interface']['default'] = 'tcl_null_mail';
$config['system.logging']['error_level'] = 'hide';
unset($tclFixture);
