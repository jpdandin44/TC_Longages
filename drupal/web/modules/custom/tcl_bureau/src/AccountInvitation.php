<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

use Drupal\Core\Session\AccountInterface;
use Drupal\tcl_bureau_oauth\OAuthSettings;
use Drupal\tcl_identity\IdentitySettings;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** Explicit manager invitation, never provisioning by email matching. */
final class AccountInvitation {
  public static function version(UserInterface $user): string {
    return hash('sha256', serialize([$user->uuid(), $user->getEmail(), $user->getRoles(), $user->isActive(), $user->getPassword()]));
  }
  public static function send(AccountInterface $actor, int $uid, string $version, bool $reset = FALSE): bool {
    $lock = \Drupal::lock();
    $name = 'tcl.invitation.' . $uid;
    if (!$lock->acquire($name, 60)) { throw new ConflictHttpException('Un envoi est déjà en cours.'); }
    try {
      $storage = \Drupal::entityTypeManager()->getStorage('user');
      $storage->resetCache([$uid]);
      $user = $storage->load($uid);
      if (!$user || !\Drupal::service('tcl_bureau.repository')->canManageAccount($actor, $user) || !$user->isActive()) { throw new AccessDeniedHttpException(); }
      if (!hash_equals(self::version($user), $version)) { throw new ConflictHttpException('Le compte a changé. Rechargez sa fiche.'); }
      if (!RecipeMail::allowed($user->getEmail())) { throw new \InvalidArgumentException('Cette adresse n’est pas autorisée pour les courriels de recette.'); }
      $settings = OAuthSettings::load();
      $provider = IdentitySettings::load();
      foreach (['issuer', 'client_id', 'redirect_uri'] as $key) {
        if ($settings[$key] !== $provider[$key]) { throw new \InvalidArgumentException('L’invitation nécessite le fournisseur TC dédié.'); }
      }
      $flood = \Drupal::service('flood');
      if (!$flood->isAllowed('tcl.account.invitation', 1, 300, (string) $uid)) { throw new \InvalidArgumentException('Un essai d’envoi a déjà eu lieu. Attendez cinq minutes avant de réessayer.'); }
      $existing = OAuthSettings::identityUid($settings, (string) $uid);
      if ($existing !== NULL && $existing !== $uid) { throw new \InvalidArgumentException('Association d’identité incohérente.'); }
      if ($reset && $existing !== $uid) { throw new \InvalidArgumentException('Invitez d’abord ce compte avant de réinitialiser son mot de passe.'); }
      // The numeric subject is issued by this same Drupal TC provider.
      if (!$reset) { \Drupal::keyValue('tcl_bureau.invited_identities')->set((string) $uid, ['issuer' => $settings['issuer'], 'client_id' => $settings['client_id'], 'uuid' => $user->uuid(), 'actor' => (int) $actor->id(), 'at' => time()]); }
      $flood->register('tcl.account.invitation', 300, (string) $uid);
      \Drupal::moduleHandler()->loadInclude('user', 'module');
      $url = user_pass_reset_url($user);
      $body = "Bonjour,\n\nVotre compte de recette TC Longages est prêt. Identifiant : " . $user->getAccountName() . "\n\nDéfinissez votre mot de passe avec ce lien personnel à usage unique :\n" . $url . "\n\nPuis ouvrez le Bureau :\n" . \Drupal\Core\Url::fromRoute('tcl_bureau.home', [], ['absolute' => TRUE])->toString() . "\n\nL’accès préproduction reste protégé. Ne partagez pas ce lien. Si vous n’attendiez pas ce message, ignorez-le.";
      if ($reset) { $body = "Bonjour,\n\nUne réinitialisation du mot de passe de votre compte TC Longages a été demandée par le Bureau. Identifiant : " . $user->getAccountName() . "\n\nChoisissez un nouveau mot de passe avec ce lien personnel à usage unique :\n" . $url . "\n\nVotre mot de passe actuel reste inchangé tant que vous n’en choisissez pas un nouveau. Ne partagez pas ce lien. Si vous n’attendiez pas ce message, ignorez-le."; }
      $accepted = RecipeMail::send('account_invitation', $user->getEmail(), $reset ? 'Réinitialiser votre mot de passe TC Longages' : 'Votre compte TC Longages', $body);
      \Drupal::keyValue('tcl_bureau.recipe_mail')->set(($reset ? 'password_reset.' : 'invitation.') . $uid, ['status' => $accepted ? 'accepted' : 'failed', 'at' => time(), 'actor' => (int) $actor->id()]);
      return $accepted;
    }
    finally { $lock->release($name); }
  }
}
