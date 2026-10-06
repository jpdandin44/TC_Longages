<?php

declare(strict_types=1);

// This file is for loopback evaluation only. It is not a hosting configuration.
$tclProject = dirname(__DIR__, 2);
$tclRuntime = $tclProject . '/.local/drupal-runtime';
$tclSecrets = json_decode(file_get_contents($tclProject . '/.local/drupal-admin.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$databases['default']['default'] = [
  'database' => $tclRuntime . '/site.sqlite',
  'prefix' => '',
  'driver' => 'sqlite',
  'namespace' => 'Drupal\\sqlite\\Driver\\Database\\sqlite',
  'autoload' => 'core/modules/sqlite/src/Driver/Database/sqlite/',
];
$settings['hash_salt'] = $tclSecrets['hashSalt'];
$settings['trusted_host_patterns'] = ['^127\\.0\\.0\\.1$'];
$settings['tcl_local_session_suffix'] = hash('sha256', $tclProject);
$settings['file_private_path'] = $tclRuntime . '/private-files';
$settings['file_temp_path'] = $tclRuntime . '/temp';
$settings['config_sync_directory'] = $tclRuntime . '/config-sync';
$settings['update_free_access'] = FALSE;
$settings['rebuild_access'] = FALSE;
$settings['skip_permissions_hardening'] = TRUE;
$config['system.mail']['interface']['default'] = 'tcl_null_mail';
$settings['tcl_support_enabled'] = TRUE;
$settings['tcl_support_mail_mode'] = 'capture';
$config['system.mail']['interface']['tcl_support_report'] = 'test_mail_collector';
$config['system.logging']['error_level'] = 'hide';
unset($tclSecrets);
