<?php

declare(strict_types=1);

namespace Drupal\tcl_support\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;

final class ReportForm extends FormBase {

  public const PAGES = ['/', '/club', '/index.html', '/competitions.html', '/calendrier.html', '/disponibilites.html', '/equipes.html', '/espace.html', '/contact.html'];

  public function getFormId(): string {
    return 'tcl_support_report';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Anonymous Form API forms need their own session-bound CSRF protection.
    $request = $this->getRequest();
    $request->getSession()->start();
    // Keep a minimal user-data entry: metadata alone is discarded by Drupal.
    $request->getSession()->set('tcl_support_form', TRUE);
    $page = $request->query->get('page', '/');
    $page = is_string($page) && in_array($page, self::PAGES, TRUE) ? $page : '/';
    $form['#cache']['max-age'] = 0;
    $form['#attributes']['class'][] = 'tcl-support-form';
    $form['#attached']['library'][] = 'tcl_support/form';
    $form['club'] = ['#type' => 'link', '#title' => '← Retour au site du club', '#url' => Url::fromRoute('tcl_site.front')];
    if (Settings::get('tcl_support_mail_mode', 'disabled') === 'capture') {
      $form['local_notice'] = ['#type' => 'container', '#attributes' => ['class' => ['tcl-support-notice']], 'text' => [
        '#plain_text' => 'Essai local : utilisez des données fictives. Le courriel est capturé ici, sans envoi. Les champs proposés restent à confronter à la stratégie de bêta-test.',
      ]];
    }
    $form['intro'] = ['#type' => 'container', 'text' => ['#plain_text' => 'Un problème rencontré ou une amélioration souhaitée ? Décrivez votre demande. N’indiquez aucun mot de passe ni information confidentielle.']];
    $form['category'] = ['#type' => 'select', '#title' => 'Type de demande', '#required' => TRUE, '#empty_option' => '— Choisir —', '#options' => [
      'problem' => 'Un problème rencontré', 'need' => 'Un besoin ou une amélioration',
    ]];
    $form['subject'] = ['#type' => 'textfield', '#title' => 'Objet de la demande', '#required' => TRUE, '#maxlength' => 180];
    $form['description'] = ['#type' => 'textarea', '#title' => 'Description de la demande', '#required' => TRUE,
      '#rows' => 7, '#maxlength' => 4000, '#description' => 'Pour un problème, précisez les étapes, le résultat attendu et ce qui s’est produit.'];
    $form['page'] = ['#type' => 'textfield', '#title' => 'Page concernée', '#default_value' => $page, '#maxlength' => 64,
      '#attributes' => ['readonly' => 'readonly'], '#description' => 'La page depuis laquelle vous avez ouvert le formulaire.'];
    $form['email'] = ['#type' => 'email', '#title' => 'Votre adresse e-mail pour une réponse (facultatif)', '#maxlength' => 254];
    $form['information'] = ['#type' => 'checkbox', '#title' => 'Je confirme que ces informations peuvent être transmises au club pour traiter ma demande.', '#required' => TRUE,
      '#required_error' => 'Confirmez la transmission au club pour envoyer cette demande.'];
    $form['website'] = ['#type' => 'textfield', '#title' => 'Laisser ce champ vide', '#maxlength' => 100,
      '#wrapper_attributes' => ['class' => ['tcl-support-honeypot']], '#attributes' => ['tabindex' => '-1', 'autocomplete' => 'off']];
    $form['support_token'] = ['#type' => 'hidden', '#default_value' => \Drupal::csrfToken()->get('tcl_support_report')];
    $form['submission_nonce'] = ['#type' => 'hidden', '#default_value' => bin2hex(random_bytes(16))];
    $form['actions'] = ['#type' => 'actions', 'submit' => ['#type' => 'submit', '#value' => 'Envoyer ma demande']];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $request = $this->getRequest();
    $token = $form_state->getValue('support_token');
    $origin = $request->headers->get('Origin');
    if (!is_string($token) || !\Drupal::csrfToken()->validate($token, 'tcl_support_report')
      || ($origin !== NULL && $origin !== $request->getSchemeAndHttpHost())) {
      $form_state->setErrorByName('subject', 'La session du formulaire a expiré. Rechargez cette page avant de réessayer.');
    }
    foreach (['subject' => 180, 'description' => 4000, 'email' => 254] as $field => $max) {
      $value = $form_state->getValue($field);
      if (!is_string($value) || mb_strlen(trim($value)) > $max || str_contains($value, "\0")
        || (in_array($field, ['subject', 'description'], TRUE) && trim($value) === '')) {
        $form_state->setErrorByName($field, 'Vérifiez ce champ et sa longueur.');
      }
    }
    if ($form_state->getValue('email') !== '' && preg_match('/[\r\n]/', (string) $form_state->getValue('email'))) {
      $form_state->setErrorByName('email', 'Adresse e-mail invalide.');
    }
    if (($form_state->getUserInput()['information'] ?? '') !== '1') {
      $form_state->setErrorByName('information', 'Confirmez la transmission au club pour envoyer cette demande.');
    }
    if (!in_array($form_state->getValue('category'), ['problem', 'need'], TRUE)
      || !in_array($form_state->getValue('page'), self::PAGES, TRUE)
      || $form_state->getValue('website') !== ''
      || !preg_match('/^[a-f0-9]{32}$/D', (string) $form_state->getValue('submission_nonce'))) {
      $form_state->setErrorByName('subject', 'Le formulaire ne peut pas être accepté. Rechargez cette page.');
    }
    // An already recorded POST is a confirmation, not a new quota-consuming request.
    $already_recorded = !$form_state->hasAnyErrors() && \Drupal::service('tcl_support.repository')
      ->findSubmission(hash('sha256', $token . $form_state->getValue('submission_nonce'))) !== FALSE;
    if (!$already_recorded && !\Drupal::service('flood')->isAllowed('tcl_support.submit', 5, 3600, $this->floodIdentifier())) {
      $form_state->setErrorByName('subject', 'Le nombre de demandes autorisé est atteint. Réessayez dans une heure.');
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = [];
    foreach (['category', 'subject', 'description', 'page', 'email'] as $field) {
      $values[$field] = trim((string) $form_state->getValue($field));
    }
    $values['submission_id'] = hash('sha256', $form_state->getValue('support_token') . $form_state->getValue('submission_nonce'));
    try {
      $result = \Drupal::service('tcl_support.repository')->create($values);
    }
    catch (\Throwable) {
      $this->messenger()->addError('La demande n’a pas pu être confirmée. Conservez votre texte et réessayez.');
      $form_state->setRebuild();
      \Drupal::logger('tcl_support')->error('Enregistrement de demande interrompu.');
      return;
    }
    if ($result['created']) {
      try {
        \Drupal::service('flood')->register('tcl_support.submit', 3600, $this->floodIdentifier());
        \Drupal::service('tcl_support.notifier')->send($result['id']);
      }
      catch (\Throwable) {
        // The request is already durable. Leave its pending notification visible.
        \Drupal::logger('tcl_support')->warning('Demande enregistrée ; notification à reprendre dans le suivi.');
      }
    }
    $this->messenger()->addStatus($this->t('Votre demande n° @number est enregistrée. Merci pour votre contribution.', ['@number' => $result['id']]));
    $form_state->setRedirect('tcl_support.report');
  }

  private function floodIdentifier(): string {
    return hash('sha256', (string) $this->getRequest()->getClientIp());
  }

}
