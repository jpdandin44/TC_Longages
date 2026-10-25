<?php
declare(strict_types=1);
require dirname(__DIR__) . '/drupal/web/modules/custom/tcl_bureau/src/EventSchedule.php';
use Drupal\tcl_bureau\EventSchedule;
$checks = [];
function verifySchedule(string $label, bool $ok): void {
  global $checks; $checks[] = ['label' => $label, 'passed' => $ok];
  if (!$ok) { throw new RuntimeException($label); }
}
$base = ['title' => 'Recette tennis', 'location' => 'Court fictif', 'description' => '', 'start_date' => '2027-03-21', 'start_time' => '10:00', 'end_date' => '2027-03-21', 'end_time' => '11:30', 'frequency' => 'once', 'until' => '', 'excluded' => ''];
$r = EventSchedule::inspect($base);
verifySchedule('Single occurrence has exact Paris offset', !$r['errors'] && $r['occurrences'] === [['date' => '2027-03-21', 'start' => '2027-03-21T10:00:00+01:00', 'end' => '2027-03-21T11:30:00+01:00']]);
$r = EventSchedule::inspect(array_replace($base, ['frequency' => 'weekly', 'until' => '2027-04-04']));
verifySchedule('Weekly recurrence preserves wall time across summer transition', count($r['occurrences']) === 3 && $r['occurrences'][1]['start'] === '2027-03-28T10:00:00+02:00');
$r = EventSchedule::inspect(array_replace($base, ['frequency' => 'fortnightly', 'until' => '2027-04-04']));
verifySchedule('Fortnightly recurrence includes final date', count($r['occurrences']) === 2 && $r['occurrences'][1]['date'] === '2027-04-04');
$r = EventSchedule::inspect(array_replace($base, ['start_date' => '2028-01-31', 'end_date' => '2028-01-31', 'frequency' => 'monthly', 'until' => '2028-04-30']));
verifySchedule('Monthly recurrence skips missing day rather than drifting', array_column($r['occurrences'], 'date') === ['2028-01-31', '2028-03-31']);
$r = EventSchedule::inspect(array_replace($base, ['start_date' => '2028-02-28', 'end_date' => '2028-02-28', 'frequency' => 'daily', 'until' => '2028-03-01', 'excluded' => '2028-02-29']));
verifySchedule('Leap day can be excluded', array_column($r['occurrences'], 'date') === ['2028-02-28', '2028-03-01']);
foreach ([['end_time' => '09:00'], ['start_date' => '2027-02-30'], ['frequency' => 'daily', 'until' => '2029-01-01'], ['frequency' => 'weekly', 'until' => ''], ['title' => ['bad']], ['excluded' => '2027-03-22'], ['excluded' => '2027-03-21'], ['frequency' => 'weekly', 'until' => '2027-04-04', 'excluded' => "2027-03-28\n2027-03-28"], ['frequency' => 'injected']] as $i => $invalid) {
  verifySchedule('Invalid input rejected ' . $i, (bool) EventSchedule::inspect(array_replace($base, $invalid))['errors']);
}
foreach (['2027-03-28', '2027-10-31'] as $date) {
  verifySchedule('Nonexistent or ambiguous Paris hour rejected ' . $date, (bool) EventSchedule::inspect(array_replace($base, ['start_date' => $date, 'end_date' => $date, 'start_time' => '02:30', 'end_time' => '04:00']))['errors']);
}
$r = EventSchedule::inspect(array_replace($base, ['start_time' => '02:30', 'end_time' => '04:00', 'frequency' => 'weekly', 'until' => '2027-04-04', 'excluded' => '2027-03-28']));
verifySchedule('Exclusion resolves recurrence transition conflict', !$r['errors'] && count($r['occurrences']) === 2);
$r = EventSchedule::inspect(array_replace($base, ['start_date' => '2028-01-01', 'end_date' => '2028-01-01', 'frequency' => 'daily', 'until' => '2029-01-01']));
verifySchedule('One-year leap window is bounded and inclusive', count($r['occurrences']) === 367);
$r = EventSchedule::inspect(array_replace($base, ['end_date' => '2027-03-22', 'frequency' => 'weekly', 'until' => '2027-03-28']));
verifySchedule('Multi-day recurrence retains end-day offset', $r['occurrences'][1]['end'] === '2027-03-29T11:30:00+02:00');
echo count($checks) . " event schedule checks passed.\n";
