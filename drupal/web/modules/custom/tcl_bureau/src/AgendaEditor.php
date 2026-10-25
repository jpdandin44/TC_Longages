<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

/** Private month view; the shared Google calendar is never mutated. */
final class AgendaEditor {
  public static function render(): string {
    $account = \Drupal::currentUser();
    $repository = \Drupal::service('tcl_bureau.events');
    if (!$repository->allowed($account)) { return ''; }
    $today = new \DateTimeImmutable('today', new \DateTimeZone('Europe/Paris'));
    $query = \Drupal::request()->query->get('mois', '');
    $month = is_string($query) && preg_match('/^20[0-9]{2}-(0[1-9]|1[0-2])$/D', $query) ? $query : $today->format('Y-m');
    $first = new \DateTimeImmutable($month . '-01', new \DateTimeZone('Europe/Paris'));
    $last = $first->modify('last day of this month');
    $events = [];
    foreach ($repository->all($account) as $event) {
      foreach (EventSchedule::inspect($event)['occurrences'] as $occurrence) {
        $start = new \DateTimeImmutable($occurrence['start']);
        $end = new \DateTimeImmutable($occurrence['end']);
        // Exclusive midnight ending does not occupy the next day.
        $end = $end->modify('-1 second');
        $day = max($first, $start->setTime(0, 0));
        for (; $day <= min($last, $end); $day = $day->modify('+1 day')) {
          $events[$day->format('Y-m-d')][] = [$event, $start];
        }
      }
    }
    $months = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    $label = ucfirst($months[(int) $first->format('n') - 1]) . ' ' . $first->format('Y');
    $page = static fn(string $m): string => Html::escape(Url::fromUserInput('/calendrier.html', ['query' => ['mois' => $m], 'fragment' => 'agenda-bureau'])->toString());
    $html = '<section id="agenda-bureau" class="tcl-month-editor" aria-labelledby="agenda-bureau-title"><h2 id="agenda-bureau-title">Mon agenda du Bureau</h2><p>Recette privée — cliquez sur un jour pour ajouter un événement, ou sur un rendez-vous pour le modifier. Les événements restent séparés du calendrier Google du club. Horaires : Europe/Paris.</p>';
    $html .= '<nav class="tcl-month-toolbar" aria-label="Navigation par mois"><a href="' . $page($first->modify('-1 month')->format('Y-m')) . '" aria-label="Mois précédent">← Précédent</a><h3>' . $label . '</h3><a href="' . $page($first->modify('+1 month')->format('Y-m')) . '" aria-label="Mois suivant">Suivant →</a><a href="' . $page($today->format('Y-m')) . '">Aujourd’hui</a><a href="' . Html::escape(Url::fromRoute('tcl_bureau.events')->toString()) . '">Toutes les séries et les participants</a></nav>';
    $html .= '<div class="tcl-month-grid">';
    foreach (['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'] as $name) { $html .= '<div class="tcl-month-weekday" aria-hidden="true">' . $name . '</div>'; }
    for ($i = 1; $i < (int) $first->format('N'); $i++) { $html .= '<div class="tcl-month-empty" aria-hidden="true"></div>'; }
    for ($day = $first; $day <= $last; $day = $day->modify('+1 day')) {
      $date = $day->format('Y-m-d');
      $return = '/calendrier.html?mois=' . $month . '#agenda-bureau';
      $options = ['query' => ['date' => $date, 'destination' => $return]];
      $add = Html::escape(Url::fromRoute('tcl_bureau.event_add', [], $options)->toString());
      $html .= '<div class="tcl-month-day' . ($date === $today->format('Y-m-d') ? ' is-today' : '') . '"><a class="tcl-month-add" href="' . $add . '" aria-label="Ajouter un événement le ' . $day->format('d/m/Y') . '"><time datetime="' . $date . '">' . $day->format('d/m') . '</time><span aria-hidden="true"> +</span></a>';
      foreach ($events[$date] ?? [] as [$event, $start]) {
        $category = isset(EventRepository::CATEGORIES[$event['category'] ?? '']) ? $event['category'] : 'other';
        [$categoryLabel, $icon] = EventRepository::CATEGORIES[$category];
        $url = Html::escape(Url::fromRoute('tcl_bureau.event_edit', ['event' => $event['id']], ['query' => ['destination' => $return]])->toString());
        $html .= '<a class="tcl-month-event tcl-event-' . $category . '" href="' . $url . '"><span>' . $icon . ' ' . Html::escape($categoryLabel) . '</span><strong>' . Html::escape($event['title']) . '</strong><span>' . $start->format('H:i') . ($event['status'] === 'cancelled' ? ' · Annulé' : '') . '</span></a>';
      }
      $html .= '</div>';
    }
    return $html . '</div></section>';
  }
}
