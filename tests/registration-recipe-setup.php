<?php
declare(strict_types=1);
// Refuses hosted databases before toggling any state; no credentials are printed.
require dirname(__DIR__) . '/drupal/bin/local-bootstrap.php';
$flag = dirname(__DIR__) . '/.local/registration-recipe.enabled';
if (($argv[1] ?? '') === 'enable') { touch($flag); }
elseif (($argv[1] ?? '') === 'disable') { if (is_file($flag)) { unlink($flag); } }
else { throw new InvalidArgumentException('enable or disable required'); }
drupal_flush_all_caches();
echo "Local recipe flag updated; routes rebuilt.\n";
