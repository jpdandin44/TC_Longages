<?php

declare(strict_types=1);

namespace Drupal\tcl_site;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Site\Settings;

/** Separates sessions between loopback checkouts sharing the same hostname. */
final class TclSiteServiceProvider extends ServiceProviderBase {

  public function alter(ContainerBuilder $container): void {
    $suffix = Settings::get('tcl_local_session_suffix');
    if (is_string($suffix) && $suffix !== '') {
      $options = $container->getParameter('session.storage.options');
      $options['name_suffix'] = $suffix;
      $container->setParameter('session.storage.options', $options);
    }
  }

}
