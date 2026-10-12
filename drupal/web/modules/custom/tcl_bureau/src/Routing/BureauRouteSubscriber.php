<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/** Adds native news to the home page without replacing V1 information pages. */
final class BureauRouteSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection): void {
    foreach (['tcl_site.front', 'tcl_site.index'] as $name) {
      if ($route = $collection->get($name)) {
        $route->setDefault('_controller', '\\Drupal\\tcl_bureau\\Controller\\CommunicationController::front');
      }
    }
  }

}
