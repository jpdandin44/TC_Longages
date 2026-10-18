<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

use Drupal\Core\Site\Settings;

/** Explicit private recipients and transport; disabled outside recipe hosts. */
final class RecipeMail {
  public static function settings(): array {
    $value = Settings::get('tcl_bureau_recipe_mail', []);
    return is_array($value) && ($value['enabled'] ?? FALSE) === TRUE
      && in_array(\Drupal::request()->getHost(), ['127.0.0.1', 'localhost', 'preprod.tclongages.fr'], TRUE) ? $value : [];
  }
  public static function allowed(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && in_array(strtolower(trim($email)), array_map('strtolower', self::settings()['allowed_recipients'] ?? []), TRUE);
  }
  public static function inputAllowed(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL) && (str_ends_with($email, '@example.invalid') || self::allowed($email));
  }
  public static function send(string $key, string $to, string $subject, string $body): bool {
    if (!self::allowed($to) || !in_array($key, ['account_invitation', 'registration'], TRUE)) { return FALSE; }
    $manager = \Drupal::service('plugin.manager.mail');
    $plugin = $manager->getInstance(['module' => 'tcl_bureau', 'key' => $key]);
    // NullMail reports success without sending. Only explicit transports qualify.
    $local = in_array(\Drupal::request()->getHost(), ['127.0.0.1', 'localhost'], TRUE);
    if (get_class($plugin) !== \Drupal\Core\Mail\Plugin\Mail\PhpMail::class && !($local && get_class($plugin) === \Drupal\Core\Mail\Plugin\Mail\TestMailCollector::class)) { return FALSE; }
    try {
      $result = $manager->mail('tcl_bureau', $key, $to, 'fr', ['subject' => '[RECETTE PRÃ‰PRODUCTION] ' . $subject, 'body' => $body]);
      return ($result['result'] ?? FALSE) === TRUE;
    }
    catch (\Throwable $error) { return FALSE; }
  }
  public static function registration(int $id): string {
    if (!self::settings()) { return 'disabled'; }
    $lock = \Drupal::lock();
    $key = 'registration.' . $id;
    if (!$lock->acquire('tcl.recipe.mail.' . $key, 60)) { return 'pending'; }
    try {
      $store = \Drupal::keyValue('tcl_bureau.recipe_mail');
      $previous = $store->get($key);
      // At-most-once attempt, including uncertain transport outcomes.
      if ($previous) { return $previous['status']; }
      $row = \Drupal::database()->select('tcl_bureau_registration', 'r')->fields('r', ['details'])->condition('id', $id)->execute()->fetchField();
      $values = $row ? json_decode($row, TRUE, 512, JSON_THROW_ON_ERROR) : [];
      if (($values['recipe'] ?? FALSE) !== TRUE || ($values['origin'] ?? '') !== 'web_recipe') { return 'failed'; }
      $to = self::settings()['registration_to'] ?? '';
      $body = "Demande dâ€™adhÃ©sion fictive nÂ°" . $id . ". Ceci est un essai : aucune adhÃ©sion rÃ©elle.\n\n";
      foreach (RegistrationRepository::FIELDS as $field) {
        if (!empty($values[$field])) { $body .= $field . ' : ' . $values[$field] . "\n"; }
      }
      $body .= "\nDisponibilitÃ©s : " . implode(', ', $values['availability'] ?? []) . "\n\nSuivi privÃ© : " . \Drupal\Core\Url::fromRoute('tcl_bureau.registrations', [], ['absolute' => TRUE])->toString() . "\nAucun accusÃ© de rÃ©ception au demandeur.";
      $store->set($key, ['status' => 'pending', 'at' => time()]);
      $status = self::send('registration', $to, 'Demande dâ€™adhÃ©sion fictive nÂ°' . $id, $body) ? 'accepted' : 'failed';
      $store->set($key, ['status' => $status, 'at' => time()]);
      return $status;
    }
    finally { $lock->release('tcl.recipe.mail.' . $key); }
  }
}
