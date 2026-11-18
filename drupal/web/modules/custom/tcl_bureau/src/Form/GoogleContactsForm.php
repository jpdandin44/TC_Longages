<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\tcl_bureau\GoogleContactsConnection;
use Drupal\tcl_bureau\MemberContacts;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
final class GoogleContactsForm extends FormBase {
  public function getFormId(): string { return 'tcl_google_contacts'; }
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if (!GoogleContactsConnection::allowed($this->currentUser())) { throw new AccessDeniedHttpException(); }
    $form['#cache']['max-age'] = 0; $form['#attributes']['class'][] = 'tcl-bureau'; $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['notice'] = ['#plain_text' => 'Compte du club : tclongages@gmail.com. Libellé : Adhérents_2026_2027. Google demande la lecture des contacts du compte ; le site utilise uniquement ce libellé. Aucune modification des contacts ni aucun envoi automatique. ' . (MemberContacts::production() ? 'La liste est actualisée à chaque préparation de courriel.' : 'Les destinataires restent limités aux adresses de recette autorisées.')];
    $form['status'] = ['#type' => 'item', '#title' => 'Connexion', '#plain_text' => MemberContacts::configured() ? 'Autorisation enregistrée. La préparation du courriel vérifiera la lecture effective.' : 'À connecter.'];
    $last = \Drupal::keyValue('tcl_bureau.google_contacts')->get('last_refresh');
    if ($last) {
      $date = (new \DateTimeImmutable('@' . $last['at']))->setTimezone(new \DateTimeZone('Europe/Paris'))->format('d/m/Y à H:i:s');
      $form['last_refresh'] = ['#type' => 'item', '#title' => 'Dernière lecture réussie',
        '#plain_text' => $date . ' (Paris) — ' . $last['count'] . ' adresses distinctes utilisables ; ' . $last['excluded'] . ' contacts sans adresse valide.'];
    }
    $form['refresh_help'] = ['#plain_text' => 'Actualisez la liste depuis le libellé Google du club. Les doublons d’adresse sont regroupés.' . (MemberContacts::production() ? ' Aucun courriel ne sera envoyé.' : ' Les aperçus de recette conservent leurs destinataires fictifs.')];
    $form['actions']['#type'] = 'actions';
    $form['actions']['refresh'] = ['#type' => 'submit', '#name' => 'refresh', '#value' => 'Mettre à jour les contacts', '#disabled' => !MemberContacts::configured()];
    $form['actions']['connect'] = ['#type' => 'submit', '#name' => 'connect', '#value' => 'Connecter Google Contacts en lecture seule'];
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if (!GoogleContactsConnection::allowed($this->currentUser())) { throw new AccessDeniedHttpException(); }
    if (($form_state->getTriggeringElement()['#name'] ?? '') === 'refresh') {
      try {
        $directory = MemberContacts::recipients();
        if ($directory['group'] !== 'Adhérents_2026_2027') {
          throw new \RuntimeException('Le libellé Google ne correspond pas à Adhérents_2026_2027.');
        }
        $summary = ['at' => time(), 'count' => count($directory['recipients']), 'excluded' => $directory['excluded']];
        \Drupal::keyValue('tcl_bureau.google_contacts')->set('last_refresh', $summary);
        \Drupal::request()->attributes->set('_tcl_google_recipients', $directory);
        $this->messenger()->addStatus('Contacts Google actualisés : ' . $summary['count'] . ' adresses distinctes utilisables. Aucun courriel envoyé.');
      } catch (\RuntimeException $e) {
        $this->messenger()->addError('Mise à jour impossible. Vérifiez la connexion Google Contacts et le libellé du club. La dernière lecture réussie reste affichée.');
      }
      $form_state->setRebuild();
      return;
    }
    try { $response = new TrustedRedirectResponse(GoogleContactsConnection::begin($this->currentUser())); $response->headers->set('Cache-Control', 'no-store'); $response->headers->set('Referrer-Policy', 'no-referrer'); $form_state->setResponse($response); }
    catch (\RuntimeException $e) { $this->messenger()->addError($e->getMessage()); $form_state->setRebuild(); }
  }
}
