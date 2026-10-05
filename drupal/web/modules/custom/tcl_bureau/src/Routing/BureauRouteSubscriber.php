<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/** Connects the existing Espace menu only when this V2 module is enabled. */
final class BureauRouteSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('tcl_site.espace')) {
      $route->setDefault('_controller', '\\Drupal\\tcl_bureau\\Controller\\BureauController::entry');
    }
    foreach (['tcl_site.front', 'tcl_site.index'] as $name) {
      if ($route = $collection->get($name)) {
        $route->setDefault('_controller', '\\Drupal\\tcl_bureau\\Controller\\CommunicationController::front');
      }
    }
  }

}
