<?php

declare(strict_types=1);

namespace Drupal\tcl_support\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\tcl_support\SupportRepository;

final class TrackerController extends ControllerBase {

  public function list(): array {
    $repository = \Drupal::service('tcl_support.repository');
    $status = \Drupal::request()->query->get('etat', '');
    $status = is_string($status) ? $status : '';
    $build = ['#cache' => ['max-age' => 0], '#attached' => ['library' => ['tcl_support/tracker']]];
    $build['explanation'] = ['#plain_text' => 'Les demandes et leurs coordonnées sont privées. Une notification acceptée par le transport ne prouve pas sa réception dans la boîte support.'];
    $build['filters'] = ['#theme' => 'item_list', '#attributes' => ['class' => ['tcl-support-filters']], '#items' => []];
    foreach (['' => 'Toutes les demandes'] + SupportRepository::STATUSES as $key => $label) {
      $build['filters']['#items'][] = Link::fromTextAndUrl($label, Url::fromRoute('tcl_support.tracker', [], ['query' => ['etat' => $key]]))->toRenderable();
    }
    $rows = [];
    foreach ($repository->list($status) as $ticket) {
      $rows[] = [
        Link::fromTextAndUrl('#' . $ticket->id . ' — ' . $ticket->subject, Url::fromRoute('tcl_support.edit', ['request_id' => $ticket->id])),
        $ticket->category === 'need' ? 'Besoin / amélioration' : 'Problème',
        SupportRepository::STATUSES[$ticket->status],
        $ticket->owner ?: 'À attribuer',
        SupportRepository::NOTIFICATIONS[$ticket->notification],
        date('d/m/Y H:i', (int) $ticket->created),
      ];
    }
    $build['requests'] = ['#type' => 'container', '#attributes' => ['class' => ['tcl-support-table-wrap'], 'role' => 'region', 'aria-label' => 'Liste des demandes', 'tabindex' => '0'],
      'table' => ['#type' => 'table', '#header' => ['Demande', 'Type', 'État', 'Responsable', 'Notification', 'Création'],
        '#rows' => $rows, '#empty' => 'Aucune demande dans cette sélection.']];
    $build['pager'] = ['#type' => 'pager'];
    return $build;
  }

}
