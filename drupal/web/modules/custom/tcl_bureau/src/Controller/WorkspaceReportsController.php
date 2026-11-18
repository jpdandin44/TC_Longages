<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\tcl_bureau\EventParticipation;
use Drupal\tcl_bureau\EventSchedule;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** Read-only views of existing records. No fixture and no mail or Google call. */
final class WorkspaceReportsController extends ControllerBase {
  private function shell(string $intro): array {
    return ['#type' => 'container', '#attributes' => ['class' => ['tcl-bureau', 'tcl-report']], '#cache' => ['max-age' => 0],
      'intro' => ['#type' => 'html_tag', '#tag' => 'p', '#value' => $intro, '#attributes' => ['class' => ['tcl-report-intro']]]];
  }
  private function selector(string $route, array $fields): array {
    $html = '<form method="get" class="tcl-report-filters" action="' . Html::escape(Url::fromRoute($route)->toString()) . '">';
    foreach ($fields as $name => [$label, $options, $selected]) {
      $html .= '<label>' . Html::escape($label) . '<select name="' . Html::escape($name) . '">';
      foreach ($options as $value => $text) { $html .= '<option value="' . Html::escape((string) $value) . '"' . ((string) $value === (string) $selected ? ' selected' : '') . '>' . Html::escape($text) . '</option>'; }
      $html .= '</select></label>';
    }
    return ['#markup' => \Drupal\Core\Render\Markup::create($html . '<button type="submit">Afficher</button></form>')];
  }
  public function responses(): array {
    $actor = $this->currentUser();
    if (!EventParticipation::enabled() || !\Drupal::service('tcl_bureau.events')->allowed($actor)) { throw new AccessDeniedHttpException(); }
    $events = \Drupal::service('tcl_bureau.events')->all($actor);
    $build = $this->shell('Choisissez un événement et sa date pour voir les réponses et le nombre de personnes confirmées.');
    if (!$events) { $build['empty'] = ['#plain_text' => 'Aucun événement. Créez votre premier rendez-vous depuis Événements.']; return $build; }
    $query = \Drupal::request()->query;
    $id = (string) $query->get('event', (string) array_key_first($events));
    if (!isset($events[$id])) { $id = (string) array_key_first($events); }
    $event = $events[$id];
    $dates = array_column(EventSchedule::inspect($event)['occurrences'], 'date');
    if (!$dates) { $build['empty'] = ['#plain_text' => 'Cet événement ne possède aucune date disponible.']; return $build; }
    $date = (string) $query->get('date', $dates[0]);
    if (!in_array($date, $dates, TRUE)) { $date = $dates[0]; }
    $options = [];
    foreach ($events as $key => $e) { $options[$key] = $e['title'] . ($e['status'] === 'cancelled' ? ' · Annulé' : ''); }
    $filters = ['all' => 'Toutes les réponses', 'yes' => 'Présents', 'no' => 'Absents', 'maybe' => 'Incertains', 'unknown' => 'Sans réponse', 'reconfirm' => 'À reconfirmer'];
    $filter = (string) $query->get('answer', 'all'); if (!isset($filters[$filter])) { $filter = 'all'; }
    $build['filters'] = $this->selector('tcl_bureau.responses', ['event' => ['Événement', $options, $id], 'date' => ['Date', array_combine($dates, $dates), $date], 'answer' => ['Réponses', $filters, $filter]]);
    $row = EventParticipation::read($actor, $id, $date);
    $totals = EventParticipation::totals($row, (int) $event['revision']);
    $build['stats'] = ['#type' => 'container', '#attributes' => ['class' => ['tcl-stats']]];
    foreach (['people' => 'Personnes confirmées', 'yes' => 'Réponses présentes', 'no' => 'Absents', 'maybe' => 'Incertains', 'unknown' => 'Sans réponse', 'reconfirm' => 'À reconfirmer'] as $key => $label) {
      $build['stats'][$key] = ['#type' => 'container', '#attributes' => ['class' => ['tcl-stat']], 'value' => ['#type' => 'html_tag', '#tag' => 'strong', '#value' => (string) $totals[$key]], 'label' => ['#plain_text' => $label]];
    }
    $build['note'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'Une réponse peut représenter plusieurs personnes. Après une modification de l’événement, les réponses à reconfirmer sont exclues des personnes confirmées.'];
    if ($event['status'] === 'cancelled') { $build['cancelled'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'Événement annulé — les réponses ci-dessous sont conservées à titre historique.']; }
    $rows = [];
    foreach ($row['people'] as $p) {
      $status = $p['answer'] !== 'unknown' && $p['event_revision'] !== $event['revision'] ? 'reconfirm' : $p['answer'];
      if ($filter !== 'all' && $status !== $filter) { continue; }
      $displayName = \Drupal\tcl_bureau\MemberContacts::production() ? $p['name'] : 'Participant de recette ' . (count($rows) + 1);
      $displayEmail = \Drupal\tcl_bureau\MemberContacts::production() ? $p['email'] : 'participant' . (count($rows) + 1) . '@example.invalid';
      $rows[] = [['data' => ['#plain_text' => $displayName]], ['data' => ['#plain_text' => $displayEmail]], $status === 'reconfirm' ? 'À reconfirmer' : EventParticipation::ANSWERS[$status], $status === 'yes' ? $p['count'] : '—'];
    }
    $build['table'] = ['#type' => 'container', '#attributes' => ['class' => ['tcl-table']], 'data' => ['#type' => 'table', '#header' => ['Participant', 'Courriel', 'Réponse', 'Personnes confirmées'], '#rows' => $rows, '#empty' => $row['people'] ? 'Aucune réponse pour ce filtre.' : 'Aucune invitation enregistrée pour cette date.']];
    $build['manage'] = Link::fromTextAndUrl('Gérer les inscriptions', Url::fromRoute('tcl_bureau.event_participation', ['event' => $id], ['query' => ['date' => $date]]))->toRenderable();
    $build['history'] = Link::fromTextAndUrl('Voir les envois associés', Url::fromRoute('tcl_bureau.delivery_history', [], ['query' => ['post' => $row['registration_post'] ?? 0]]))->toRenderable();
    return $build;
  }
  public function history(): array {
    $actor = $this->currentUser(); $repo = \Drupal::service('tcl_bureau.communication');
    if (!$repo->allowed($actor)) { throw new AccessDeniedHttpException(); }
    $posts = [];
    foreach (array_merge($repo->list($actor), $repo->list($actor, TRUE)) as $p) { $posts[(int) $p['id']] = $p; }
    $query = \Drupal::request()->query; $post = (int) $query->get('post', 0);
    $status = (string) $query->get('status', 'all');
    $labels = ['accepted' => 'Accepté par le serveur', 'failed' => 'Échec', 'pending' => 'Résultat à vérifier'];
    if (!isset($labels[$status])) { $status = 'all'; }
    $options = [0 => 'Toutes les communications']; foreach ($posts as $id => $p) { $options[$id] = $p['title']; }
    if (!isset($options[$post])) { $post = 0; }
    $build = $this->shell('Consultez les traces d’envoi disponibles. Un courriel accepté par le serveur ne prouve pas sa réception.');
    $build['filters'] = $this->selector('tcl_bureau.delivery_history', ['post' => ['Communication', $options, $post], 'status' => ['Résultat', ['all' => 'Tous les résultats'] + $labels, $status]]);
    $records = [];
    foreach (\Drupal::keyValue('tcl_bureau.member_mail')->getAll() as $key => $record) {
      if (!preg_match('/^(\d+)\.(\d+)\.[a-f0-9]{64}$/D', (string) $key, $m) || !isset($posts[(int) $m[1]])) { continue; }
      if (($post && (int) $m[1] !== $post) || ($status !== 'all' && ($record['status'] ?? '') !== $status)) { continue; }
      $records[] = $record + ['post' => (int) $m[1], 'revision' => (int) $m[2]];
    }
    usort($records, static fn($a, $b) => ($b['at'] ?? 0) <=> ($a['at'] ?? 0));
    $rows = [];
    foreach ($records as $r) {
      $date = isset($r['at']) ? (new \DateTimeImmutable('@' . $r['at']))->setTimezone(new \DateTimeZone('Europe/Paris'))->format('d/m/Y H:i') : 'Date non conservée';
      $rows[] = [$date, ['data' => ['#plain_text' => $posts[$r['post']]['title']]], $r['revision'], 'Adresse non conservée', $labels[$r['status']] ?? 'Résultat inconnu'];
    }
    $build['count'] = ['#type' => 'html_tag', '#tag' => 'h2', '#value' => count($rows) . ' trace(s) d’envoi'];
    $build['limitations'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'Les anciennes traces conservent la date, la révision et le résultat par destinataire, mais pas son adresse, l’auteur de l’envoi ni le détail des erreurs. Elles ne constituent pas un journal complet des tentatives.'];
    $build['table'] = ['#type' => 'container', '#attributes' => ['class' => ['tcl-table']], 'data' => ['#type' => 'table', '#header' => ['Date · Paris', 'Communication', 'Révision', 'Destinataire', 'Résultat'], '#rows' => $rows, '#empty' => 'Aucune trace d’envoi pour cette sélection.']];
    if (EventParticipation::enabled() && \Drupal::service('tcl_bureau.events')->allowed($actor)) {
      foreach (\Drupal::service('tcl_bureau.events')->all($actor) as $event) {
        foreach (EventSchedule::inspect($event)['occurrences'] as $occurrence) {
          $row = EventParticipation::read($actor, $event['id'], $occurrence['date']);
          if ($post && (int) ($row['registration_post'] ?? 0) === $post) { $build['responses_' . $event['id'] . $occurrence['date']] = Link::fromTextAndUrl('Réponses : ' . $event['title'] . ' · ' . $occurrence['date'], Url::fromRoute('tcl_bureau.responses', [], ['query' => ['event' => $event['id'], 'date' => $occurrence['date']]]))->toRenderable(); }
        }
      }
    }
    return $build;
  }
}
