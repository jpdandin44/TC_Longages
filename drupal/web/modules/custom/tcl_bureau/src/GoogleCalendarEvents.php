<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

/** Site-authored series mirrored to the one app-created recipe calendar. */
final class GoogleCalendarEvents {
  public static function payload(array $row): array {
    if (EventSchedule::inspect($row)['errors']) { throw new \InvalidArgumentException('Événement invalide.'); }
    $zone = new \DateTimeZone('Europe/Paris');
    $date = static fn(string $d, string $t): \DateTimeImmutable => new \DateTimeImmutable($d . ' ' . $t, $zone);
    $recurrence = [];
    if ($row['frequency'] !== 'once') {
      $rule = ['daily' => 'DAILY', 'weekly' => 'WEEKLY', 'fortnightly' => 'WEEKLY;INTERVAL=2', 'monthly' => 'MONTHLY'][$row['frequency']];
      $until = $date($row['until'], '23:59:59')->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z');
      $recurrence[] = 'RRULE:FREQ=' . $rule . ';UNTIL=' . $until;
      $excluded = preg_split('/\s+/', trim($row['excluded']), -1, PREG_SPLIT_NO_EMPTY);
      if ($excluded) { $recurrence[] = 'EXDATE;TZID=Europe/Paris:' . implode(',', array_map(static fn($d) => $date($d, $row['start_time'])->format('Ymd\THis'), $excluded)); }
    }
    // Cancellation stays reversible and visible; no Google event deletion.
    $cancelled = $row['status'] === 'cancelled';
    return [
      'summary' => ($cancelled ? '[ANNULÉ] ' : '') . $row['title'],
      'description' => "RECETTE PRÉPRODUCTION — données fictives.\n" . $row['description'],
      'location' => $row['location'],
      'start' => ['dateTime' => $date($row['start_date'], $row['start_time'])->format(DATE_ATOM), 'timeZone' => 'Europe/Paris'],
      'end' => ['dateTime' => $date($row['end_date'], $row['end_time'])->format(DATE_ATOM), 'timeZone' => 'Europe/Paris'],
      'recurrence' => $recurrence, 'status' => 'confirmed', 'transparency' => $cancelled ? 'transparent' : 'opaque',
      'reminders' => ['useDefault' => FALSE],
      'extendedProperties' => ['private' => ['tclRecipeId' => $row['id'], 'tclOrigin' => 'preprod-v2']],
    ];
  }
  public static function digest(array $event): string {
    $normalized = [];
    foreach (['summary','description','location','status','transparency'] as $key) { $normalized[$key] = $event[$key] ?? (['status' => 'confirmed','transparency' => 'opaque'][$key] ?? ''); }
    foreach (['start','end'] as $key) {
      $normalized[$key] = ['time' => isset($event[$key]['dateTime']) ? (new \DateTimeImmutable($event[$key]['dateTime']))->getTimestamp() : NULL, 'zone' => $event[$key]['timeZone'] ?? ''];
    }
    $normalized['recurrence'] = $event['recurrence'] ?? []; sort($normalized['recurrence']);
    $normalized['attendees'] = $event['attendees'] ?? [];
    $normalized['attachments'] = $event['attachments'] ?? [];
    return hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));
  }
  public static function save(array $row, ?array $old): array {
    $target = GoogleCalendarConnection::target();
    if (!$target) { throw new \RuntimeException('Calendrier de recette non créé.'); }
    if (!preg_match('/^[a-f0-9]{32}$/D', $row['id'] ?? '') || !preg_match('/^[a-zA-Z0-9_-]+@group\.calendar\.google\.com$/D', $target['id'] ?? '')) { throw new \RuntimeException('Cible agenda non autorisée.'); }
    if (!empty($old['google']) && $old['google']['calendar'] !== $target['id']) { throw new \RuntimeException('Cet événement appartient à une autre cible Google.'); }
    try {
      $token = GoogleCalendarConnection::token();
      $client = \Drupal::httpClient(); $options = ['timeout' => 20, 'allow_redirects' => FALSE, 'http_errors' => FALSE, 'headers' => ['Authorization' => 'Bearer ' . $token]];
      $base = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($target['id']);
      $response = $client->get($base, $options);
      $calendar = json_decode((string) $response->getBody(), TRUE, 32, JSON_THROW_ON_ERROR);
      if ($response->getStatusCode() !== 200 || ($calendar['id'] ?? '') !== $target['id'] || ($calendar['summary'] ?? '') !== GoogleCalendarConnection::NAME || ($calendar['timeZone'] ?? '') !== 'Europe/Paris' || !str_contains($calendar['description'] ?? '', $target['marker'])) { throw new \RuntimeException('Cible Google non reconnue. Aucune écriture effectuée.'); }
      $id = 'tcl' . $row['id']; $url = $base . '/events/' . $id;
      $payload = self::payload($row); $digest = self::digest($payload);
      $response = $client->get($url, $options); $code = $response->getStatusCode();
      $current = json_decode((string) $response->getBody(), TRUE, 64, JSON_THROW_ON_ERROR);
      if ($code === 404 && empty($old['google'])) {
        $response = $client->post($base . '/events?sendUpdates=none', $options + ['json' => ['id' => $id] + $payload]);
      } elseif ($code === 200) {
        if (($current['extendedProperties']['private']['tclRecipeId'] ?? '') !== $row['id'] || ($current['extendedProperties']['private']['tclOrigin'] ?? '') !== 'preprod-v2') { throw new \RuntimeException('Événement Google non reconnu. Aucune modification effectuée.'); }
        $currentDigest = self::digest($current);
        if ($currentDigest === $digest) { return ['calendar' => $target['id'], 'id' => $id, 'digest' => $digest, 'at' => time()]; }
        if (empty($old['google']['digest']) || !hash_equals($old['google']['digest'], $currentDigest)) { throw new \RuntimeException('Cet événement a changé dans Google. Aucune donnée écrasée ; demandez une vérification au Bureau.'); }
        if (empty($current['etag'])) { throw new \RuntimeException('Version Google manquante.'); }
        $options['headers']['If-Match'] = $current['etag'];
        $response = $client->patch($url . '?sendUpdates=none', $options + ['json' => $payload]);
      } else { throw new \RuntimeException('Événement Google inaccessible ou supprimé. Reconnectez Google ou faites vérifier la cible.'); }
      $saved = json_decode((string) $response->getBody(), TRUE, 64, JSON_THROW_ON_ERROR);
      if ($response->getStatusCode() !== 200 || ($saved['id'] ?? '') !== $id || self::digest($saved) !== $digest) { throw new \RuntimeException('Enregistrement Google non confirmé. Réessayez la même saisie ; aucun doublon ne sera créé.'); }
      return ['calendar' => $target['id'], 'id' => $id, 'digest' => $digest, 'at' => time()];
    } catch (\GuzzleHttp\Exception\GuzzleException | \JsonException $error) { throw new \RuntimeException('Google Agenda indisponible. Gardez cette saisie et réessayez ; aucun succès n’est confirmé.'); }
  }
}
