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
    $rows = [];
    foreach ($this->events->all($this->currentUser()) as $event) {
      $schedule = EventSchedule::inspect($event);
      $rows[] = [
        ['data' => ['#plain_text' => $event['title']]],
        ['data' => ['#plain_text' => $event['start_date'] . ' à ' . $event['start_time']]],
        EventSchedule::FREQUENCIES[$event['frequency']], count($schedule['occurrences']),
        EventRepository::STATUSES[$event['status']],
        Link::fromTextAndUrl('Ouvrir / modifier', Url::fromRoute('tcl_bureau.event_edit', ['event' => $event['id']])),
      ];
    }
    return [
      '#type' => 'container', '#attributes' => ['class' => ['tcl-bureau']], '#cache' => ['max-age' => 0],
      '#attached' => ['library' => ['tcl_bureau/bureau']],
      'back' => Link::fromTextAndUrl('Retour au Bureau', Url::fromRoute('tcl_bureau.home'))->toRenderable(),
      'notice' => ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'Agenda de recette indépendant — données fictives uniquement. Ces événements restent dans le Bureau de préproduction et ne sont pas diffusés dans Google Agenda ni sur le site public. Horaires : Europe/Paris.'],
      'add' => Link::fromTextAndUrl('Créer un événement', Url::fromRoute('tcl_bureau.event_add'))->toRenderable(),
      'table' => ['#type' => 'container', '#attributes' => ['class' => ['tcl-table']], 'content' => [
        '#type' => 'table', '#header' => ['Événement', 'Premier début', 'Répétition', 'Occurrences', 'État', 'Action'], '#rows' => $rows, '#empty' => 'Aucun événement de recette. Créez le premier.',
      ]],
    ];
  }
}
