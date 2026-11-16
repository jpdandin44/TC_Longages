<?php
declare(strict_types=1);
require dirname(__DIR__) . '/drupal/bin/local-bootstrap.php';

use Drupal\Core\Site\Settings;
use Drupal\tcl_bureau\Plugin\Mail\RecipePasswordMail;
use Symfony\Component\HttpFoundation\Request;

$original = Settings::getAll();
$stack = Drupal::requestStack();
$checks = 0;
function verifyReset(bool $ok): void { global $checks; if (!$ok) { throw new RuntimeException('Reset transport guard failed'); } $checks++; }
try {
  $settings = $original;
  $settings['tcl_bureau_recipe_mail'] = ['enabled' => TRUE, 'allowed_recipients' => ['fixture@example.invalid']];
  new Settings($settings);
  foreach (['preprod.tclongages.fr', 'tclongages.fr', 'localhost'] as $host) {
    $stack->push(Request::create('https://' . $host));
    try {
      $plugin = new class extends RecipePasswordMail {
        public int $attempts = 0;
        protected function deliver(array $message): bool { $this->attempts++; return TRUE; }
      };
      $message = ['id' => 'user_password_reset', 'to' => 'fixture@example.invalid', 'headers' => []];
      verifyReset($plugin->mail($message) === ($host === 'preprod.tclongages.fr'));
      verifyReset(!$plugin->mail(array_replace($message, ['to' => 'other@example.invalid'])));
      verifyReset(!$plugin->mail(array_replace($message, ['to' => 'fixture@example.invalid,other@example.invalid'])));
      verifyReset(!$plugin->mail(array_replace($message, ['id' => 'user_register_admin_created'])));
      verifyReset(!$plugin->mail(array_replace($message, ['headers' => ['Bcc' => 'other@example.invalid']])));
      verifyReset($plugin->attempts === ($host === 'preprod.tclongages.fr' ? 1 : 0));
      $formatted = $plugin->format($message + ['subject' => 'Test', 'body' => ['Fictif'], 'from' => 'old@example.invalid']);
      verifyReset($formatted['from'] === 'support@tclongages.fr' && str_starts_with($formatted['subject'], '[RECETTE PRÉPRODUCTION]'));
      $settings['tcl_bureau_recipe_mail']['enabled'] = FALSE;
      new Settings($settings);
      verifyReset(!$plugin->mail($message));
      $settings['tcl_bureau_recipe_mail']['enabled'] = TRUE;
      new Settings($settings);
    }
    finally { $stack->pop(); }
  }
  echo $checks . " reset transport checks passed; no real mail sent.\n";
}
finally { new Settings($original); }
