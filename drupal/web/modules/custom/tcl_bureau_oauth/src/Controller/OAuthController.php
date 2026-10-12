<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau_oauth\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\tcl_bureau_oauth\BureauOAuth;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class OAuthController extends ControllerBase {
  public function __construct(private BureauOAuth $oauth) {}

  public static function create(ContainerInterface $container) {
    return new static($container->get('tcl_bureau_oauth.connection'));
  }

  public function login(): Response {
    if ($this->oauth->valid()) {
      return new RedirectResponse('/bureau', 303);
    }
    try {
      return new TrustedRedirectResponse($this->oauth->begin(), 303);
    }
    catch (\Throwable $error) {
      $this->recordFailure($error);
      return $this->failure(503, 'La connexion au Bureau est en cours de préparation. Contactez l’administrateur du club.');
    }
  }

  public function callback(): Response {
    try {
      $this->oauth->complete();
      return new RedirectResponse('/bureau', 303);
    }
    catch (\Throwable $error) {
      $this->recordFailure($error);
      return $this->failure(403, 'La connexion au Bureau n’a pas pu être confirmée. Votre compte doit être autorisé par l’administrateur du club.');
    }
  }

  private function failure(int $status, string $message): Response {
    return new Response('<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Connexion au Bureau</title></head><body><main><h1>Connexion au Bureau</h1><p>' . $message . '</p><p><a href="/bureau/connexion">Réessayer la connexion</a> · <a href="/">Retour au site</a></p></main></body></html>', $status, ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'private, no-store, max-age=0', 'Referrer-Policy' => 'no-referrer']);
  }

  private function recordFailure(\Throwable $error): void {
    // Never log an upstream exception message, request query, token or profile.
    $category = preg_match('/^oauth_[a-z_]+$/D', $error->getMessage()) ? $error->getMessage() : 'oauth_processing_failed';
    $this->getLogger('tcl_bureau_oauth')->warning('Connexion Bureau refusée : @category.', ['@category' => $category]);
  }
}
