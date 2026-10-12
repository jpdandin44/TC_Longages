<?php

declare(strict_types=1);

namespace Drupal\tcl_identity;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

final class IdentityRoutes extends RouteSubscriberBase {
  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('oauth2_token.authorize')) {
      $route->setDefault('_controller', TcAuthorizeController::class . '::authorize');
    }
  }
}
