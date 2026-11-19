<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\tcl_bureau\GmailConnection as G;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
final class GmailForm extends FormBase {
  public function getFormId(): string { return 'tcl_gmail'; }
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if (!G::allowed($this->currentUser())) { throw new AccessDeniedHttpException(); }
    $form['#cache']['max-age'] = 0; $form['#attributes']['class'][] = 'tcl-bureau'; $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['back'] = ['#type' => 'link', '#title' => 'Retour aux contacts', '#url' => Url::fromRoute('tcl_bureau.google_contacts')];
    $form['notice'] = ['#plain_text' => 'Communications du club : tclongages@gmail.com. Les comptes et le support technique restent sur support@tclongages.fr. Autorisation d’envoi uniquement, sans lecture de la boîte Gmail. Connecter Gmail ne déclenche aucun courriel.'];
    $form['status'] = ['#type' => 'item', '#title' => 'Connexion Gmail', '#plain_text' => G::connected() ? 'Autorisation enregistrée. Sa validité sera contrôlée avant chaque envoi.' : 'À connecter avant tout envoi de communication.'];
    $form['actions']['#type'] = 'actions';
    $form['actions']['connect'] = ['#type' => 'submit', '#value' => 'Autoriser l’envoi avec Gmail'];
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try { $response = new TrustedRedirectResponse(G::begin($this->currentUser())); $response->headers->set('Cache-Control', 'no-store'); $response->headers->set('Referrer-Policy', 'no-referrer'); $form_state->setResponse($response); }
    catch (\RuntimeException $e) { $this->messenger()->addError($e->getMessage()); $form_state->setRebuild(); }
  }
}
