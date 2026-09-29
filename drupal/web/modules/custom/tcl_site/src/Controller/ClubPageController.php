<?php

declare(strict_types=1);

namespace Drupal\tcl_site\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Serves a fixed allowlist; page files stay outside the public document root. */
final class ClubPageController {

  private const PAGES = ['index', 'competitions', 'calendrier', 'disponibilites', 'equipes', 'espace', 'contact'];

  public function front(): Response {
    return $this->page('index');
  }

  public function page(string $page): Response {
    if (!in_array($page, self::PAGES, TRUE)) {
      throw new NotFoundHttpException();
    }
    $file = dirname(DRUPAL_ROOT) . '/site-pages/' . $page . '.html';
    if (!is_file($file) || !is_readable($file)) {
      throw new NotFoundHttpException('Vitrine locale non préparée.');
    }
    return new Response(file_get_contents($file), 200, [
      'Content-Type' => 'text/html; charset=UTF-8',
      'Cache-Control' => 'private, no-store, max-age=0',
      'X-Robots-Tag' => 'noindex, nofollow, noarchive',
    ]);
  }

}
