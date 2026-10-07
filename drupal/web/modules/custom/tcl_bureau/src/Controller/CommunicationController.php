<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\tcl_bureau\CommunicationRepository;
use Drupal\tcl_site\Controller\ClubPageController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CommunicationController extends ControllerBase {
  public function __construct(private CommunicationRepository $repository) {}

  public static function create(ContainerInterface $container) {
    return new static($container->get('tcl_bureau.communication'));
  }

  public function access(AccountInterface $account) {
    return AccessResult::allowedIf($this->repository->allowed($account))->setCacheMaxAge(0);
  }

  public function archives(): array { return $this->overview(TRUE); }

  public function overview(bool $archived = FALSE): array {
    $build = $this->shell();
    $build['intro'] = ['#plain_text' => 'Rédigez les actualités du club, validez chaque révision, puis choisissez sa publication sur le site.'];
    $build['add'] = Link::fromTextAndUrl('Créer une actualité', Url::fromRoute('tcl_bureau.communication_add'))->toRenderable();
    $rows = [];
    foreach ($this->repository->list($this->currentUser(), $archived) as $post) {
      $route = $archived ? Url::fromRoute('tcl_bureau.communication_action', ['post' => $post['id'], 'action' => 'restore']) : Url::fromRoute('tcl_bureau.communication_review', ['post' => $post['id']]);
      $rows[] = [['data' => ['#plain_text' => $post['title']]], CommunicationRepository::STATUSES[$post['status']], Link::fromTextAndUrl($archived ? 'Restaurer en brouillon' : 'Relire et décider', $route)];
    }
    $build['posts'] = ['#type' => 'container', '#attributes' => ['class' => ['tcl-table']], 'table' => ['#type' => 'table', '#header' => ['Actualité', 'État', 'Action'], '#rows' => $rows, '#empty' => 'Aucune actualité enregistrée.']];
    return $build;
  }

  public function review(string $post): array {
    $row = $this->repository->get($this->currentUser(), (int) $post);
    if (!$row || $row['status'] === 'archived') {
      throw new NotFoundHttpException();
    }
    $build = $this->shell();
    $build['state'] = ['#plain_text' => 'État : ' . CommunicationRepository::STATUSES[$row['status']] . ' — Révision ' . $row['revision']];
    $build['edit'] = Link::fromTextAndUrl('Modifier le brouillon', Url::fromRoute('tcl_bureau.communication_edit', ['post' => $post]))->toRenderable();
    $build['preview'] = ['#type' => 'container', '#attributes' => ['class' => ['tcl-preview-grid']]];
    foreach (['Site', 'Facebook', 'WhatsApp', 'ADOC'] as $channel) {
      $card = ['#type' => 'html_tag', '#tag' => 'section', '#attributes' => ['class' => ['tcl-preview-card']], 'heading' => ['#type' => 'html_tag', '#tag' => 'h2', '#value' => 'Aperçu ' . $channel], 'content' => $this->article($row, FALSE)];
      if ($channel === 'ADOC') {
        $text = self::message($row, FALSE);
        // Count UTF-16 code units, like the prototype; never truncate a message.
        $length = strlen(mb_convert_encoding($text, 'UTF-16LE', 'UTF-8')) / 2;
        $card['limit'] = ['#plain_text' => $length . ' / 2 000 caractères — Visible sur Ten’Up : ' . ($row['tenup_visible'] === 'yes' ? 'Oui' : 'Non') . ($length > 2000 ? '. Contenu trop long pour ADOC : raccourcissez-le avant saisie.' : '')];
      }
      $build['preview'][$channel] = $card;
    }
    $actions = ['draft' => ['validate' => 'Valider cette actualité'], 'validated' => ['publish' => 'Publier sur le site'], 'published' => ['withdraw' => 'Retirer du site']][$row['status']] ?? [];
    $actions['archive'] = 'Archiver';
    foreach ($actions as $action => $label) {
      $build[$action] = Link::fromTextAndUrl($label, Url::fromRoute('tcl_bureau.communication_action', ['post' => $post, 'action' => $action]))->toRenderable();
    }
    if (in_array($row['status'], ['validated', 'published'], TRUE) && (int) $row['approved_revision'] === (int) $row['revision']) {
      $build['sharing'] = ['#type' => 'details', '#title' => 'Partager manuellement la révision validée', '#open' => TRUE];
      $build['sharing']['help'] = ['#plain_text' => 'Choisissez le destinataire et confirmez l’envoi dans le service. L’affiche doit être jointe manuellement. Une ouverture ne confirme aucune livraison.'];
      $build['sharing']['text'] = ['#type' => 'textarea', '#title' => 'Message à copier', '#value' => self::message($row), '#attributes' => ['readonly' => 'readonly'], '#rows' => 6];
      foreach (['WhatsApp' => 'https://wa.me/?text=' . rawurlencode(self::message($row)), 'Facebook du club' => 'https://www.facebook.com/tc.longages.31', 'ADOC' => 'https://adoc.app.fft.fr/adoc/', 'Ten’Up du club' => 'https://tenup.fft.fr/club/60310230'] as $label => $url) {
        if ($label === 'ADOC' && strlen(mb_convert_encoding(self::message($row, FALSE), 'UTF-16LE', 'UTF-8')) / 2 > 2000) {
          $build['sharing']['ADOC'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'Préparation ADOC indisponible : contenu supérieur à 2 000 caractères.'];
          continue;
        }
        $build['sharing'][$label] = Link::fromTextAndUrl('Ouvrir ' . $label . ' ↗', Url::fromUri($url, ['attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer', 'referrerpolicy' => 'no-referrer']]))->toRenderable();
      }
      if ($row['image_data'] !== '') {
        $build['sharing']['poster'] = Link::fromTextAndUrl('Télécharger l’affiche préparée', Url::fromRoute('tcl_bureau.communication_image', ['post' => $post], ['query' => ['download' => 1]]))->toRenderable();
      }
    }
    else {
      $build['sharing_pending'] = ['#plain_text' => 'Enregistrez et validez cette révision pour préparer son partage.'];
    }
    return $build;
  }

  public static function message(array $post, bool $title = TRUE): string {
    return implode("\n\n", array_values(array_filter([$title ? $post['title'] : '', $post['body'], $post['link']], static fn($value) => $value !== '')));
  }

  public function image(string $post): Response {
    return $this->imageResponse($this->repository->get($this->currentUser(), (int) $post));
  }

  public function publicImage(string $post): Response {
    return $this->imageResponse($this->repository->published((int) $post));
  }

  private function imageResponse(?array $row): Response {
    if (!$row || $row['image_data'] === '') {
      throw new NotFoundHttpException();
    }
    $headers = ['Content-Type' => 'image/jpeg', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
    if (\Drupal::request()->query->get('download') === '1') {
      $headers['Content-Disposition'] = 'attachment; filename="affiche-club-' . (int) $row['id'] . '.jpg"';
    }
    return new Response($row['image_data'], 200, $headers);
  }

  public function news(): array {
    $build = $this->shell(TRUE);
    $posts = $this->repository->published();
    foreach ($posts as $row) {
      $build['post_' . $row['id']] = Link::fromTextAndUrl($row['title'], Url::fromRoute('tcl_bureau.public_post', ['post' => $row['id']]))->toRenderable();
    }
    if (!$posts) {
      $build['empty'] = ['#plain_text' => 'Aucune actualité publiée pour le moment.'];
    }
    return $build;
  }

  public function publicPost(string $post): array {
    $row = $this->repository->published((int) $post);
    if (!$row) {
      throw new NotFoundHttpException();
    }
    $build = $this->shell(TRUE);
    $build['article'] = $this->article($row, TRUE);
    return $build;
  }

  /** Add published news to the original club page without touching V1 files. */
  public function front(): Response {
    $response = (new ClubPageController())->front();
    $url = Html::escape(Url::fromRoute('tcl_bureau.public_news')->toString());
    $news = '<section id="actualites-v2" class="section"><div class="container"><h2>Actualités du club</h2><ul>';
    foreach (array_slice($this->repository->published(), 0, 5) as $row) {
      $postUrl = Html::escape(Url::fromRoute('tcl_bureau.public_post', ['post' => $row['id']])->toString());
      $news .= '<li><a href="' . $postUrl . '">' . Html::escape($row['title']) . '</a></li>';
    }
    $news .= '</ul><p><a href="' . $url . '">Toutes les actualités</a></p></div></section>';
    $response->setContent(str_replace('</main>', $news . '</main>', $response->getContent()));
    return $response;
  }

  private function article(array $row, bool $public): array {
    $build = ['#type' => 'html_tag', '#tag' => 'article', 'category' => ['#plain_text' => CommunicationRepository::CATEGORIES[$row['category']]], 'title' => ['#type' => 'html_tag', '#tag' => 'h3', '#value' => Html::escape($row['title'])], 'body' => ['#type' => 'container', '#attributes' => ['class' => ['tcl-post-body']], 'text' => ['#plain_text' => $row['body']]]];
    if (!empty($row['image_data'])) {
      $build['image'] = ['#theme' => 'image', '#uri' => Url::fromRoute($public ? 'tcl_bureau.public_image' : 'tcl_bureau.communication_image', ['post' => $row['id']], ['query' => ['v' => $row['revision']]])->toString(), '#alt' => $row['image_alt'], '#attributes' => ['class' => ['tcl-post-image']]];
    }
    if ($row['link'] !== '') {
      $build['link'] = Link::fromTextAndUrl('Consulter le lien utile ↗', Url::fromUri($row['link'], ['attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer']]))->toRenderable();
    }
    return $build;
  }

  private function shell(bool $public = FALSE): array {
    $build = ['#type' => 'container', '#attributes' => ['class' => ['tcl-bureau', 'tcl-communication']], '#cache' => ['max-age' => 0], '#attached' => ['library' => ['tcl_bureau/communication']]];
    $build['navigation'] = ['#type' => 'html_tag', '#tag' => 'nav', '#attributes' => ['aria-label' => $public ? 'Actualités du club' : 'Bureau']];
    $links = $public ? ['Retour au site' => 'tcl_site.front', 'Toutes les actualités' => 'tcl_bureau.public_news'] : ['Adhérents' => 'tcl_bureau.registrations', 'Communication' => 'tcl_bureau.communication', 'Archives' => 'tcl_bureau.communication_archives', 'Actualités du site' => 'tcl_bureau.public_news', 'Retour au site' => 'tcl_site.front', 'Se déconnecter' => 'user.logout'];
    foreach ($links as $label => $route) {
      $build['navigation'][$route] = Link::fromTextAndUrl($label, Url::fromRoute($route))->toRenderable();
    }
    return $build;
  }
}
