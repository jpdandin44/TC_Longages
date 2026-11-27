<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

use Drupal\Core\Link;
use Drupal\Core\Url;

/** Shared, permission-aware workspace navigation. Never grants route access. */
final class WorkspaceNavigation {
  public static function visible(): bool {
    return str_starts_with((string) \Drupal::routeMatch()->getRouteName(), 'tcl_bureau.')
      && str_starts_with(\Drupal::request()->getPathInfo(), '/bureau')
      && \Drupal::service('tcl_bureau.repository')->allowed(\Drupal::currentUser(), 'access tcl bureau');
  }

  public static function build(): array {
    $actor = \Drupal::currentUser();
    $repo = \Drupal::service('tcl_bureau.repository');
    $current = (string) \Drupal::routeMatch()->getRouteName();
    $items = ['tcl_bureau.home' => [\Drupal::service('tcl_bureau.events')->allowed($actor) ? 'Agenda' : 'Accueil du club', '▦']];
    if (\Drupal::service('tcl_bureau.events')->allowed($actor)) {
      $items['tcl_bureau.events'] = ['Événements', '◷'];
      $items['tcl_bureau.lessons'] = ['Cours de tennis', '🎾'];
      if (EventParticipation::enabled()) { $items['tcl_bureau.responses'] = ['Suivi des réponses', '✓']; }
    }
    if ($repo->allowed($actor, 'manage tcl communication')) {
      $items['tcl_bureau.communication'] = ['Communications', '✎'];
      $items['tcl_bureau.delivery_history'] = ['Historique des envois', '↗'];
    }
    if (GoogleContactsConnection::allowed($actor)) { $items['tcl_bureau.google_contacts'] = ['Contacts Google', '◎']; }
    if (\Drupal::config('tcl_bureau.settings')->get('teams_enabled')) { $items['tcl_bureau.teams'] = [$repo->allowed($actor, 'manage tcl teams') ? 'Équipes du club' : 'Mes équipes', '♧']; }
    if ($repo->canManageAccount($actor)) { $items['tcl_bureau.accounts'] = ['Comptes et accès', '⚙']; }
    $items['tcl_bureau.members'] = ['Adhérents · bientôt', '◌'];
    $active = str_starts_with($current, 'tcl_bureau.lesson_') ? 'tcl_bureau.lessons' : $current;
    if (str_starts_with($current, 'tcl_bureau.communication_') || $current === 'tcl_bureau.member_mail') { $active = 'tcl_bureau.communication'; }
    if (str_starts_with($current, 'tcl_bureau.account_')) { $active = 'tcl_bureau.accounts'; }
    if (str_starts_with($current, 'tcl_bureau.team_')) { $active = 'tcl_bureau.teams'; }
    if (in_array($current, ['tcl_bureau.event_add', 'tcl_bureau.event_edit'], TRUE)) { $active = 'tcl_bureau.events'; }
    if ($current === 'tcl_bureau.event_participation') { $active = 'tcl_bureau.responses'; }
    $build = ['#type' => 'container', '#attributes' => ['class' => ['tcl-workspace-nav']], '#cache' => ['max-age' => 0],
      'brand' => ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'TC LONGAGES', '#attributes' => ['class' => ['tcl-workspace-brand']]],
      'title' => ['#type' => 'html_tag', '#tag' => 'h2', '#value' => 'Espace du club'],
      'menu' => ['#type' => 'html_tag', '#tag' => 'nav', '#attributes' => ['aria-label' => 'Navigation principale du Bureau']],
    ];
    foreach ($items as $route => [$label, $icon]) {
      $link = Link::fromTextAndUrl($label, Url::fromRoute($route))->toRenderable();
      $link['#attributes'] = ['data-icon' => $icon];
      if ($route === $active) { $link['#attributes']['aria-current'] = 'page'; }
      $build['menu'][$route] = $link;
    }
    $build['user'] = ['#type' => 'container', '#attributes' => ['class' => ['tcl-workspace-user']],
      'name' => ['#plain_text' => $actor->getAccountName()],
      'site' => Link::fromTextAndUrl('Voir le site ↗', Url::fromRoute('tcl_site.front'))->toRenderable(),
      'logout' => Link::fromTextAndUrl('Se déconnecter', Url::fromRoute('user.logout'))->toRenderable(),
    ];
    return $build;
  }
}
