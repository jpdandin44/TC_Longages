<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

use Drupal\Component\Utility\Html;

/** Read-only calendar of events explicitly linked to a published site announcement. */
final class PublicAgenda {
  public static function render(): string {
    if (!EventRepository::clubEnabled()) { return ''; }
    $posts = [];
    foreach (\Drupal::service('tcl_bureau.communication')->published() as $post) {
      $posts[(int) $post['id']] = $post;
    }
    $query = \Drupal::request()->query->get('mois', '');
    return self::month(\Drupal::keyValue(EventRepository::storeName())->getAll(), $posts, is_string($query) ? $query : '');
  }

  /** Only title/date/status/category and public detail links reach the visitor. */
  public static function month(array $rows, array $published, string $month): string {
    $zone = new \DateTimeZone('Europe/Paris');
    $today = new \DateTimeImmutable('today', $zone);
    if (!preg_match('/^20[0-9]{2}-(0[1-9]|1[0-2])$/D', $month)) { $month = $today->format('Y-m'); }
    $first = new \DateTimeImmutable($month . '-01', $zone);
    $last = $first->modify('last day of this month');
    $days = [];
    foreach ($rows as $event) {
      $postId = (int) ($event['poster_post'] ?? 0);
      if ($postId < 1 || !isset($published[$postId]) || !in_array($event['status'] ?? '', ['scheduled', 'cancelled'], TRUE)) { continue; }
      foreach (EventSchedule::inspect($event)['occurrences'] as $occurrence) {
        $start = new \DateTimeImmutable($occurrence['start']);
        $end = new \DateTimeImmutable($occurrence['end']);
        $lastDay = $end->modify('-1 second')->setTime(0, 0);
        for ($day = max($first, $start->setTime(0, 0)); $day <= min($last, $lastDay); $day = $day->modify('+1 day')) {
          $category = isset(EventRepository::CATEGORIES[$event['category'] ?? '']) ? $event['category'] : 'other';
          [$label, $icon] = EventRepository::CATEGORIES[$category];
          $days[$day->format('Y-m-d')][] = ['start' => $start, 'end' => $end, 'category' => $category, 'label' => $label, 'icon' => $icon, 'title' => $published[$postId]['title'], 'post' => $postId, 'cancelled' => $event['status'] === 'cancelled'];
        }
      }
    }
    $months = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    $url = static fn(string $m): string => Html::escape(base_path() . 'calendrier.html?mois=' . $m . '#agenda-public');
    $html = '<section id="agenda-public" class="tcl-month-editor" aria-labelledby="agenda-public-title"><h2 id="agenda-public-title">Les rendez-vous publiés du club</h2><p>Horaires de Paris. Sélectionnez un événement pour consulter son actualité.</p><nav class="tcl-month-toolbar" aria-label="Mois des événements publics"><a href="' . $url($first->modify('-1 month')->format('Y-m')) . '">← Mois précédent</a><h3>' . ucfirst($months[(int) $first->format('n') - 1]) . ' ' . $first->format('Y') . '</h3><a href="' . $url($first->modify('+1 month')->format('Y-m')) . '">Mois suivant →</a></nav><div class="tcl-month-grid">';
    foreach (['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'] as $label) { $html .= '<div class="tcl-month-weekday">' . $label . '</div>'; }
    for ($i = 1; $i < (int) $first->format('N'); $i++) { $html .= '<div class="tcl-month-empty" aria-hidden="true"></div>'; }
    for ($day = $first; $day <= $last; $day = $day->modify('+1 day')) {
      $date = $day->format('Y-m-d');
      $html .= '<div class="tcl-month-day"><time datetime="' . $date . '">' . $day->format('d/m') . '</time>';
      $entries = $days[$date] ?? [];
      usort($entries, static fn(array $a, array $b): int => $a['start'] <=> $b['start']);
      foreach ($entries as $entry) {
        $html .= '<a class="tcl-month-event tcl-event-' . $entry['category'] . '" href="' . Html::escape(base_path() . 'actualites/' . $entry['post']) . '"><span>' . $entry['icon'] . ' ' . Html::escape($entry['label']) . '</span><strong>' . Html::escape($entry['title']) . '</strong><span>' . $entry['start']->format('H:i') . ' – ' . $entry['end']->format('H:i') . ($entry['cancelled'] ? ' · Annulé' : '') . '</span></a>';
      }
      $html .= '</div>';
    }
    return $html . '</div>' . (!$days ? '<p>Aucun rendez-vous publié pour ce mois.</p>' : '') . '</section>';
  }
}
