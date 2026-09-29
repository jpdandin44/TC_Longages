<?php

declare(strict_types=1);

namespace Drupal\tcl_site\Plugin\Mail;

use Drupal\Core\Mail\Attribute\Mail;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/** Deliberately discards all email in the local evaluation environment. */
#[Mail(id: 'tcl_null_mail', label: new TranslatableMarkup('TC Longages — aucun envoi local'))]
final class NullMail implements MailInterface {

  public function format(array $message): array {
    $message['body'] = implode("\n", $message['body']);
    return $message;
  }

  public function mail(array $message): bool {
    return TRUE;
  }

}
