<?php

declare(strict_types=1);

namespace Drupal\tcl_support\EventSubscriber;

use Drupal\Component\Utility\Html;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SupportButtonSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [KernelEvents::RESPONSE => ['onResponse', 0]];
  }

  public function onResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest() || Settings::get('tcl_support_enabled', FALSE) !== TRUE
      || $event->getResponse()->getStatusCode() !== 200
      || !str_starts_with((string) $event->getRequest()->attributes->get('_route'), 'tcl_site.')) {
      return;
    }
    $response = $event->getResponse();
    $html = $response->getContent();
    if (!is_string($html) || !str_contains($html, '</body>')) {
      return;
    }
    // Only the allowlisted path is carried over, never query strings or tokens.
    $path = $event->getRequest()->getPathInfo();
    if (!in_array($path, ['/', '/club', '/index.html', '/competitions.html', '/calendrier.html', '/disponibilites.html', '/equipes.html', '/espace.html', '/contact.html'], TRUE)) {
      return;
    }
    $link = Html::escape(Url::fromRoute('tcl_support.report', [], ['query' => ['page' => $path]])->toString());
    $widget = '<style>.tcl-support-link{display:block;padding:1.2rem;text-align:center;background:#faf4f5;border-top:1px solid #e7cbd0;font:600 1rem/1.5 Arial,sans-serif;color:#85152a}.tcl-support-link a{color:inherit;display:inline-block;padding:.65rem 1rem;border:1px solid currentColor;border-radius:.35rem}.tcl-support-link a:focus-visible{outline:3px solid #111;outline-offset:4px}</style>'
      . '<aside class="tcl-support-link" aria-label="Assistance du site"><a href="' . $link . '">Signaler un problème sur le site</a></aside>';
    $response->setContent(str_replace('</body>', $widget . '</body>', $html));
  }

}
