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
    $form['notice'] = ['#plain_text' => 'Compte du club : tclongages@gmail.com. Libellé : Adhérents_2026_2027. Google demande la lecture des contacts du compte ; le site utilise uniquement ce libellé. Aucune modification des contacts ni aucun envoi automatique. Les destinataires restent limités aux adresses de recette autorisées.'];
    $form['status'] = ['#type' => 'item', '#title' => 'Connexion', '#plain_text' => MemberContacts::configured() ? 'Autorisation enregistrée. La préparation du courriel vérifiera la lecture effective.' : 'À connecter.'];
    $form['actions']['#type'] = 'actions';
    $form['actions']['connect'] = ['#type' => 'submit', '#value' => 'Connecter Google Contacts en lecture seule'];
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try { $response = new TrustedRedirectResponse(GoogleContactsConnection::begin($this->currentUser())); $response->headers->set('Cache-Control', 'no-store'); $response->headers->set('Referrer-Policy', 'no-referrer'); $form_state->setResponse($response); }
    catch (\RuntimeException $e) { $this->messenger()->addError($e->getMessage()); $form_state->setRebuild(); }
  }
}
