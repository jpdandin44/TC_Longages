<?php

declare(strict_types=1);

namespace Drupal\tcl_identity;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\simple_oauth\Controller\Oauth2AuthorizeController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/** Keep upstream authorization validation and use the club's native login form. */
final class TcAuthorizeController extends Oauth2AuthorizeController {
  protected function redirectAnonymous(Request $request): RedirectResponse {
    $destination = Url::fromRoute('oauth2_token.authorize', [], [
      'query' => UrlHelper::parse('/?' . $request->getQueryString())['query'],
    ]);
    return new RedirectResponse(Url::fromRoute('tcl_identity.login', [], [
      'query' => ['destination' => $destination->toString()],
    ])->toString());
  }
}
