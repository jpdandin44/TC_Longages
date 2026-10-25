<?php

declare(strict_types=1);
namespace Drupal\tcl_bureau;

/** Bounded wall-clock recurrences in Europe/Paris, independent of any transport. */
final class EventSchedule {
  public const FREQUENCIES = ['once' => 'Une seule fois', 'daily' => 'Tous les jours', 'weekly' => 'Toutes les semaines', 'fortnightly' => 'Toutes les deux semaines', 'monthly' => 'Tous les mois, au même jour du mois'];
  public const FIELDS = ['title', 'location', 'description', 'start_date', 'start_time', 'end_date', 'end_time', 'frequency', 'until', 'excluded'];

  private static function dateTime(string $date, string $time): ?\DateTimeImmutable {
    if (!preg_match('/^20\d{2}-\d{2}-\d{2}$/D', $date) || !preg_match('/^\d{2}:\d{2}$/D', $time)) { return NULL; }
    $text = $date . ' ' . $time;
    $zone = new \DateTimeZone('Europe/Paris');
    $value = \DateTimeImmutable::createFromFormat('!Y-m-d H:i', $text, $zone);
    if (!$value || $value->format('Y-m-d H:i') !== $text) { return NULL; }
    // Reject the repeated hour at the autumn transition, rather than guessing.
    $naive = \DateTimeImmutable::createFromFormat('!Y-m-d H:i', $text, new \DateTimeZone('UTC'))->getTimestamp();
    $transitions = $zone->getTransitions($value->getTimestamp() - 86400, $value->getTimestamp() + 86400);
    $offset = $transitions[0]['offset'];
    foreach (array_slice($transitions, 1) as $transition) {
      if ($transition['offset'] < $offset && $naive >= $transition['ts'] + $transition['offset'] && $naive < $transition['ts'] + $offset) { return NULL; }
      $offset = $transition['offset'];
    }
    return $value;
  }

  /** Returns all occurrences, or field errors. End date of recurrence is inclusive. */
  public static function inspect(array $input): array {
    $errors = [];
    foreach (self::FIELDS as $field) {
      if (!is_string($input[$field] ?? NULL)) { $errors[$field] = 'Valeur invalide.'; }
    }
    if ($errors) { return ['errors' => $errors, 'occurrences' => []]; }
    foreach (['title' => 120, 'location' => 200, 'description' => 3000, 'excluded' => 5000] as $field => $limit) {
      if (mb_strlen($input[$field]) > $limit) { $errors[$field] = 'Texte trop long.'; }
    }
    if (trim($input['title']) === '') { $errors['title'] = 'Indiquez un titre.'; }
    if (!isset(self::FREQUENCIES[$input['frequency']])) { $errors['frequency'] = 'Choisissez une répétition proposée.'; }
    $start = self::dateTime($input['start_date'], $input['start_time']);
    $end = self::dateTime($input['end_date'], $input['end_time']);
    if (!$start) { $errors['start_time'] = 'Date ou heure de début invalide ou ambiguë (changement d’heure).'; }
    if (!$end) { $errors['end_time'] = 'Date ou heure de fin invalide ou ambiguë (changement d’heure).'; }
    if ($start && $end && ($end <= $start || $end > $start->modify('+7 days'))) { $errors['end_date'] = 'La fin doit suivre le début, dans les sept jours.'; }
    $until = $input['frequency'] === 'once' ? $start : self::dateTime($input['until'], '23:59');
    if (!$until || ($start && ($until < $start || $until > $start->modify('+1 year')->setTime(23, 59)))) { $errors['until'] = 'Indiquez une fin de répétition comprise entre le début et un an plus tard.'; }
    if ($errors) { return ['errors' => $errors, 'occurrences' => []]; }
    $excluded = preg_split('/\s+/', trim($input['excluded']), -1, PREG_SPLIT_NO_EMPTY);
    if (count($excluded) !== count(array_unique($excluded))) { $errors['excluded'] = 'Chaque date exclue doit être unique.'; }
    $days = (int) $start->setTime(12, 0)->diff($end->setTime(12, 0))->format('%a');
    $dates = [];
    $occurrences = [];
    // At most 367 daily occurrences including both ends of a leap-year window.
    for ($n = 0; $n < 368; $n++) {
      if ($input['frequency'] === 'once' && $n > 0) { break; }
      if ($input['frequency'] === 'monthly') {
        $month = $start->modify('first day of this month')->setTime(12, 0)->modify('+' . $n . ' months');
        if ((int) $start->format('d') > (int) $month->format('t')) { continue; }
        $day = $month->setDate((int) $month->format('Y'), (int) $month->format('m'), (int) $start->format('d'));
      }
      else {
        $step = ['once' => 1, 'daily' => 1, 'weekly' => 7, 'fortnightly' => 14][$input['frequency']];
        $day = $start->setTime(12, 0)->modify('+' . ($n * $step) . ' days');
      }
      if ($day->format('Y-m-d') > $until->format('Y-m-d')) { break; }
      $date = $day->format('Y-m-d');
      $dates[] = $date;
      if (in_array($date, $excluded, TRUE)) { continue; }
      $from = self::dateTime($date, $input['start_time']);
      $to = self::dateTime($day->modify('+' . $days . ' days')->format('Y-m-d'), $input['end_time']);
      if (!$from || !$to || $to <= $from) { $errors['excluded'] = 'Un horaire est invalide ou ambigu le ' . $date . '. Excluez cette date ou modifiez les horaires.'; continue; }
      $occurrences[] = ['date' => $date, 'start' => $from->format(DATE_ATOM), 'end' => $to->format(DATE_ATOM)];
    }
    if (array_diff($excluded, $dates)) { $errors['excluded'] = 'Les dates exclues doivent correspondre à une occurrence de la série (AAAA-MM-JJ).'; }
    if (!$occurrences && !$errors) { $errors['excluded'] = 'Conservez au moins une occurrence ; utilisez Annulé pour annuler toute la série.'; }
    return ['errors' => $errors, 'occurrences' => $errors ? [] : $occurrences];
  }
}
