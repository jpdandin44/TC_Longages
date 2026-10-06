<?php

declare(strict_types=1);

namespace Drupal\tcl_site\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\tcl_site\PublicPageEditor;
use Drupal\tcl_site\PublicPageText;

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
    $html = file_get_contents($file);
    $node = PublicPageEditor::node($page);
    if ($node) {
      if (!$node->isPublished() && !$node->access('view', \Drupal::currentUser())) {
        throw new NotFoundHttpException();
      }
      $digest = \Drupal::config('tcl_site.public_pages')->get('pages.' . $page . '.sourceDigest');
      $blocksDigest = \Drupal::config('tcl_site.public_pages')->get('pages.' . $page . '.blocksDigest');
      $html = PublicPageText::renderBlocks($html, json_decode($node->get(PublicPageEditor::TEXTS)->value, TRUE, 32, JSON_THROW_ON_ERROR), $blocksDigest);
      $html = PublicPageText::render($html, $node->label(), $node->get(PublicPageEditor::FIELD)->value, $digest);
    }
    return new Response($html, 200, [
      'Content-Type' => 'text/html; charset=UTF-8',
      'Cache-Control' => 'private, no-store, max-age=0',
    ]);
  }

  public function editor(): array {
    $rows = [];
    foreach (PublicPageEditor::PAGES as $key) {
      $node = PublicPageEditor::node($key);
      if (!$node) {
        throw new \RuntimeException('L’initialisation éditoriale est à terminer.');
      }
      $rows[] = [
        $node->label(),
        ['data' => Link::fromTextAndUrl('Voir', Url::fromUserInput('/' . $key . '.html'))->toRenderable()],
        ['data' => $node->access('update') ? Link::fromTextAndUrl('Modifier', Url::fromRoute('entity.node.edit_form', ['node' => $node->id()], ['query' => ['destination' => '/admin/content/tcl-pages']]))->toRenderable() : 'Accès en lecture'],
        ['data' => $node->access('view all revisions') ? Link::fromTextAndUrl('Révisions', Url::fromRoute('entity.node.version_history', ['node' => $node->id()]))->toRenderable() : ''],
      ];
    }
    return [
      'intro' => ['#markup' => '<p>Choisissez une page puis Modifier : titre, présentation, rubriques, questions et légendes. Enregistrer publie le texte sur cette instance ; Révisions conserve les versions précédentes. La mise en page, les liens et les services restent gérés dans le code.</p>'],
      'pages' => ['#type' => 'table', '#header' => ['Page', 'Aperçu', 'Édition', 'Historique'], '#rows' => $rows],
      'accounts' => \Drupal::currentUser()->hasPermission('administer users') ? Link::fromTextAndUrl('Gérer les comptes', Url::fromRoute('entity.user.collection'))->toRenderable() : [],
      '#cache' => ['max-age' => 0],
    ];
  }

}
