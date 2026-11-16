<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau\Plugin\Mail;

use Drupal\Core\Mail\Attribute\Mail;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tcl_bureau\RecipeMail;

/** Native reset only, with the existing private recipe recipient allowlist. */
#[Mail(id: 'tcl_recipe_password_mail', label: new TranslatableMarkup('TC — réinitialisation en préproduction'))]
class RecipePasswordMail extends PhpMail {

  public function format(array $message) {
    $message['subject'] = '[RECETTE PRÉPRODUCTION] ' . $message['subject'];
    $message['from'] = 'support@tclongages.fr';
    $message['headers']['From'] = 'Tennis Club de Longages <support@tclongages.fr>';
    $message['headers']['Sender'] = 'support@tclongages.fr';
    $message['headers']['Return-Path'] = 'support@tclongages.fr';
    $message['headers']['Reply-to'] = 'support@tclongages.fr';
    return parent::format($message);
  }

  public function mail(array $message) {
    if ($this->request->getHost() !== 'preprod.tclongages.fr'
      || ($message['id'] ?? '') !== 'user_password_reset'
      || !RecipeMail::allowed((string) ($message['to'] ?? ''))) {
      return FALSE;
    }
    // Do not allow additional recipients to bypass the single-recipient guard.
    foreach ($message['headers'] ?? [] as $name => $value) {
      if (in_array(strtolower($name), ['cc', 'bcc', 'to'], TRUE) && trim((string) $value) !== '') {
        return FALSE;
      }
    }
    return $this->deliver($message);
  }

  protected function deliver(array $message): bool {
    return parent::mail($message);
  }

}
