<?php

declare(strict_types=1);
namespace Drupal\tcl_bureau\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\tcl_bureau\EventRepository;
use Drupal\tcl_bureau\EventSchedule;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class EventController extends ControllerBase {
  public function __construct(private EventRepository $events) {}
  public static function create(ContainerInterface $container) { return new static($container->get('tcl_bureau.events')); }
  public function access(AccountInterface $account) { return AccessResult::allowedIf($this->events->allowed($account))->setCacheMaxAge(0); }
  public function overview(): array {
    $cards = ['#type' => 'container', '#attributes' => ['class' => ['tcl-event-grid']]];
    $filter = (string) \Drupal::request()->query->get('categorie', '');
    if (!isset(EventRepository::CATEGORIES[$filter])) { $filter = ''; }
    foreach ($this->events->all($this->currentUser()) as $event) {
      $category = isset(EventRepository::CATEGORIES[$event['category'] ?? '']) ? $event['category'] : 'other';
      if ($filter !== '' && $category !== $filter) { continue; }
      [$label, $icon] = EventRepository::CATEGORIES[$category];
      $schedule = EventSchedule::inspect($event);
      $card = ['#type' => 'html_tag', '#tag' => 'article', '#attributes' => ['class' => ['tcl-event-card', 'tcl-event-' . $category]],
        'badge' => ['#type' => 'container', '#attributes' => ['class' => ['tcl-event-badge']],
          'icon' => ['#type' => 'html_tag', '#tag' => 'span', '#attributes' => ['aria-hidden' => 'true'], '#value' => $icon],
          'label' => ['#plain_text' => $label]],
        'title' => ['#type' => 'html_tag', '#tag' => 'h2', 'text' => ['#plain_text' => $event['title']]],
      ];
      $post = !empty($event['poster_post']) ? \Drupal::service('tcl_bureau.communication')->get($this->currentUser(), (int) $event['poster_post']) : NULL;
      if ($post && $post['status'] !== 'archived' && $post['image_data'] !== '') {
        $image = Url::fromRoute('tcl_bureau.communication_image', ['post' => $post['id']], ['query' => ['v' => $post['revision']]])->toString();
        $card['poster'] = \Drupal\tcl_bureau\CommunicationImage::isPdf($post['image_data'])
          ? ['#type' => 'html_tag', '#tag' => 'div', '#attributes' => ['class' => ['tcl-event-poster'], 'data-pdf-url' => $image], '#value' => 'Aperçu de l’affiche PDF…']
          : ['#type' => 'html_tag', '#tag' => 'img', '#attributes' => ['class' => ['tcl-event-poster'], 'src' => $image, 'alt' => $post['image_alt'], 'loading' => 'lazy']];
        $card['communication'] = Link::fromTextAndUrl('Affiche et préparation du partage', Url::fromRoute('tcl_bureau.communication_review', ['post' => $post['id']]))->toRenderable();
      }
      else {
        $card['placeholder'] = ['#type' => 'html_tag', '#tag' => 'div', '#attributes' => ['class' => ['tcl-event-symbol'], 'aria-hidden' => 'true'], '#value' => $icon];
      }
      $card['date'] = ['#type' => 'html_tag', '#tag' => 'p', 'text' => ['#plain_text' => (new \DateTimeImmutable($event['start_date']))->format('d/m/Y') . ' à ' . $event['start_time'] . ' — ' . $event['location']]];
      $card['repeat'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => EventSchedule::FREQUENCIES[$event['frequency']] . ' · ' . count($schedule['occurrences']) . ' date(s)'];
      $card['state'] = ['#type' => 'html_tag', '#tag' => 'strong', '#value' => EventRepository::STATUSES[$event['status']]];
      $card['edit'] = Link::fromTextAndUrl('Ouvrir / modifier', Url::fromRoute('tcl_bureau.event_edit', ['event' => $event['id']]))->toRenderable();
      $cards[$event['id']] = $card;
    }
    if (count($cards) === 2) { $cards['empty'] = ['#plain_text' => 'Aucun événement de recette dans cette catégorie.']; }
    $filters = ['#type' => 'html_tag', '#tag' => 'nav', '#attributes' => ['aria-label' => 'Types de rendez-vous', 'class' => ['tcl-event-filters']]];
    foreach (['' => ['Tous', '']] + EventRepository::CATEGORIES as $key => [$label, $icon]) {
      $filters['filter_' . $key] = Link::fromTextAndUrl(trim($icon . ' ' . $label), Url::fromRoute('tcl_bureau.events', [], ['query' => $key === '' ? [] : ['categorie' => $key], 'attributes' => ['aria-current' => $filter === $key ? 'page' : 'false']]))->toRenderable();
    }
    return [
      '#type' => 'container', '#attributes' => ['class' => ['tcl-bureau']], '#cache' => ['max-age' => 0],
      '#attached' => ['library' => ['tcl_bureau/events', 'tcl_bureau/poster_preview']],
      'back' => Link::fromTextAndUrl('Retour au Bureau', Url::fromRoute('tcl_bureau.home'))->toRenderable(),
      'notice' => ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'Agenda de recette indépendant — données fictives uniquement. Ces événements restent dans le Bureau de préproduction et ne sont pas diffusés dans Google Agenda ni sur le site public. Horaires : Europe/Paris.'],
      'add' => Link::fromTextAndUrl('Créer un événement', Url::fromRoute('tcl_bureau.event_add'))->toRenderable(),
      'filters' => $filters,
      'cards' => $cards,
    ];
  }
}
