<?php

declare(strict_types=1);

namespace Drupal\tcl_identity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/** Extend Simple OAuth through its service contract, without patching vendor code. */
final class TclIdentityServiceProvider extends ServiceProviderBase {
  public function alter(ContainerBuilder $container): void {
    $definition = $container->getDefinition('simple_oauth.server.response_type');
    $definition->setClass(TcIdTokenResponse::class);
    $definition->addArgument(new Reference('request_stack'));
    $definition->addArgument(new Reference('keyvalue.expirable'));
  }
}
