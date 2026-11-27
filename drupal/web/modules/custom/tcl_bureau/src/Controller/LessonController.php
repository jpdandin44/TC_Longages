<?php

declare(strict_types=1);
namespace Drupal\tcl_bureau\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\tcl_bureau\LessonRepository;
use Drupal\tcl_bureau\LessonSchedule;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class LessonController extends ControllerBase {

  public function __construct(private LessonRepository $lessons) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('tcl_bureau.lessons'));
  }

  public function access(AccountInterface $account): AccessResult {
    return AccessResult::allowedIf($this->lessons->allowed($account))->setCacheMaxAge(0);
  }

  private static function page(): array {
    return [
      '#type' => 'container', '#attributes' => ['class' => ['tcl-bureau']],
      '#attached' => ['library' => ['tcl_bureau/bureau']], '#cache' => ['max-age' => 0],
    ];
  }

  private static function link(string $label, string $route, array $params = [], array $options = []): array {
    return Link::fromTextAndUrl($label, Url::fromRoute($route, $params, $options))->toRenderable();
  }

  private static function groupLink(array $group): array {
    return self::link($group['name'], 'tcl_bureau.lesson_group', ['group' => $group['id']]);
  }

  private static function paragraph(string $text): array {
    return ['#type' => 'html_tag', '#tag' => 'p', 'text' => ['#plain_text' => $text]];
  }

  private static function table(array $header, array $rows, string $empty): array {
    return [
      '#type' => 'container', '#attributes' => ['class' => ['tcl-table']],
      'table' => ['#type' => 'table', '#header' => $header, '#rows' => $rows, '#empty' => $empty],
    ];
  }

  private static function dateLabel(string $date): string {
    return (new \DateTimeImmutable($date))->format('d/m/Y');
  }

  private static function slot(array $attempt): string {
    if ($attempt['start'] === NULL) {
      return self::dateLabel($attempt['date']);
    }
    $start = new \DateTimeImmutable($attempt['start']);
    $end = new \DateTimeImmutable($attempt['end']);
    return $start->format('d/m/Y H:i') . ' – ' . $end->format('H:i') . ' · ' . $attempt['court'];
  }

  public function overview(): array {
    $groups = $this->lessons->all($this->currentUser());
    $now = $this->lessons->now();
    $seasons = array_values(array_unique(array_column($groups, 'season')));
    $season = \Drupal::request()->query->get('saison', '');
    $build = self::page();
    $build['add'] = self::link('Créer un groupe de cours', 'tcl_bureau.lesson_add');
    $build['seasons'] = ['#theme' => 'item_list', '#title' => 'Saisons', '#items' => [
      self::link('Toutes les saisons', 'tcl_bureau.lessons'),
    ]];
    $rows = $pending = [];
    foreach ($seasons as $year) {
      $build['seasons']['#items'][] = self::link($year . '–' . ($year + 1), 'tcl_bureau.lessons', [], ['query' => ['saison' => $year]]);
    }
    foreach ($groups as $group) {
      if ($season !== '' && (string) $group['season'] !== $season) {
        continue;
      }
      $summary = LessonSchedule::summary($group, $now);
      $rows[] = [
        ['data' => self::groupLink($group)], $group['season'] . '–' . ($group['season'] + 1),
        $group['goal'], $summary['held'], $summary['remaining'], $summary['upcoming'],
        $summary['confirmation'], $summary['pending'], $summary['makeups'], $summary['closed'],
      ];
      foreach ($group['sessions'] as $date => $session) {
        $current = LessonSchedule::latest($session);
        if ($current['state'] !== 'cancelled' || LessonSchedule::closed($session)) {
          continue;
        }
        $params = ['group' => $group['id'], 'date' => $date];
        $pending[] = [
          ['data' => self::groupLink($group)], self::dateLabel($date),
          ['data' => ['#plain_text' => self::slot($current)]], ['data' => ['#plain_text' => $current['reason']]],
          ['data' => self::link('Programmer le rattrapage', 'tcl_bureau.lesson_action', $params + ['action' => 'reschedule'])],
        ];
      }
    }
    $build['groups'] = self::table(
      ['Groupe', 'Saison', 'Objectif', 'Assurés', 'Restants', 'À venir', 'À confirmer', 'À reprogrammer', 'Rattrapages datés', 'Clôturés'],
      $rows, 'Aucun groupe pour cette saison.',
    );
    $build['pending_title'] = ['#type' => 'html_tag', '#tag' => 'h2', '#value' => 'Rattrapages à programmer'];
    $build['pending'] = self::table(['Groupe', 'Séance initiale', 'Dernière annulation', 'Motif', 'Action'], $pending, 'Aucun rattrapage à programmer.');
    return $build;
  }

  public function group(string $group): array {
    $row = $this->lessons->get($this->currentUser(), $group);
    $now = $this->lessons->now();
    $summary = LessonSchedule::summary($row, $now);
    $build = self::page();
    $build['back'] = self::link('Tous les groupes', 'tcl_bureau.lessons');
    $build['label'] = ['#type' => 'html_tag', '#tag' => 'h2', 'text' => ['#plain_text' => $row['name']]];
    $build['group'] = self::paragraph($row['season'] . '–' . ($row['season'] + 1) . ' · Enseignant : ' . $row['coach'] . ' · Court habituel : ' . $row['court']);
    $build['progress'] = self::paragraph($summary['held'] . ' cours assurés sur ' . $row['goal'] . ' · ' . $summary['remaining'] . ' restants · ' . $row['baseline'] . ' repris dans le bilan initial.');
    $rows = [];
    foreach ($row['sessions'] as $date => $session) {
      $latest = LessonSchedule::latest($session);
      $rows[] = [
        self::dateLabel($date), ['data' => ['#plain_text' => self::slot($latest)]],
        LessonSchedule::label($session, $now),
        ['data' => self::link('Ouvrir', 'tcl_bureau.lesson_session', ['group' => $group, 'date' => $date])],
      ];
    }
    $build['sessions'] = self::table(['Date initiale', 'Dernier créneau', 'État', 'Détail'], $rows, 'Aucune séance.');
    return $build;
  }

  public function session(string $group, string $date): array {
    $row = $this->lessons->get($this->currentUser(), $group);
    $session = $row['sessions'][$date] ?? throw new NotFoundHttpException();
    $now = $this->lessons->now();
    $build = self::page();
    $build['back'] = self::link('Retour au groupe', 'tcl_bureau.lesson_group', ['group' => $group]);
    $build['label'] = ['#type' => 'html_tag', '#tag' => 'h2', 'text' => ['#plain_text' => $row['name'] . ' · ' . self::dateLabel($date)]];
    $build['status'] = self::paragraph(LessonSchedule::label($session, $now));
    $actions = [];
    foreach (LessonSchedule::available($session, $now) as $action) {
      $actions[] = self::link(LessonSchedule::ACTIONS[$action], 'tcl_bureau.lesson_action', ['group' => $group, 'date' => $date, 'action' => $action]);
    }
    if ($actions) {
      $build['actions'] = ['#theme' => 'item_list', '#items' => $actions];
    }
    $rows = [];
    foreach ($session['attempts'] as $index => $attempt) {
      $rows[] = [
        $index === 0 ? 'Initiale' : 'Rattrapage ' . $index,
        ['data' => ['#plain_text' => self::slot($attempt)]],
        ['scheduled' => 'Programmé', 'held' => 'Assuré', 'cancelled' => 'Annulé'][$attempt['state']],
        ['data' => ['#plain_text' => $attempt['reason']]],
      ];
    }
    $build['history'] = self::table(['Séance', 'Créneau', 'État', 'Motif'], $rows, 'Aucun historique.');
    $decisions = [];
    foreach ($session['decisions'] as $decision) {
      $dateLabel = (new \DateTimeImmutable('@' . $decision['at']))->setTimezone(new \DateTimeZone('Europe/Paris'))->format('d/m/Y H:i');
      $decisions[] = [$dateLabel, LessonSchedule::ACTIONS[$decision['action']], ['data' => ['#plain_text' => $decision['reason']]]];
    }
    if ($decisions) {
      $build['decisions'] = self::table(['Date de la décision', 'Décision', 'Motif'], $decisions, '');
    }
    $latest = LessonSchedule::latest($session);
    if ($latest['state'] === 'cancelled' || ($latest['state'] === 'scheduled' && count($session['attempts']) > 1)) {
      $message = $latest['state'] === 'cancelled'
        ? 'Le cours du groupe ' . $row['name'] . ' prévu le ' . self::slot($latest) . ' est annulé.'
        : 'Le cours du groupe ' . $row['name'] . ' initialement prévu le ' . self::dateLabel($date) . ' est reprogrammé le ' . self::slot($latest) . '.';
      $build['message'] = [
        '#type' => 'details', '#title' => 'Message à transmettre au groupe',
        'help' => self::paragraph('Vérifiez les destinataires du groupe, puis copiez le texte dans votre canal habituel.'),
        'body' => ['#type' => 'textarea', '#title' => 'Texte à copier', '#rows' => 5,
          '#value' => "Bonjour,\n\n" . $message . "\n\nLe TC Longages",
          '#attributes' => ['readonly' => 'readonly']],
      ];
    }
    return $build;
  }

}
