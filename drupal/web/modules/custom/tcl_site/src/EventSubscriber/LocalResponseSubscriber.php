<?php

declare(strict_types=1);

namespace Drupal\tcl_site\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Keep every response private; permit indexing only approved public pages. */
final class LocalResponseSubscriber implements EventSubscriberInterface {

  private const PUBLIC_PATHS = ['/', '/club', '/index.html', '/competitions.html', '/calendrier.html', '/disponibilites.html', '/equipes.html', '/espace.html', '/contact.html'];

  public static function getSubscribedEvents(): array {
    return [KernelEvents::RESPONSE => ['onResponse', -1000]];
  }

  public function onResponse(ResponseEvent $event): void {
    $response = $event->getResponse();
    $request = $event->getRequest();
    $indexable = getenv('TCL_PUBLIC_INDEXING') === '1'
      && $response->getStatusCode() === 200
      && in_array($request->getPathInfo(), self::PUBLIC_PATHS, TRUE);
    if ($indexable) {
      $response->headers->remove('X-Robots-Tag');
    }
    else {
      $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
    $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
  }

}
