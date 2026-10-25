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
use Drupal\tcl_bureau\CommunicationImage;
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
    $build['state'] = ['#type' => 'html_tag', '#tag' => 'p', '#attributes' => ['class' => ['tcl-status']], '#value' => 'État : ' . CommunicationRepository::STATUSES[$row['status']] . ' — Révision ' . $row['revision']];
    $build['edit'] = Link::fromTextAndUrl('Modifier le brouillon', Url::fromRoute('tcl_bureau.communication_edit', ['post' => $post]))->toRenderable();
    $selectedChannels = explode(',', $row['channels']);
    $build['channels'] = ['#type' => 'html_tag', '#tag' => 'p', '#attributes' => ['class' => ['tcl-channel-summary']], '#value' => 'Canaux choisis : ' . implode(', ', array_intersect_key(CommunicationRepository::CHANNELS, array_flip($selectedChannels)))];
    $build['preview_help'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'Aperçus de contenu : la présentation finale dépend de Facebook ou WhatsApp. Une affiche reste à joindre séparément dans le service choisi.'];
    $build['preview'] = ['#type' => 'container', '#attributes' => ['class' => ['tcl-preview-grid']]];
    $build['fft'] = ['#type' => 'details', '#title' => 'Outils FFT complémentaires (saisie manuelle)', '#open' => FALSE];
    foreach (['site' => 'Site', 'facebook' => 'Facebook', 'whatsapp' => 'WhatsApp', 'email' => 'Courriel', 'adoc' => 'ADOC'] as $key => $channel) {
      if ($key !== 'adoc' && !in_array($key, $selectedChannels, TRUE)) { continue; }
      $card = ['#type' => 'html_tag', '#tag' => 'section', '#attributes' => ['class' => ['tcl-preview-card', 'tcl-preview-' . $key]], 'heading' => ['#type' => 'html_tag', '#tag' => 'h2', '#value' => 'Aperçu ' . $channel], 'content' => $this->article(\Drupal\tcl_bureau\ChannelContent::forChannel($row, $key), FALSE)];
      if ($channel === 'ADOC') {
        $text = self::message($row, FALSE);
        // Count UTF-16 code units, like the prototype; never truncate a message.
        $length = strlen(mb_convert_encoding($text, 'UTF-16LE', 'UTF-8')) / 2;
        $card['limit'] = ['#plain_text' => $length . ' / 2 000 caractères — Visible sur Ten’Up : ' . ($row['tenup_visible'] === 'yes' ? 'Oui' : 'Non') . ($length > 2000 ? '. Contenu trop long pour ADOC : raccourcissez-le avant saisie.' : '')];
      }
      if ($channel === 'ADOC') { $build['fft']['preview'] = $card; }
      else { $build['preview'][$channel] = $card; }
    }
    $actions = ['draft' => ['validate' => 'Valider cette actualité'], 'validated' => ['publish' => 'Publier sur le site'], 'published' => ['withdraw' => 'Retirer du site']][$row['status']] ?? [];
    if (!in_array('site', $selectedChannels, TRUE)) { unset($actions['publish']); }
    $actions['archive'] = 'Archiver';
    foreach ($actions as $action => $label) {
      $build[$action] = Link::fromTextAndUrl($label, Url::fromRoute('tcl_bureau.communication_action', ['post' => $post, 'action' => $action]))->toRenderable();
    }
    if (in_array($row['status'], ['validated', 'published'], TRUE) && (int) $row['approved_revision'] === (int) $row['revision']) {
      if (in_array('email', $selectedChannels, TRUE) && \Drupal\tcl_bureau\SeasonCatalogue::recipeEnabled()) {
        $build['member_mail'] = Link::fromTextAndUrl('Préparer le courriel aux adhérents', Url::fromRoute('tcl_bureau.member_mail', ['post' => $post]))->toRenderable();
      }
      $build['sharing'] = ['#type' => 'details', '#title' => 'Partager manuellement la révision validée', '#open' => TRUE];
      $build['sharing']['help'] = ['#plain_text' => 'Choisissez le destinataire et confirmez l’envoi dans le service. L’affiche doit être jointe manuellement. Une ouverture ne confirme aucune livraison.'];
      $build['#attached']['library'][] = 'tcl_bureau/sharing_tools';
      foreach (['facebook' => 'Facebook', 'whatsapp' => 'WhatsApp'] as $channel => $label) {
        if (!in_array($channel, $selectedChannels, TRUE)) { continue; }
        $build['sharing'][$channel] = ['#type' => 'details', '#title' => 'Message ' . $label, '#open' => TRUE, '#attributes' => ['data-sharing-tools' => '']];
        $build['sharing'][$channel]['text'] = ['#type' => 'textarea', '#title' => 'Message à copier — ' . $label, '#value' => self::message($row, TRUE, $channel), '#attributes' => ['readonly' => 'readonly'], '#rows' => 6];
        $build['sharing'][$channel]['copy'] = ['#type' => 'html_tag', '#tag' => 'button', '#value' => 'Copier pour ' . $label, '#attributes' => ['type' => 'button', 'data-copy-message' => '', 'hidden' => 'hidden']];
        $build['sharing'][$channel]['feedback'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => '', '#attributes' => ['role' => 'status', 'aria-live' => 'polite', 'data-copy-status' => '']];
      }
      if ($row['image_data'] === '') {
        $build['sharing']['no_poster'] = ['#plain_text' => 'Aucune affiche jointe : le message peut être partagé seul.'];
      }
      foreach (['WhatsApp' => 'https://wa.me/?text=' . rawurlencode(self::message($row, TRUE, 'whatsapp')), 'Facebook du club' => 'https://www.facebook.com/tc.longages.31', 'ADOC' => 'https://adoc.app.fft.fr/adoc/', 'Ten’Up du club' => 'https://tenup.fft.fr/club/60310230'] as $label => $url) {
        if (($label === 'WhatsApp' && !in_array('whatsapp', $selectedChannels, TRUE)) || ($label === 'Facebook du club' && !in_array('facebook', $selectedChannels, TRUE))) { continue; }
        if ($label === 'ADOC' && strlen(mb_convert_encoding(self::message($row, FALSE), 'UTF-16LE', 'UTF-8')) / 2 > 2000) {
          $build['sharing']['ADOC'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'Préparation ADOC indisponible : contenu supérieur à 2 000 caractères.'];
          continue;
        }
        $target = in_array($label, ['ADOC', 'Ten’Up du club'], TRUE) ? 'fft' : 'sharing';
        $build[$target][$label] = Link::fromTextAndUrl('Ouvrir ' . $label . ' ↗', Url::fromUri($url, ['attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer', 'referrerpolicy' => 'no-referrer']]))->toRenderable();
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

  public static function message(array $post, bool $title = TRUE, string $channel = 'site'): string {
    $post = \Drupal\tcl_bureau\ChannelContent::forChannel($post, $channel);
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
    $asPdf = CommunicationImage::isPdf($row['image_data']);
    if ($asPdf) {
      return new Response($row['image_data'], 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="affiche-club-' . (int) $row['id'] . '.pdf"', 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "sandbox; default-src 'none'", 'Cache-Control' => 'private, no-store']);
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
      $row = \Drupal\tcl_bureau\ChannelContent::forChannel($row, 'site');
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
    $build['article'] = $this->article(\Drupal\tcl_bureau\ChannelContent::forChannel($row, 'site'), TRUE);
    return $build;
  }

  /** Add published news to the original club page without touching V1 files. */
  public function front(): Response {
    $response = (new ClubPageController())->front();
    $response->setContent(\Drupal::service('tcl_bureau.seasons')->renderHomepage($response->getContent()));
    $url = Html::escape(Url::fromRoute('tcl_bureau.public_news')->toString());
    $posts = $this->repository->published();
    if ($posts) {
      // The same publication query drives the banner and the public listing.
      // No draft, validation-only revision or withdrawn post is eligible.
      $latest = \Drupal\tcl_bureau\ChannelContent::forChannel($this->repository->published((int) $posts[0]['id']), 'site');
      $postUrl = Html::escape(Url::fromRoute('tcl_bureau.public_post', ['post' => $latest['id']])->toString());
      $body = preg_replace('/\s+/u', ' ', trim($latest['body']));
      $summary = mb_strlen($body) > 220 ? mb_substr($body, 0, 219) . '…' : $body;
      $banner = '<style>.tcl-flash-news{margin:1.25rem auto;padding:1.25rem;border-left:5px solid #a6192e;background:#fff5f6;color:#202124;border-radius:.5rem;overflow-wrap:anywhere}.tcl-flash-layout{display:flex;align-items:center;gap:1.25rem}.tcl-flash-copy{flex:1;min-width:0}.tcl-flash-thumb{display:block;flex:0 0 110px;max-width:110px}.tcl-flash-thumb img,.tcl-flash-thumb canvas{display:block;width:100%;height:auto;max-height:155px;object-fit:contain;border-radius:.35rem}.tcl-flash-thumb p{display:none}@media(max-width:480px){.tcl-flash-layout{align-items:flex-start}.tcl-flash-thumb{flex-basis:75px;max-width:75px}}.tcl-flash-news h2{margin:.25rem 0;font-size:clamp(1.25rem,2.5vw,1.65rem);line-height:1.3}.tcl-flash-news p{margin:.5rem 0}.tcl-flash-news nav{display:flex;flex-wrap:wrap;gap:.75rem 1.5rem}.tcl-flash-news a{color:#801326;text-decoration:underline}.tcl-flash-news a:focus-visible{outline:3px solid #202124;outline-offset:4px}</style>';
      $poster = '';
      if ($latest['image_data'] !== '') {
        $imageUrl = Html::escape(Url::fromRoute('tcl_bureau.public_image', ['post' => $latest['id']], ['query' => ['v' => $latest['revision']]])->toString());
        $alt = Html::escape($latest['image_alt']);
        $visual = CommunicationImage::isPdf($latest['image_data']) ? '<span data-pdf-url="' . $imageUrl . '">Voir l’affiche PDF</span>' : '<img src="' . $imageUrl . '" alt="' . $alt . '" width="110" loading="lazy">';
        $poster = '<a class="tcl-flash-thumb" href="' . $postUrl . '" aria-label="Voir l’affiche : ' . $alt . '">' . $visual . '</a>';
      }
      $banner .= '<section id="flash-news" class="container tcl-flash-news" aria-labelledby="flash-news-title"><div class="tcl-flash-layout">' . $poster . '<div class="tcl-flash-copy"><p>Actualités du club</p><h2 id="flash-news-title">' . Html::escape($latest['title']) . '</h2>';
      if ($summary !== '') {
        $banner .= '<p>' . Html::escape($summary) . '</p>';
      }
      $registration = \Drupal\tcl_bureau\EventParticipation::registrationUrl((int) $latest['id']);
      $signup = $registration !== '' ? '<a class="tcl-event-signup" href="' . Html::escape($registration) . '">Je m’inscris à cet événement</a>' : '';
      $banner .= '<nav aria-label="Dernière actualité"><a href="' . $postUrl . '">Lire la suite</a><a href="' . $url . '">Toutes les actualités</a>' . $signup . '</nav></div></div></section>';
      if ($poster !== '' && CommunicationImage::isPdf($latest['image_data'])) { $banner .= '<script type="module" src="/modules/custom/tcl_bureau/js/poster-preview.js"></script>'; }
      $response->setContent(preg_replace_callback('/<main\b[^>]*>/i', static fn(array $match): string => $match[0] . $banner, $response->getContent(), 1));
    }
    return $response;
  }

  private function article(array $row, bool $public): array {
    $build = ['#type' => 'html_tag', '#tag' => 'article', 'category' => ['#plain_text' => CommunicationRepository::CATEGORIES[$row['category']]], 'title' => ['#type' => 'html_tag', '#tag' => 'h3', '#value' => Html::escape($row['title'])], 'body' => ['#type' => 'container', '#attributes' => ['class' => ['tcl-post-body']], 'text' => ['#plain_text' => $row['body']]]];
    if (!empty($row['image_data'])) {
      $build['image'] = ['#theme' => 'image', '#uri' => Url::fromRoute($public ? 'tcl_bureau.public_image' : 'tcl_bureau.communication_image', ['post' => $row['id']], ['query' => ['v' => $row['revision']]])->toString(), '#alt' => $row['image_alt'], '#attributes' => ['class' => ['tcl-post-image']]];
    }
    if (!empty($row['image_data']) && CommunicationImage::isPdf($row['image_data'])) {
      $build['image'] = Link::fromTextAndUrl('Télécharger l’affiche PDF — ' . $row['image_alt'], Url::fromRoute($public ? 'tcl_bureau.public_image' : 'tcl_bureau.communication_image', ['post' => $row['id']]))->toRenderable();
      $build['pdf_preview'] = ['#type' => 'container', '#attributes' => ['class' => ['tcl-pdf-preview'], 'data-pdf-url' => Url::fromRoute($public ? 'tcl_bureau.public_image' : 'tcl_bureau.communication_image', ['post' => $row['id']], ['query' => ['v' => $row['revision']]])->toString()], 'loading' => ['#plain_text' => 'Chargement de l’aperçu PDF…']];
    }
    $registration = \Drupal\tcl_bureau\EventParticipation::registrationUrl((int) $row['id']);
    if ($public && $registration !== '') { $build['signup'] = Link::fromTextAndUrl('Je m’inscris à cet événement', Url::fromUri($registration))->toRenderable(); }
    if ($row['link'] !== '') {
      $build['link'] = Link::fromTextAndUrl('Consulter le lien utile ↗', Url::fromUri($row['link'], ['attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer']]))->toRenderable();
    }
    return $build;
  }

  private function shell(bool $public = FALSE): array {
    $build = ['#type' => 'container', '#attributes' => ['class' => ['tcl-bureau', 'tcl-communication']], '#cache' => ['max-age' => 0], '#attached' => ['library' => ['tcl_bureau/communication', 'tcl_bureau/poster_preview']]];
    $build['navigation'] = ['#type' => 'html_tag', '#tag' => 'nav', '#attributes' => ['aria-label' => $public ? 'Actualités du club' : 'Bureau']];
    $links = $public ? ['Retour au site' => 'tcl_site.front', 'Toutes les actualités' => 'tcl_bureau.public_news'] : ['Adhérents' => 'tcl_bureau.registrations', 'Communication' => 'tcl_bureau.communication', 'Archives' => 'tcl_bureau.communication_archives', 'Actualités du site' => 'tcl_bureau.public_news', 'Retour au site' => 'tcl_site.front', 'Se déconnecter' => 'user.logout'];
    foreach ($links as $label => $route) {
      $build['navigation'][$route] = Link::fromTextAndUrl($label, Url::fromRoute($route))->toRenderable();
      $current = \Drupal::routeMatch()->getRouteName();
      if ($current === $route || ($route === 'tcl_bureau.communication' && in_array($current, ['tcl_bureau.communication_review', 'tcl_bureau.communication_edit', 'tcl_bureau.communication_add'], TRUE))) {
        $build['navigation'][$route]['#attributes']['aria-current'] = $current === $route ? 'page' : 'location';
      }
    }
    return $build;
  }
}
