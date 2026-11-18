<?php
namespace Drupal\tcl_post_fixture;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

final class TclPostFixtureServiceProvider extends ServiceProviderBase {
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('http_client')->setFactory([LocalClient::class, 'create'])->setArguments([]);
  }
}
