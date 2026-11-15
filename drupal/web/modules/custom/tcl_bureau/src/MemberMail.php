<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** Explicit, individually addressed recipe sends of a reviewed snapshot. */
final class MemberMail {
  public static function post(AccountInterface $actor, int $id): array {
    if (!SeasonCatalogue::recipeEnabled() && !MemberContacts::production()) { throw new AccessDeniedHttpException(); }
    $row = \Drupal::service('tcl_bureau.communication')->get($actor, $id);
    if (!$row || !in_array($row['status'], ['validated', 'published'], TRUE) || (int) $row['revision'] !== (int) $row['approved_revision'] || !in_array('email', explode(',', $row['channels']), TRUE)) { throw new ConflictHttpException('Enregistrez le canal courriel et validez cette révision avant de préparer un envoi.'); }
    return $row;
  }
  public static function body(array $row): string {
    $row = ChannelContent::forChannel($row, 'email');
    $body = $row['title'] . "\n\n" . $row['body'];
    if ($row['link'] !== '') { $body .= "\n\n" . $row['link']; }
    if ($row['status'] === 'published' && in_array('site', explode(',', $row['channels']), TRUE)) { $body .= "\n\nActualité et support : " . \Drupal\Core\Url::fromRoute('tcl_bureau.public_post', ['post' => $row['id']], ['absolute' => TRUE])->toString(); }
    $registration = EventParticipation::registrationUrl((int) $row['id']);
    if ($registration !== '') { $body .= "\n\nJe m’inscris à cet événement / je réponds : " . $registration; }
    if (MemberContacts::production()) { return $body . "\n\nTennis Club de Longages"; }
    return $body . "\n\nTennis Club de Longages — essai de communication en préproduction.";
  }
  /** Only escaped text and server-generated URLs; no user HTML or tracking. */
  public static function html(array $row, array $links = [], bool $previewResponses = FALSE): string {
    $copy = ChannelContent::forChannel($row, 'email');
    $escape = static fn(string $s): string => \Drupal\Component\Utility\Html::escape($s);
    $html = '<div style="font-family:Arial,sans-serif;max-width:620px;margin:auto;color:#242424;padding:24px;border-top:5px solid #a71930"><p>Tennis Club de Longages ' . (MemberContacts::production() ? '' : '· RECETTE') . '</p><h1 style="color:#801326">' . $escape($copy['title']) . '</h1><p style="line-height:1.6">' . nl2br($escape($copy['body'])) . '</p>';
    if ($copy['link'] !== '') { $html .= '<p><a href="' . $escape($copy['link']) . '">Informations complémentaires</a></p>'; }
    if ($row['status'] === 'published' && in_array('site', explode(',', $row['channels']), TRUE)) {
      $url = \Drupal\Core\Url::fromRoute('tcl_bureau.public_post', ['post' => $row['id']], ['absolute' => TRUE])->toString();
      $html .= '<p><a href="' . $escape($url) . '">Voir l’actualité et l’affiche</a></p>';
    }
    if ($links || $previewResponses || EventParticipation::entry((int) $row['id'])) {
      $html .= '<h2>Votre réponse</h2><p>Choisissez une réponse, puis confirmez sur la page. Si vous participez, indiquez le nombre total de personnes, vous compris.</p>';
      foreach (['yes', 'no', 'maybe'] as $answer) {
        $label = $escape(EventParticipation::ANSWERS[$answer]);
        $style = 'display:inline-block;margin:6px 6px 6px 0;padding:12px 16px;background:#a71930;color:#ffffff;text-decoration:none;border-radius:5px';
        $html .= isset($links[$answer]) ? '<a style="' . $style . '" href="' . $escape($links[$answer]) . '">' . $label . '</a>' : '<span style="' . $style . '">' . $label . '</span>';
      }
      $html .= '<p>Liens personnels : ne transférez pas ce message. Aucun choix n’est enregistré avant confirmation. Validité : sept jours au maximum et avant clôture.</p>';
    }
    if (MemberContacts::production()) { return $html . '</div>'; }
    return $html . '<p style="font-size:12px;color:#555">Essai de préproduction — aucune inscription réelle.</p></div>';
  }
  public static function multipart(array $message, string $body, string $html): array {
    $boundary = 'tcl_' . bin2hex(random_bytes(18));
    $message['headers']['Content-Type'] = 'multipart/alternative; boundary="' . $boundary . '"';
    $message['headers']['Content-Transfer-Encoding'] = '7bit';
    $message['body'] = '--' . $boundary . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n") . '--' . $boundary . "\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode('<!doctype html><html lang="fr"><body>' . $html . '</body></html>'), 76, "\r\n") . '--' . $boundary . "--\r\n";
    return $message;
  }
  public static function snapshot(AccountInterface $actor, int $id, array $contacts): array {
    $row = self::post($actor, $id); $allowed = []; $excluded = 0;
    $production = MemberContacts::production();
    if ($production) { $contacts = MemberContacts::deliveryDirectory()['recipients']; }
    foreach ($contacts as $contact) {
      $email = strtolower(trim((string) ($contact['email'] ?? '')));
      if ($production || RecipeMail::allowed($email)) { $allowed[$email] = $email; } else { $excluded++; }
    }
    if (!$production && count($allowed) > 20) { throw new \InvalidArgumentException('Recette limitée à 20 destinataires autorisés par envoi.'); }
    return ['post' => $id, 'revision' => (int) $row['revision'], 'subject' => ChannelContent::forChannel($row, 'email')['title'], 'body' => self::body($row), 'recipients' => array_values($allowed), 'excluded' => $excluded, 'prepared' => time()];
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
    if ((int) $row['revision'] !== $snapshot['revision'] || self::body($row) !== $snapshot['body'] || ChannelContent::forChannel($row, 'email')['title'] !== $snapshot['subject'] || $snapshot['prepared'] < time() - 900 || (!MemberContacts::production() && count($snapshot['recipients']) > 20)) { throw new ConflictHttpException('L’aperçu a changé ou expiré. Préparez-le à nouveau.'); }
    $result = ['accepted' => 0, 'failed' => 0, 'skipped' => 0];
    $store = \Drupal::keyValue('tcl_bureau.member_mail'); $lock = \Drupal::lock();
    $production = MemberContacts::production();
    $directory = $production ? array_column(MemberContacts::deliveryDirectory()['recipients'], 'email') : [];
    if ($production && \Drupal::service('plugin.manager.mail')->getInstance(['module' => 'tcl_bureau', 'key' => 'member_communication']) instanceof \Drupal\tcl_site\Plugin\Mail\NullMail) {
      throw new \RuntimeException('Le transport des courriels adhérents est désactivé. Aucun envoi effectué.');
    }
    foreach ($snapshot['recipients'] as $to) {
      if ($production ? !in_array($to, $directory, TRUE) : !RecipeMail::allowed($to)) { $result['skipped']++; continue; }
      $key = $snapshot['post'] . '.' . $snapshot['revision'] . '.' . hash('sha256', $to);
      if (!$lock->acquire('tcl.member.mail.' . $key, 120)) { $result['skipped']++; continue; }
      try {
        if ($store->get($key)) { $result['skipped']++; continue; }
        // Recheck before each irreversible send. Pending is never automatically retried.
        if ((int) self::post($actor, $snapshot['post'])['revision'] !== $snapshot['revision']) { throw new ConflictHttpException('La communication a changé. Envoi interrompu ; consulter le suivi.'); }
        $store->set($key, ['status' => 'pending', 'at' => time()]);
        $links = EventParticipation::mailLinks($actor, (int) $row['id'], $to);
        $text = $snapshot['body'];
        if ($links) {
          $registration = EventParticipation::registrationUrl((int) $row['id']);
          $text = str_replace("Je m’inscris à cet événement / je réponds : " . $registration, 'Votre réponse personnelle (à confirmer sur la page) :', $text);
          foreach ($links as $answer => $url) { $text .= "\n\n" . EventParticipation::ANSWERS[$answer] . ' : ' . $url; }
          $text .= "\n\nLien personnel valable sept jours au maximum, jusqu’à la clôture. Ne le transférez pas. Une nouvelle invitation remplace l’ancien lien sans effacer votre réponse.";
        }
        if ($production && $links) {
          $manager = \Drupal::service('plugin.manager.mail');
          $message = $manager->mail('tcl_bureau', 'member_communication', $to, 'fr', ['subject' => $snapshot['subject'], 'body' => $text], NULL, FALSE);
          $plugin = $manager->getInstance(['module' => 'tcl_bureau', 'key' => 'member_communication']);
          $status = ($message['send'] ?? TRUE) && $plugin->mail(self::multipart($message, $text, self::html($row, $links))) ? 'accepted' : 'failed';
        } elseif ($production) {
          $mail = \Drupal::service('plugin.manager.mail')->mail('tcl_bureau', 'member_communication', $to, 'fr', ['subject' => $snapshot['subject'], 'body' => $text], NULL, TRUE);
          $status = !empty($mail['result']) ? 'accepted' : 'failed';
        } else {
          $status = RecipeMail::send('member_communication', $to, $snapshot['subject'], $text, self::html($row, $links)) ? 'accepted' : 'failed';
          }
        $store->set($key, ['status' => $status, 'at' => time()]); $result[$status]++;
      } finally { $lock->release('tcl.member.mail.' . $key); }
    }
    return $result;
  }
}
