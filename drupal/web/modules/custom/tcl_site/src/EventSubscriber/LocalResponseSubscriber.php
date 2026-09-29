<?php

declare(strict_types=1);

namespace Drupal\tcl_site\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Local evaluation is never indexable, including login and maintenance pages. */
final class LocalResponseSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [KernelEvents::RESPONSE => ['onResponse', -1000]];
  }

  public function onResponse(ResponseEvent $event): void {
    $event->getResponse()->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
    $event->getResponse()->headers->set('Cache-Control', 'private, no-store, max-age=0');
  }

}
