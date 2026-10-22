<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** Private, occurrence-scoped recipe responses; independent of competitions. */
final class EventParticipation {
  public const ANSWERS = ['unknown' => 'Sans réponse', 'yes' => 'Je participe', 'no' => 'Je ne participe pas', 'maybe' => 'Je ne sais pas encore'];
  private static function guard(): void {
    if (!SeasonCatalogue::recipeEnabled()) { throw new AccessDeniedHttpException(); }
  }
  private static function store() { return \Drupal::keyValue('tcl_bureau.event_participation'); }
  private static function event(string $id, string $date): array {
    self::guard();
    $event = \Drupal::keyValue('tcl_bureau.recipe_events')->get($id);
    if (!$event || $event['status'] !== 'scheduled' || !in_array($date, array_column(EventSchedule::inspect($event)['occurrences'], 'date'), TRUE)) { throw new AccessDeniedHttpException('Événement annulé ou date indisponible.'); }
    return $event;
  }
  public static function manage(AccountInterface $actor, string $id): array {
    return \Drupal::service('tcl_bureau.events')->get($actor, $id);
  }
  public static function read(AccountInterface $actor, string $id, string $date): array {
    self::manage($actor, $id);
    return self::store()->get($id . '.' . $date, ['revision' => 0, 'open' => FALSE, 'deadline' => '', 'people' => []]);
  }
  private static function write(string $id, string $date, callable $operation) {
    $key = $id . '.' . $date;
    $lock = \Drupal::lock();
    // Event changes and replies cannot interleave.
    if (!$lock->acquire('tcl_bureau.recipe_events.write', 30)) { throw new ConflictHttpException('Une modification est en cours. Réessayez.'); }
    try {
      $event = self::event($id, $date);
      $row = self::store()->get($key, ['revision' => 0, 'open' => FALSE, 'deadline' => '', 'people' => []]);
      $result = $operation($row, $event);
      $row['revision']++;
      self::store()->set($key, $row);
      return $result;
    } finally { $lock->release('tcl_bureau.recipe_events.write'); }
  }
  public static function configure(AccountInterface $actor, string $id, string $date, int $revision, bool $open, string $deadline, ?int $registrationPost = NULL): void {
    self::manage($actor, $id);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $deadline) || (\DateTimeImmutable::createFromFormat('!Y-m-d', $deadline) ?: new \DateTimeImmutable())->format('Y-m-d') !== $deadline || $deadline > $date) { throw new \InvalidArgumentException('La date limite doit être valide et ne pas dépasser la date de l’événement.'); }
    self::write($id, $date, static function (&$row) use ($revision, $open, $deadline, $registrationPost): void {
      if ($row['revision'] !== $revision) { throw new ConflictHttpException('Le suivi a changé. Rechargez la page.'); }
      $row['open'] = $open; $row['deadline'] = $deadline;
      if ($registrationPost !== NULL) {
        if ($registrationPost !== 0 && !\Drupal::service('tcl_bureau.communication')->published($registrationPost)) { throw new \InvalidArgumentException('Choisissez une actualité publiée sur le site.'); }
        $row['registration_post'] = $registrationPost;
      }
    });
  }
  public static function invite(AccountInterface $actor, string $id, string $date, string $name, string $email): string {
    self::manage($actor, $id);
    return self::issue($id, $date, $name, $email);
  }
  private static function issue(string $id, string $date, string $name, string $email): string {
    $name = trim($name); $email = strtolower(trim($email));
    if ($name === '' || mb_strlen($name) > 100 || !RecipeMail::inputAllowed($email)) { throw new \InvalidArgumentException('Nom fictif et adresse de recette valides requis.'); }
    return self::write($id, $date, static function (&$row) use ($id, $date, $name, $email): string {
      if (count($row['people']) >= 500 && !isset($row['people'][hash('sha256', $email)])) { throw new \InvalidArgumentException('Limite de 500 réponses de recette atteinte.'); }
      $key = hash('sha256', $email); $token = bin2hex(random_bytes(32));
      $old = $row['people'][$key] ?? [];
      $row['people'][$key] = ['name' => $name, 'email' => $email, 'token_hash' => hash('sha256', $token), 'expires' => time() + 7 * 86400, 'revision' => ($old['revision'] ?? 0) + 1, 'answer' => $old['answer'] ?? 'unknown', 'count' => $old['count'] ?? 0, 'event_revision' => $old['event_revision'] ?? 0];
      if (!empty($old['token_hash'])) { \Drupal::keyValueExpirable('tcl_bureau.event_reply_tokens')->delete($old['token_hash']); }
      \Drupal::keyValueExpirable('tcl_bureau.event_reply_tokens')->setWithExpire(hash('sha256', $token), ['id' => $id, 'date' => $date, 'person' => $key], 7 * 86400);
      return $token;
    });
  }
  /** Only explicitly linked, published communications expose an occurrence. */
  public static function entry(int $post): ?array {
    if (!SeasonCatalogue::recipeEnabled() || !\Drupal::service('tcl_bureau.communication')->published($post)) { return NULL; }
    $matches = [];
    foreach (self::store()->getAll() as $key => $row) {
      if ((int) ($row['registration_post'] ?? 0) !== $post || !self::isOpen($row)) { continue; }
      [$id, $date] = explode('.', $key, 2);
      try { $event = self::event($id, $date); } catch (AccessDeniedHttpException) { continue; }
      $matches[] = ['id' => $id, 'date' => $date, 'title' => $event['title'], 'time' => $event['start_time'], 'location' => $event['location'], 'deadline' => $row['deadline']];
    }
    // Ambiguous associations never choose a date on the participant's behalf.
    return count($matches) === 1 ? $matches[0] : NULL;
  }
  public static function registrationUrl(int $post): string {
    return self::entry($post) ? \Drupal\Core\Url::fromRoute('tcl_bureau.event_signup', ['post' => $post], ['absolute' => TRUE])->toString() : '';
  }
  public static function requestLink(int $post, string $name, string $email): void {
    self::guard(); $entry = self::entry($post);
    $email = strtolower(trim($email));
    if (!$entry || !RecipeMail::allowed($email) || trim($name) === '' || mb_strlen($name) > 100) { return; }
    $flood = \Drupal::service('flood'); $key = hash('sha256', $email);
    if (!$flood->isAllowed('tcl.event.signup.email', 1, 900, $key)) { return; }
    $flood->register('tcl.event.signup.email', 900, $key);
    $token = self::issue($entry['id'], $entry['date'], $name, $email);
    $url = \Drupal\Core\Url::fromRoute('tcl_bureau.event_response', [], ['absolute' => TRUE])->toString() . '#' . $token;
    RecipeMail::send('member_communication', $email, 'Votre lien de participation — ' . $entry['title'], "Bonjour,\n\nVotre lien personnel pour répondre à cet événement de recette :\n" . $url . "\n\nJe participe / Je ne participe pas / Je ne sais pas encore. Si vous participez, indiquez le total, vous compris.\nLien valable sept jours, dans la limite de la clôture. Ne partagez pas ce lien. Si vous ne l’avez pas demandé, ignorez ce message.");
  }
  /** Personal links are issued only during an explicitly authorized individual send. */
  public static function mailLinks(AccountInterface $actor, int $post, string $email): array {
    self::guard();
    if (!RecipeMail::allowed($email)) { throw new AccessDeniedHttpException(); }
    $entry = self::entry($post);
    if (!$entry) { return []; }
    self::manage($actor, $entry['id']);
    $row = self::read($actor, $entry['id'], $entry['date']);
    $name = $row['people'][hash('sha256', strtolower(trim($email)))]['name'] ?? 'Participant de recette';
    $token = self::issue($entry['id'], $entry['date'], $name, $email);
    $url = \Drupal\Core\Url::fromRoute('tcl_bureau.event_response', [], ['absolute' => TRUE])->toString();
    $links = [];
    foreach (['yes', 'no', 'maybe'] as $answer) { $links[$answer] = $url . '#' . $token . ':' . $answer; }
    return $links;
  }
  private static function locate(string $token): array {
    self::guard();
    if (!preg_match('/^[a-f0-9]{64}$/D', $token)) { throw new AccessDeniedHttpException(); }
    $hash = hash('sha256', $token);
    $index = \Drupal::keyValueExpirable('tcl_bureau.event_reply_tokens')->get($hash);
    if ($index) {
      $row = self::store()->get($index['id'] . '.' . $index['date'], []);
      $person = $row['people'][$index['person']] ?? NULL;
      if ($person && hash_equals($person['token_hash'], $hash) && $person['expires'] > time()) {
        return [$index['id'], $index['date'], $index['person'], $row, $person];
      }
    }
    throw new AccessDeniedHttpException();
  }
  private static function isOpen(array $row): bool {
    return $row['open'] && $row['deadline'] !== '' && $row['deadline'] >= (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d');
  }
  public static function context(string $token): array {
    [$id, $date, , $row, $person] = self::locate($token);
    $event = self::event($id, $date);
    return ['title' => $event['title'], 'date' => $date, 'time' => $event['start_time'], 'location' => $event['location'], 'name' => $person['name'], 'answer' => $person['answer'], 'count' => $person['count'], 'revision' => $person['revision'], 'eventRevision' => $event['revision'], 'needsConfirmation' => $person['answer'] !== 'unknown' && $person['event_revision'] !== $event['revision'], 'open' => self::isOpen($row), 'deadline' => $row['deadline']];
  }
  public static function respond(string $token, string $answer, int $count, int $revision, int $eventRevision): void {
    if (!in_array($answer, ['yes', 'no', 'maybe'], TRUE) || ($answer === 'yes' && ($count < 1 || $count > 99)) || ($answer !== 'yes' && $count !== 0)) { throw new \InvalidArgumentException('Indiquez de 1 à 99 personnes, vous compris, uniquement si vous participez.'); }
    [$id, $date, $key] = self::locate($token);
    self::write($id, $date, static function (&$row, $event) use ($token, $key, $answer, $count, $revision, $eventRevision): void {
      $person = $row['people'][$key] ?? [];
      if (!self::isOpen($row) || !hash_equals($person['token_hash'] ?? '', hash('sha256', $token)) || ($person['expires'] ?? 0) <= time()) { throw new AccessDeniedHttpException(); }
      if ($person['revision'] !== $revision || $event['revision'] !== $eventRevision) { throw new ConflictHttpException('La réponse ou l’événement a changé. Rechargez le contexte avant de confirmer.'); }
      $row['people'][$key] = array_replace($person, ['answer' => $answer, 'count' => $count, 'event_revision' => $eventRevision, 'revision' => $revision + 1]);
    });
  }
  public static function totals(array $row, int $eventRevision): array {
    $totals = ['yes' => 0, 'no' => 0, 'maybe' => 0, 'unknown' => 0, 'reconfirm' => 0, 'people' => 0];
    foreach ($row['people'] as $p) {
      if ($p['answer'] !== 'unknown' && $p['event_revision'] !== $eventRevision) { $totals['reconfirm']++; continue; }
      $totals[$p['answer']]++;
      if ($p['answer'] === 'yes') { $totals['people'] += $p['count']; }
    }
    return $totals;
  }
}
