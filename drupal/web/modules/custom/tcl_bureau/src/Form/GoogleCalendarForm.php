<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\tcl_bureau\GoogleCalendarConnection as G;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
final class GoogleCalendarForm extends FormBase {
  public function getFormId(): string { return 'tcl_google_calendar'; }
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if (!G::allowed($this->currentUser())) { throw new AccessDeniedHttpException(); }
    $form['#cache']['max-age'] = 0; $form['#attributes']['class'][] = 'tcl-bureau'; $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['back'] = ['#type' => 'link', '#title' => 'Retour au calendrier', '#url' => Url::fromUserInput('/calendrier.html')];
    $form['notice'] = ['#plain_text' => 'Compte : tclongages@gmail.com. Autorisation limitée aux calendriers créés par cette application. La cible sera « TC Longages — Recette V2 », privée et séparée du calendrier officiel. Aucun partage public, aucune invitation, aucun import automatique des événements existants.'];
    $form['status'] = ['#type' => 'item', '#title' => 'Connexion', '#plain_text' => G::target() ? 'Calendrier de recette enregistré. Les prochains enregistrements depuis le site seront transmis à Google.' : (G::connected() ? 'Autorisation enregistrée. Créez maintenant le calendrier de recette.' : 'À connecter.')];
    $form['actions']['#type'] = 'actions';
    $form['actions']['connect'] = ['#type' => 'submit', '#value' => 'Autoriser Google Agenda', '#name' => 'connect'];
    if (G::connected() && !G::target()) { $form['actions']['create'] = ['#type' => 'submit', '#value' => 'Créer TC Longages — Recette V2', '#name' => 'create']; }
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try {
      if (($form_state->getTriggeringElement()['#name'] ?? '') === 'create') { G::createCalendar($this->currentUser()); $this->messenger()->addStatus('Calendrier Google de recette créé. Aucun événement officiel modifié.'); $form_state->setRebuild(); return; }
      $response = new TrustedRedirectResponse(G::begin($this->currentUser())); $response->headers->set('Cache-Control', 'no-store'); $response->headers->set('Referrer-Policy', 'no-referrer'); $form_state->setResponse($response);
    } catch (\RuntimeException $e) { $this->messenger()->addError($e->getMessage()); $form_state->setRebuild(); }
  }
}
