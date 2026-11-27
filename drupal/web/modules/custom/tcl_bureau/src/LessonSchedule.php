<?php

declare(strict_types=1);
namespace Drupal\tcl_bureau;

/** One original session can be delivered by at most one of its attempts. */
final class LessonSchedule {

  public const ACTIONS = [
    'hold' => 'Confirmer le cours assuré',
    'cancel' => 'Annuler la séance',
    'reschedule' => 'Programmer le rattrapage',
    'close' => 'Clôturer sans rattrapage',
    'reopen' => 'Rouvrir le rattrapage',
  ];

  public static function validDate(string $value): bool {
    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
  }

  private static function scheduleInput(array $input, bool $series): array {
    return [
      'title' => $input['name'] ?? 'Rattrapage',
      'location' => $input['court'],
      'description' => '',
      'start_date' => $input['date'],
      'start_time' => $input['start'],
      'end_date' => $input['date'],
      'end_time' => $input['end'],
      'frequency' => $series ? 'weekly' : 'once',
      'until' => $series ? $input['until'] : '',
      'excluded' => $series ? $input['excluded'] : '',
    ];
  }

  /** Validates season boundaries and materializes the original weekly sessions. */
  public static function creation(array $input, int $now, int $actor): array {
    $errors = [];
    $limits = ['name' => 100, 'coach' => 100, 'court' => 100];
    foreach ($limits as $field => $limit) {
      if ($input[$field] === '' || mb_strlen($input[$field]) > $limit) {
        $errors[$field] = 'Renseignez ce champ (100 caractères maximum).';
      }
    }
    $numbers = ['season' => [2000, 2098], 'goal' => [1, 999], 'baseline' => [0, 999]];
    foreach ($numbers as $field => [$min, $max]) {
      if (!ctype_digit($input[$field]) || (int) $input[$field] < $min || (int) $input[$field] > $max) {
        $errors[$field] = "Saisissez un nombre entier entre $min et $max.";
      }
    }
    if ($errors) {
      return ['errors' => $errors, 'group' => []];
    }
    $season = (int) $input['season'];
    $begins = $season . '-09-01';
    $ends = ($season + 1) . '-08-31';
    foreach (['date', 'until'] as $field) {
      if (!self::validDate($input[$field]) || $input[$field] < $begins || $input[$field] > $ends) {
        $errors[$field] = 'Choisissez une date comprise dans la saison sélectionnée.';
      }
    }
    $parsed = EventSchedule::inspect(self::scheduleInput($input, TRUE));
    $fieldNames = ['start_date' => 'date', 'end_date' => 'end', 'start_time' => 'start', 'end_time' => 'end'];
    foreach ($parsed['errors'] as $field => $error) {
      $errors[$fieldNames[$field] ?? $field] ??= $error;
    }
    $prior = preg_split('/\s+/', $input['prior_cancelled'], -1, PREG_SPLIT_NO_EMPTY);
    $today = self::localDate($now);
    if (count($prior) !== count(array_unique($prior))) {
      $errors['prior_cancelled'] = 'Chaque date doit être unique.';
    }
    foreach ($prior as $date) {
      if (!self::validDate($date) || $date < $begins || $date >= $input['date'] || $date > $today) {
        $errors['prior_cancelled'] = 'Utilisez des dates de la saison, déjà passées ou aujourd’hui, avant la première séance suivie.';
        break;
      }
    }
    if ($errors) {
      return ['errors' => $errors, 'group' => []];
    }
    $sessions = [];
    foreach ($prior as $date) {
      $attempt = ['date' => $date, 'start' => NULL, 'end' => NULL, 'court' => '', 'state' => 'cancelled', 'reason' => 'Annulation reprise du bilan initial.', 'at' => $now, 'by' => $actor];
      $sessions[$date] = ['attempts' => [$attempt], 'decisions' => []];
    }
    foreach ($parsed['occurrences'] as $date) {
      $sessions[$date['date']] = ['attempts' => [self::attempt($date, $input['court'], $now, $actor)], 'decisions' => []];
    }
    ksort($sessions);
    return ['errors' => [], 'group' => [
      'season' => $season, 'season_end' => $ends, 'name' => $input['name'],
      'coach' => $input['coach'], 'court' => $input['court'], 'goal' => (int) $input['goal'],
      'baseline' => (int) $input['baseline'], 'sessions' => $sessions,
    ]];
  }

  private static function attempt(array $date, string $court, int $now, int $actor): array {
    return $date + ['court' => $court, 'state' => 'scheduled', 'reason' => '', 'at' => $now, 'by' => $actor];
  }

  private static function localDate(int $now): string {
    return (new \DateTimeImmutable('@' . $now))->setTimezone(new \DateTimeZone('Europe/Paris'))->format('Y-m-d');
  }

  public static function latest(array $session): array {
    return $session['attempts'][array_key_last($session['attempts'])];
  }

  public static function closed(array $session): bool {
    return ($session['decisions'][array_key_last($session['decisions'])]['action'] ?? '') === 'close';
  }

  public static function available(array $session, int $now): array {
    $current = self::latest($session);
    return match ($current['state']) {
      'held' => [],
      'cancelled' => self::closed($session) ? ['reopen'] : ['reschedule', 'close'],
      'scheduled' => strtotime($current['end']) <= $now ? ['hold', 'cancel'] : ['cancel'],
    };
  }

  public static function label(array $session, int $now): string {
    $current = self::latest($session);
    $makeup = count($session['attempts']) > 1;
    return match ($current['state']) {
      'held' => $makeup ? 'Rattrapage assuré' : 'Cours assuré',
      'cancelled' => self::closed($session) ? 'Clôturé sans rattrapage' : 'À reprogrammer',
      'scheduled' => strtotime($current['end']) <= $now
        ? ($makeup ? 'Rattrapage à confirmer' : 'Cours à confirmer')
        : ($makeup ? 'Rattrapage programmé' : 'Cours programmé'),
    };
  }

  public static function summary(array $group, int $now): array {
    $result = ['held' => $group['baseline'], 'upcoming' => 0, 'pending' => 0, 'makeups' => 0, 'confirmation' => 0, 'closed' => 0];
    foreach ($group['sessions'] as $session) {
      $current = self::latest($session);
      if ($current['state'] === 'held') {
        $result['held']++;
      }
      elseif ($current['state'] === 'cancelled') {
        $result[self::closed($session) ? 'closed' : 'pending']++;
      }
      elseif (strtotime($current['end']) <= $now) {
        $result['confirmation']++;
      }
      else {
        $result[count($session['attempts']) > 1 ? 'makeups' : 'upcoming']++;
      }
    }
    $result['remaining'] = max(0, $group['goal'] - $result['held']);
    return $result;
  }

  public static function change(array $group, string $original, string $action, array $input, int $now, int $actor): array {
    $session = $group['sessions'][$original];
    if (!in_array($action, self::available($session, $now), TRUE)) {
      throw new \InvalidArgumentException('Cette action n’est plus disponible pour la séance.');
    }
    $reason = $input['reason'] ?? '';
    if (in_array($action, ['cancel', 'close', 'reopen'], TRUE) && ($reason === '' || mb_strlen($reason) > 300)) {
      throw new \InvalidArgumentException('Indiquez un motif de 300 caractères maximum.');
    }
    if ($action === 'reschedule') {
      $session['attempts'][] = self::replacement($group, $original, $input, $now, $actor);
    }
    elseif ($action === 'close' || $action === 'reopen') {
      $session['decisions'][] = ['action' => $action, 'reason' => $reason, 'at' => $now, 'by' => $actor];
    }
    else {
      $index = array_key_last($session['attempts']);
      $session['attempts'][$index]['state'] = $action === 'hold' ? 'held' : 'cancelled';
      $session['attempts'][$index]['reason'] = $reason;
      $session['attempts'][$index]['updated_at'] = $now;
      $session['attempts'][$index]['updated_by'] = $actor;
    }
    $group['sessions'][$original] = $session;
    return $group;
  }

  private static function replacement(array $group, string $original, array $input, int $now, int $actor): array {
    if ($input['court'] === '' || mb_strlen($input['court']) > 100) {
      throw new \InvalidArgumentException('Renseignez le court du rattrapage (100 caractères maximum).');
    }
    $schedule = EventSchedule::inspect(self::scheduleInput($input, FALSE));
    if ($schedule['errors']) {
      throw new \InvalidArgumentException(reset($schedule['errors']));
    }
    $replacement = $schedule['occurrences'][0];
    $start = strtotime($replacement['start']);
    $end = strtotime($replacement['end']);
    $initial = $group['sessions'][$original]['attempts'][0];
    if ($start <= $now || $replacement['date'] > $group['season_end']
      || ($initial['end'] ? $start < strtotime($initial['end']) : $replacement['date'] <= $original)) {
      throw new \InvalidArgumentException('Choisissez un créneau à venir, après la séance initiale et dans la même saison.');
    }
    foreach ($group['sessions'] as $date => $session) {
      if ($date === $original) {
        continue;
      }
      $other = self::latest($session);
      if ($other['state'] !== 'cancelled' && $start < strtotime($other['end']) && $end > strtotime($other['start'])) {
        throw new \InvalidArgumentException('Le créneau chevauche une autre séance de ce groupe.');
      }
    }
    return self::attempt($replacement, $input['court'], $now, $actor);
  }

}
