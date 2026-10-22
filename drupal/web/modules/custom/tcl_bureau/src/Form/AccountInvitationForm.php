<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tcl_bureau\AccountInvitation;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AccountInvitationForm extends FormBase {
  protected int $uid;
  protected bool $reset = FALSE;
  public function getFormId(): string { return $this->reset ? 'tcl_account_password_reset' : 'tcl_account_invitation'; }
  public function buildForm(array $form, FormStateInterface $form_state, ?UserInterface $user = NULL): array {
    if (!$user || !\Drupal::service('tcl_bureau.repository')->canManageAccount($this->currentUser(), $user)) { throw new AccessDeniedHttpException(); }
    $this->uid = (int) $user->id();
    $form['#cache']['max-age'] = 0;
    $form['#attributes']['class'][] = 'tcl-bureau';
    $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['recipient'] = ['#type' => 'item', '#title' => 'Destinataire enregistré', '#plain_text' => $user->getEmail()];
    $form['notice'] = ['#plain_text' => 'Envoyer un courriel réel de recette contenant un lien personnel pour définir le mot de passe. La connexion restera protégée par OAuth et les droits de ce compte. Aucun mot de passe n’est envoyé.'];
    if ($this->reset) { $form['notice']['#plain_text'] = 'Envoyer à cette adresse enregistrée un lien personnel pour choisir un nouveau mot de passe. Le mot de passe actuel reste valable jusqu’au changement effectué par le destinataire. Aucun mot de passe n’est envoyé. Si vous avez modifié l’adresse du compte, enregistrez-la avant de poursuivre.'; }
    $form['version'] = ['#type' => 'hidden', '#default_value' => AccountInvitation::version($user)];
    $form['actions']['#type'] = 'actions';
    $form['actions']['send'] = ['#type' => 'submit', '#value' => 'Envoyer le lien de création du mot de passe', '#disabled' => !$user->isActive()];
    $form['actions']['cancel'] = ['#type' => 'link', '#title' => 'Retour aux comptes', '#url' => \Drupal\Core\Url::fromRoute('tcl_bureau.accounts')];
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try {
      $accepted = AccountInvitation::send($this->currentUser(), $this->uid, (string) $form_state->getValue('version'), $this->reset);
      if ($accepted) { $this->messenger()->addStatus('Courriel accepté par le serveur d’envoi. Vérifiez sa réception, y compris les indésirables.'); }
      else { $this->messenger()->addError('L’envoi n’a pas été confirmé. Le compte reste enregistré ; vérifiez la configuration du transport.'); }
      $form_state->setRedirect('tcl_bureau.accounts');
    }
    catch (\InvalidArgumentException | ConflictHttpException $error) { $this->messenger()->addError($error->getMessage()); $form_state->setRebuild(); }
  }
}
