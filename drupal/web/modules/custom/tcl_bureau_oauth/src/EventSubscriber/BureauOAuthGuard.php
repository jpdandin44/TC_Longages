<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau_oauth\EventSubscriber;

use Drupal\tcl_bureau_oauth\BureauOAuth;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Runs after routing/session setup, before Drupal access checks/controllers. */
final class BureauOAuthGuard implements EventSubscriberInterface {
  public function __construct(private BureauOAuth $oauth) {}

  public static function getSubscribedEvents(): array {
    return [KernelEvents::REQUEST => ['onRequest', 34], KernelEvents::RESPONSE => ['onResponse', -1001]];
  }

  public function onRequest(RequestEvent $event): void {
    $path = $event->getRequest()->getPathInfo();
    if (($path !== '/bureau' && !str_starts_with($path, '/bureau/')) || in_array($path, ['/bureau/connexion', '/bureau/oauth/retour'], TRUE)) {
      return;
    }
    if (!$this->oauth->valid()) {
      $event->setResponse($event->getRequest()->isMethodSafe()
        ? new RedirectResponse('/bureau/connexion', 303)
        : new Response('Une nouvelle connexion au Bureau est nécessaire.', 403));
    }
  }

  public function onResponse(ResponseEvent $event): void {
    if (str_starts_with($event->getRequest()->getPathInfo(), '/bureau')) {
      $event->getResponse()->headers->set('Cache-Control', 'private, no-store, max-age=0');
      $event->getResponse()->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
      $event->getResponse()->headers->set('Referrer-Policy', 'no-referrer');
    }
  }
}
