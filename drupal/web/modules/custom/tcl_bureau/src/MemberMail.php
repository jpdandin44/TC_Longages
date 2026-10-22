<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** Explicit, individually addressed recipe sends of a reviewed snapshot. */
final class MemberMail {
  public static function post(AccountInterface $actor, int $id): array {
    if (!SeasonCatalogue::recipeEnabled()) { throw new AccessDeniedHttpException(); }
    $row = \Drupal::service('tcl_bureau.communication')->get($actor, $id);
    if (!$row || !in_array($row['status'], ['validated', 'published'], TRUE) || (int) $row['revision'] !== (int) $row['approved_revision'] || !in_array('email', explode(',', $row['channels']), TRUE)) { throw new ConflictHttpException('Enregistrez le canal courriel et validez cette révision avant de préparer un envoi.'); }
    return $row;
  }
  public static function body(array $row): string {
    $body = $row['title'] . "\n\n" . $row['body'];
    if ($row['link'] !== '') { $body .= "\n\n" . $row['link']; }
    if ($row['status'] === 'published' && in_array('site', explode(',', $row['channels']), TRUE)) { $body .= "\n\nActualité et support : " . \Drupal\Core\Url::fromRoute('tcl_bureau.public_post', ['post' => $row['id']], ['absolute' => TRUE])->toString(); }
    return $body . "\n\nTennis Club de Longages — essai de communication en préproduction.";
  }
  public static function snapshot(AccountInterface $actor, int $id, array $contacts): array {
    $row = self::post($actor, $id); $allowed = []; $excluded = 0;
    foreach ($contacts as $contact) {
      $email = strtolower(trim((string) ($contact['email'] ?? '')));
      if (RecipeMail::allowed($email)) { $allowed[$email] = $email; } else { $excluded++; }
    }
    if (count($allowed) > 20) { throw new \InvalidArgumentException('Recette limitée à 20 destinataires autorisés par envoi.'); }
    return ['post' => $id, 'revision' => (int) $row['revision'], 'subject' => $row['title'], 'body' => self::body($row), 'recipients' => array_values($allowed), 'excluded' => $excluded, 'prepared' => time()];
  }
  public static function history(AccountInterface $actor, array $snapshot): array {
    self::post($actor, $snapshot['post']); $rows = [];
    foreach ($snapshot['recipients'] as $to) {
      $key = $snapshot['post'] . '.' . $snapshot['revision'] . '.' . hash('sha256', $to);
      $record = \Drupal::keyValue('tcl_bureau.member_mail')->get($key);
      $rows[] = ['email' => $to, 'status' => $record['status'] ?? 'not_attempted'];
    }
    return $rows;
  }
  public static function send(AccountInterface $actor, array $snapshot): array {
    $row = self::post($actor, $snapshot['post']);
    if ((int) $row['revision'] !== $snapshot['revision'] || self::body($row) !== $snapshot['body'] || $row['title'] !== $snapshot['subject'] || $snapshot['prepared'] < time() - 900 || count($snapshot['recipients']) > 20) { throw new ConflictHttpException('L’aperçu a changé ou expiré. Préparez-le à nouveau.'); }
    $result = ['accepted' => 0, 'failed' => 0, 'skipped' => 0];
    $store = \Drupal::keyValue('tcl_bureau.member_mail'); $lock = \Drupal::lock();
    foreach ($snapshot['recipients'] as $to) {
      if (!RecipeMail::allowed($to)) { $result['skipped']++; continue; }
      $key = $snapshot['post'] . '.' . $snapshot['revision'] . '.' . hash('sha256', $to);
      if (!$lock->acquire('tcl.member.mail.' . $key, 120)) { $result['skipped']++; continue; }
      try {
        if ($store->get($key)) { $result['skipped']++; continue; }
        // Recheck before each irreversible send. Pending is never automatically retried.
        if ((int) self::post($actor, $snapshot['post'])['revision'] !== $snapshot['revision']) { throw new ConflictHttpException('La communication a changé. Envoi interrompu ; consulter le suivi.'); }
        $store->set($key, ['status' => 'pending', 'at' => time()]);
        $status = RecipeMail::send('member_communication', $to, $snapshot['subject'], $snapshot['body']) ? 'accepted' : 'failed';
        $store->set($key, ['status' => $status, 'at' => time()]); $result[$status]++;
      } finally { $lock->release('tcl.member.mail.' . $key); }
    }
    return $result;
  }
}
