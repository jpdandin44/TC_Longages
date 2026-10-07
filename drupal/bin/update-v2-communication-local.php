<?php

declare(strict_types=1);

require __DIR__ . '/local-bootstrap.php';
\Drupal::moduleHandler()->loadInclude('tcl_bureau', 'install');
tcl_bureau_update_11001();
\Drupal::service('update.update_hook_registry')->setInstalledVersion('tcl_bureau', 11001);
drupal_flush_all_caches();
echo json_encode(['status' => 'updated', 'scope' => 'private local SQLite only', 'existingAccountsAndEnrolments' => 'preserved', 'externalDeployment' => FALSE]) . "\n";
