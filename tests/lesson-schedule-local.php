<?php

declare(strict_types=1);

use Drupal\tcl_bureau\LessonSchedule;

require dirname(__DIR__) . '/drupal/web/modules/custom/tcl_bureau/src/EventSchedule.php';
require dirname(__DIR__) . '/drupal/web/modules/custom/tcl_bureau/src/LessonSchedule.php';

$checks = 0;
function checkLesson(bool $result, string $label): void {
  if (!$result) {
    throw new RuntimeException($label);
  }
  $GLOBALS['checks']++;
}

function rejectsLesson(callable $operation, string $message): void {
  try {
    $operation();
  }
  catch (InvalidArgumentException $error) {
    checkLesson(str_contains($error->getMessage(), $message), $message);
    return;
  }
  throw new RuntimeException('Missing rejection: ' . $message);
}

$now = strtotime('2026-11-27T12:00:00+01:00');
$source = [
  'name' => 'Adultes du vendredi', 'coach' => 'Enseignant de recette', 'court' => 'Court 1',
  'season' => '2026', 'goal' => '24', 'baseline' => '8',
  'date' => '2026-11-27', 'until' => '2026-12-18', 'start' => '18:30', 'end' => '19:30',
  'excluded' => '2026-12-11', 'prior_cancelled' => '2026-11-20',
];
$prepared = LessonSchedule::creation($source, $now, 9);
checkLesson(!$prepared['errors'], 'Season group created');
$group = $prepared['group'];
checkLesson(array_keys($group['sessions']) === ['2026-11-20', '2026-11-27', '2026-12-04', '2026-12-18'], 'Prior cancellations and weekly schedule with an exclusion');
checkLesson(LessonSchedule::summary($group, $now) === ['held' => 8, 'upcoming' => 3, 'pending' => 1, 'makeups' => 0, 'confirmation' => 0, 'closed' => 0, 'remaining' => 16], 'Initial count from the prior report');
checkLesson(isset(LessonSchedule::creation(array_replace($source, ['until' => '2027-09-01']), $now, 9)['errors']['until']), 'Season bounds');
checkLesson(isset(LessonSchedule::creation(array_replace($source, ['prior_cancelled' => "2026-11-20\n2026-11-20"]), $now, 9)['errors']['prior_cancelled']), 'Duplicate old cancellation rejected');
checkLesson(str_contains(LessonSchedule::label($group['sessions']['2026-11-27'], strtotime('2026-11-28T00:00:00+01:00')), 'à confirmer'), 'Past regular courses need confirmation');

$original = '2026-11-27';
$slot = ['date' => '2026-11-29', 'start' => '17:00', 'end' => '18:00', 'court' => 'Court 2'];
rejectsLesson(fn() => LessonSchedule::change($group, $original, 'hold', [], $now, 9), 'plus disponible');
rejectsLesson(fn() => LessonSchedule::change($group, $original, 'cancel', ['reason' => ''], $now, 9), 'motif');
$group = LessonSchedule::change($group, $original, 'cancel', ['reason' => 'Pluie'], $now, 9);
checkLesson(LessonSchedule::summary($group, $now)['pending'] === 2 && LessonSchedule::summary($group, $now)['upcoming'] === 2, 'Cancelling leaves other sessions unchanged');
rejectsLesson(fn() => LessonSchedule::change($group, $original, 'reschedule', array_replace($slot, ['date' => '2026-12-04', 'start' => '19:00', 'end' => '20:00']), $now, 9), 'chevauche');
rejectsLesson(fn() => LessonSchedule::change($group, $original, 'reschedule', array_replace($slot, ['date' => '2027-09-03']), $now, 9), 'même saison');
$group = LessonSchedule::change($group, $original, 'reschedule', $slot, $now, 9);
checkLesson(LessonSchedule::summary($group, $now)['held'] === 8 && LessonSchedule::summary($group, $now)['makeups'] === 1, 'Scheduling does not count as delivered');
rejectsLesson(fn() => LessonSchedule::change($group, $original, 'reschedule', $slot, $now, 9), 'plus disponible');
$group = LessonSchedule::change($group, $original, 'cancel', ['reason' => 'Court indisponible'], $now, 10);
checkLesson(count($group['sessions'][$original]['attempts']) === 2 && LessonSchedule::summary($group, $now)['pending'] === 2, 'Cancelled makeup returns exactly one item to the queue');
$group = LessonSchedule::change($group, $original, 'reschedule', array_replace($slot, ['date' => '2026-12-01']), $now, 9);
$later = strtotime('2026-12-02T12:00:00+01:00');
checkLesson(LessonSchedule::summary($group, $later)['confirmation'] === 1 && LessonSchedule::summary($group, $later)['makeups'] === 0, 'Past makeup appears in outstanding confirmations');
$group = LessonSchedule::change($group, $original, 'hold', [], $later, 9);
$report = LessonSchedule::summary($group, $later);
checkLesson($report['held'] === 9 && $report['pending'] === 1 && $report['makeups'] === 0 && $report['remaining'] === 15, 'Completed second makeup counts exactly once');
checkLesson(array_column($group['sessions'][$original]['attempts'], 'state') === ['cancelled', 'cancelled', 'held'], 'Original and makeup history preserved');
rejectsLesson(fn() => LessonSchedule::change($group, $original, 'hold', [], $later, 9), 'plus disponible');
$group = LessonSchedule::change($group, '2026-11-20', 'close', ['reason' => 'Décision du Bureau'], $later, 9);
checkLesson(LessonSchedule::summary($group, $later)['closed'] === 1 && LessonSchedule::summary($group, $later)['remaining'] === 15, 'Closing leaves the planned target visible');
$group = LessonSchedule::change($group, '2026-11-20', 'reopen', ['reason' => 'Créneau possible'], $later, 9);
checkLesson(LessonSchedule::summary($group, $later)['pending'] === 1 && count($group['sessions']['2026-11-20']['decisions']) === 2, 'Reopened decision is audited');
echo $checks . " lesson schedule checks passed.\n";
