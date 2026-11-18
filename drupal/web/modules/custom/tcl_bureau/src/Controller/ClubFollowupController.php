<?php
declare(strict_types=1);

namespace Drupal\tcl_bureau\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** Read-only, synthetic follow-up screens, limited to preproduction. */
final class ClubFollowupController extends ControllerBase {
  public static function enabled(AccountInterface $account): bool {
    return \Drupal::request()->getSchemeAndHttpHost() === 'https://preprod.tclongages.fr'
      && \Drupal::service('tcl_bureau.communication')->allowed($account);
  }

  public function access(AccountInterface $account) {
    return AccessResult::allowedIf(self::enabled($account))->setCacheMaxAge(0);
  }

  public function view(): Response {
    if (!self::enabled($this->currentUser())) {
      throw new AccessDeniedHttpException();
    }
    $path = \Drupal::service('extension.list.module')->getPath('tcl_bureau');
    $html = file_get_contents(DRUPAL_ROOT . '/' . $path . '/templates/club-followup-recipe.html');
    return new Response($html, 200, [
      'Content-Type' => 'text/html; charset=UTF-8',
      'Cache-Control' => 'private, no-store',
      'X-Robots-Tag' => 'noindex, nofollow',
      'Referrer-Policy' => 'same-origin',
    ]);
  }
}
