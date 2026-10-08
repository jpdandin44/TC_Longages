<?php

declare(strict_types=1);

namespace Drupal\tcl_site\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;

/** Session-protected contact; no ticket or personal message stored in the DB. */
final class ClubContactForm extends FormBase {

  public const RECIPIENT = 'tclongages@gmail.com';

  public function getFormId(): string { return 'tcl_club_contact'; }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $request = $this->getRequest();
    $request->getSession()->start();
    $request->getSession()->set('tcl_contact_form', TRUE);
    $form['#action'] = Url::fromRoute('tcl_site.contact')->toString();
    $form['#cache']['max-age'] = 0;
    $form['#attributes']['class'] = ['contact-form', 'tcl-club-contact'];
    $form['notice'] = ['#plain_text' => 'Envoyez votre message directement depuis le site, sans ouvrir de messagerie. Votre message sera transmis à tclongages@gmail.com pour que le club puisse vous répondre. N’indiquez pas de mot de passe ni d’information confidentielle.'];
    if (Settings::get('tcl_contact_mail_mode', 'disabled') === 'capture') {
      $form['capture_notice'] = ['#type' => 'container', 'text' => ['#plain_text' => 'Essai local : le courriel est capturé sans envoi.']];
    }
    $form['name'] = ['#type' => 'textfield', '#title' => 'Nom', '#required' => TRUE, '#maxlength' => 80, '#required_error' => 'Indiquez votre nom.'];
    $form['reply'] = ['#type' => 'textfield', '#title' => 'E-mail ou téléphone', '#required' => TRUE,
      '#maxlength' => 150, '#description' => 'Indiquez l’adresse (Gmail ou autre) ou le numéro où le club pourra vous répondre.', '#required_error' => 'Indiquez un moyen de vous répondre.'];
    $form['message'] = ['#type' => 'textarea', '#title' => 'Message', '#required' => TRUE, '#rows' => 6, '#maxlength' => 2000, '#required_error' => 'Écrivez votre message.'];
    $form['information'] = ['#type' => 'checkbox', '#title' => 'Je confirme que ces informations peuvent être transmises au club pour répondre à mon message.',
      '#required' => TRUE, '#required_error' => 'Confirmez la transmission au club avant l’envoi.'];
    $form['website'] = ['#type' => 'textfield', '#title' => 'Laisser ce champ vide', '#maxlength' => 100,
      '#wrapper_attributes' => ['class' => ['tcl-contact-honeypot'], 'hidden' => 'hidden'],
      '#attributes' => ['tabindex' => '-1', 'autocomplete' => 'off']];
    $form['contact_token'] = ['#type' => 'hidden', '#default_value' => \Drupal::csrfToken()->get('tcl_club_contact')];
    $form['submission_nonce'] = ['#type' => 'hidden', '#default_value' => bin2hex(random_bytes(16))];
    $form['actions'] = ['#type' => 'actions', 'submit' => ['#type' => 'submit', '#value' => 'Envoyer mon message']];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $request = $this->getRequest();
    $token = $form_state->getValue('contact_token');
    $origin = $request->headers->get('Origin');
    if (!is_string($token) || !\Drupal::csrfToken()->validate($token, 'tcl_club_contact')
      || ($origin !== NULL && $origin !== $request->getSchemeAndHttpHost())) {
      $form_state->setErrorByName('message', 'La session du formulaire a expiré. Rechargez cette page avant de réessayer.');
    }
    foreach (['name' => 80, 'reply' => 150, 'message' => 2000] as $key => $max) {
      $value = $form_state->getValue($key);
      if (!is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > $max
        || str_contains($value, "\0") || ($key !== 'message' && preg_match('/[\r\n]/', $value))) {
        $form_state->setErrorByName($key, 'Vérifiez ce champ et sa longueur.');
      }
    }
    $reply = trim((string) $form_state->getValue('reply'));
    if (preg_match('/[\r\n]/', (string) ($form_state->getUserInput()['reply'] ?? '')) || !(\Drupal::service('email.validator')->isValid($reply)
      || preg_match('/^\+?[0-9 .()\-]{6,40}$/D', $reply) && preg_match('/(?:\D*\d){6}/', $reply))) {
      $form_state->setErrorByName('reply', 'Indiquez une adresse e-mail ou un numéro de téléphone valide.');
    }
    if (($form_state->getUserInput()['information'] ?? '') !== '1') {
      $form_state->setErrorByName('information', 'Confirmez la transmission au club avant l’envoi.');
    }
    if ($form_state->getValue('website') !== ''
      || !preg_match('/^[a-f0-9]{32}$/D', (string) $form_state->getValue('submission_nonce'))) {
      $form_state->setErrorByName('message', 'Le formulaire ne peut pas être accepté. Rechargez cette page.');
    }
    if (!$this->alreadySent($form_state) && !\Drupal::service('flood')->isAllowed('tcl_site.contact', 5, 3600, $this->floodIdentifier())) {
      $form_state->setErrorByName('message', 'Le nombre de messages autorisé est atteint. Réessayez dans une heure.');
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($this->alreadySent($form_state)) { $this->confirmation($form_state); return; }
    $lock = \Drupal::lock();
    $lockId = 'tcl_contact_' . $this->submissionId($form_state);
    if (!$lock->acquire($lockId, 30.0)) {
      $this->messenger()->addWarning('Votre message est déjà en cours de traitement. Patientez avant de réessayer.');
      $form_state->setRebuild(); return;
    }
    try {
      if ($this->alreadySent($form_state)) { $this->confirmation($form_state); return; }
      $mode = Settings::get('tcl_contact_mail_mode', 'disabled');
      $plugin = \Drupal::service('plugin.manager.mail')->getInstance(['module' => 'tcl_site', 'key' => 'contact']);
      $capture = $mode === 'capture' && $plugin instanceof \Drupal\Core\Mail\Plugin\Mail\TestMailCollector;
      $transport = $mode === 'transport' && Settings::get('tcl_contact_transport_qualified', FALSE) === TRUE
        && $plugin instanceof \Drupal\Core\Mail\Plugin\Mail\PhpMail
        && !$plugin instanceof \Drupal\Core\Mail\Plugin\Mail\TestMailCollector;
      if (!$capture && !$transport) { throw new \RuntimeException('Contact transport is unavailable.'); }
      $params = ['_error_message' => FALSE];
      foreach (['name', 'reply', 'message'] as $key) { $params[$key] = trim((string) $form_state->getValue($key)); }
      $reply = \Drupal::service('email.validator')->isValid($params['reply']) ? $params['reply'] : NULL;
      $result = \Drupal::service('plugin.manager.mail')->mail('tcl_site', 'contact', self::RECIPIENT,
        \Drupal::languageManager()->getDefaultLanguage()->getId(), $params, $reply, TRUE);
      if (($result['result'] ?? FALSE) !== TRUE) { throw new \RuntimeException('Contact transport refused the message.'); }
      $session = $this->getRequest()->getSession();
      $sent = $session->get('tcl_contact_sent', []);
      $sent[$this->submissionId($form_state)] = time();
      $session->set('tcl_contact_sent', array_slice($sent, -20, NULL, TRUE));
      \Drupal::service('flood')->register('tcl_site.contact', 3600, $this->floodIdentifier());
      $this->confirmation($form_state);
    }
    catch (\Throwable) {
      $this->messenger()->addError('L’envoi n’a pas pu être confirmé. Votre texte est conservé dans le formulaire ; réessayez ou écrivez à tclongages@gmail.com.');
      $form_state->setRebuild();
      \Drupal::logger('tcl_site')->warning('Envoi du formulaire de contact interrompu ; aucune donnée du message journalisée.');
    }
    finally { $lock->release($lockId); }
  }

  private function submissionId(FormStateInterface $state): string {
    return hash('sha256', (string) $state->getValue('contact_token') . (string) $state->getValue('submission_nonce'));
  }
  private function alreadySent(FormStateInterface $state): bool {
    return isset($this->getRequest()->getSession()->get('tcl_contact_sent', [])[$this->submissionId($state)]);
  }
  private function floodIdentifier(): string { return hash('sha256', (string) $this->getRequest()->getClientIp()); }
  private function confirmation(FormStateInterface $state): void {
    $message = Settings::get('tcl_contact_mail_mode') === 'capture'
      ? 'Essai local réussi : message capturé, sans envoi au club.'
      : 'Votre message a été transmis au service de courriel du club. Merci !';
    $this->messenger()->addStatus($message);
    $state->setRedirect('tcl_site.contact');
  }

}
