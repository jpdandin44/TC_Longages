<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tcl_bureau\EventParticipation;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Public entry verifies mailbox ownership before allowing any response. */
final class EventSignupForm extends FormBase {
  public function getFormId(): string { return 'tcl_event_signup'; }
  public function buildForm(array $form, FormStateInterface $form_state, ?string $post = NULL): array {
    $entry = EventParticipation::entry((int) $post);
    if (!$entry) { throw new NotFoundHttpException('Inscriptions indisponibles ou closes.'); }
    $form_state->set('post', (int) $post);
    \Drupal::service('page_cache_kill_switch')->trigger();
    \Drupal::request()->getSession()->start();
    \Drupal::request()->getSession()->set('tcl_event_signup', TRUE);
    $form['#cache']['max-age'] = 0;
    $form['#attributes']['class'] = ['tcl-bureau'];
    $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['title'] = ['#type' => 'html_tag', '#tag' => 'h2', 'text' => ['#plain_text' => $entry['title']]];
    $form['details'] = ['#type' => 'html_tag', '#tag' => 'p', 'text' => ['#plain_text' => $entry['date'] . ' à ' . $entry['time'] . ' — ' . $entry['location']]];
    $form['help'] = ['#markup' => '<p>Recette en préproduction : utilisez un nom fictif et une adresse de recette autorisée. Recevez votre lien personnel pour répondre Oui, Non ou Incertain et préciser le nombre de personnes. Demander un lien ne vous inscrit pas encore.</p>'];
    $form['signup_csrf'] = ['#type' => 'hidden', '#value' => \Drupal::csrfToken()->get('tcl.event.signup')];
    $form['name'] = ['#type' => 'textfield', '#title' => 'Votre nom fictif', '#maxlength' => 100, '#required' => TRUE];
    $form['email'] = ['#type' => 'email', '#title' => 'Votre adresse email de recette', '#required' => TRUE];
    $form['submit'] = ['#type' => 'submit', '#value' => 'Recevoir mon lien personnel'];
    $form['back'] = ['#type' => 'link', '#title' => 'Retour à l’actualité', '#url' => Url::fromRoute('tcl_bureau.public_post', ['post' => $post])];
    return $form;
  }
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $r = \Drupal::request();
    $token = $form_state->getUserInput()['signup_csrf'] ?? '';
    if (!is_string($token) || !\Drupal::csrfToken()->validate($token, 'tcl.event.signup') || ($r->headers->has('Origin') && $r->headers->get('Origin') !== $r->getSchemeAndHttpHost())) {
      $form_state->setErrorByName('email', 'La session a expiré. Rechargez le formulaire.');
    }
    $flood = \Drupal::service('flood'); $ip = hash('sha256', (string) $r->getClientIp());
    if (!$flood->isAllowed('tcl.event.signup.ip', 10, 3600, $ip)) { $form_state->setErrorByName('email', 'Trop de demandes. Réessayez plus tard.'); }
  }
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    \Drupal::service('flood')->register('tcl.event.signup.ip', 3600, hash('sha256', (string) \Drupal::request()->getClientIp()));
    EventParticipation::requestLink($form_state->get('post'), (string) $form_state->getValue('name'), (string) $form_state->getValue('email'));
    $this->messenger()->addStatus('Si cette adresse est autorisée pour la recette, un lien personnel vous sera envoyé. Consultez aussi les indésirables. Attendez quinze minutes avant une nouvelle demande.');
    $form_state->setRedirect('tcl_bureau.event_signup', ['post' => $form_state->get('post')]);
  }
}
