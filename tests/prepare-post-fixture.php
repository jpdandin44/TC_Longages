<?php
declare(strict_types=1);
require dirname(__DIR__) . '/drupal/bin/local-bootstrap.php';
// local-bootstrap refuses HTTP invocation, hosted databases and other checkouts.
\Drupal::service('module_installer')->install(['tcl_post_fixture']);
$ids = \Drupal::entityQuery('user')->accessCheck(FALSE)->condition('name', 'oauth-local-bureau')->execute();
$user = \Drupal\user\Entity\User::load(reset($ids));
$user->addRole('tcl_admin')->save();
drupal_flush_all_caches();
echo "Local test fixture enabled; outbound Google simulated and mail forbidden.\n";
