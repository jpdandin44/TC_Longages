<?php

declare(strict_types=1);

namespace Drupal\tcl_support\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tcl_support\SupportRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class RequestForm extends FormBase {

  public function getFormId(): string {
    return 'tcl_support_request';
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?string $request_id = NULL): array {
    $repository = \Drupal::service('tcl_support.repository');
    $ticket = $repository->load((int) $request_id);
    if (!$ticket) {
      throw new NotFoundHttpException();
    }
    $form_state->set('request_id', (int) $request_id);
    $form['#cache']['max-age'] = 0;
    $form['#attached']['library'][] = 'tcl_support/tracker';
    $form['back'] = ['#type' => 'link', '#title' => '← Toutes les demandes', '#url' => Url::fromRoute('tcl_support.tracker')];
    $form['original'] = ['#type' => 'details', '#title' => 'Demande #' . $ticket->id . ' — ' . $ticket->subject, '#open' => TRUE];
    foreach (['page' => 'Page concernée', 'email' => 'Adresse pour une réponse', 'description' => 'Description'] as $field => $label) {
      $form['original'][$field] = ['#type' => 'item', '#title' => $label, '#plain_text' => $ticket->$field ?: 'Non renseignée',
        '#wrapper_attributes' => ['style' => 'white-space:pre-wrap;overflow-wrap:anywhere']];
    }
    $form['notification'] = ['#type' => 'item', '#title' => 'Notification', '#plain_text' => SupportRepository::NOTIFICATIONS[$ticket->notification]];
    $form['revision'] = ['#type' => 'hidden', '#default_value' => $ticket->revision];
    $form['status'] = ['#type' => 'select', '#title' => 'État de la demande', '#options' => SupportRepository::STATUSES, '#default_value' => $ticket->status, '#required' => TRUE];
    $form['owner'] = ['#type' => 'textfield', '#title' => 'Responsable du traitement', '#maxlength' => 120, '#default_value' => $ticket->owner];
    $form['note'] = ['#type' => 'textarea', '#title' => 'Note de suivi privée', '#required' => TRUE, '#maxlength' => 4000,
      '#description' => 'Décrivez la décision, la correction ou l’information attendue. Cette note reste dans le suivi.'];
    $form['retry'] = ['#type' => 'checkbox', '#title' => 'Réessayer la notification à support@tclongages.fr',
      '#description' => 'En développement, ce courriel est seulement capturé. En production, cette action exige un transport préalablement qualifié.'];
    $rows = [];
    foreach ($repository->history((int) $request_id) as $event) {
      $rows[] = [date('d/m/Y H:i', (int) $event->created),
        $event->actor_uid ? 'Compte Drupal #' . $event->actor_uid : 'Formulaire / système',
        SupportRepository::STATUSES[$event->state] ?? SupportRepository::NOTIFICATIONS[$event->state] ?? $event->state,
        ['data' => ['#plain_text' => $event->note]],
      ];
    }
    $form['history'] = ['#type' => 'container', '#attributes' => ['class' => ['tcl-support-table-wrap'], 'role' => 'region', 'aria-label' => 'Historique de traitement', 'tabindex' => '0'],
      'table' => ['#type' => 'table', '#caption' => 'Historique', '#header' => ['Date', 'Auteur', 'État', 'Note'], '#rows' => $rows]];
    $form['actions'] = ['#type' => 'actions', 'save' => ['#type' => 'submit', '#value' => 'Enregistrer le traitement']];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (!isset(SupportRepository::STATUSES[$form_state->getValue('status')])
      || mb_strlen(trim((string) $form_state->getValue('note'))) < 1
      || mb_strlen((string) $form_state->getValue('note')) > 4000
      || mb_strlen((string) $form_state->getValue('owner')) > 120
      || !ctype_digit((string) $form_state->getValue('revision'))) {
      $form_state->setErrorByName('note', 'Le traitement et sa note doivent être renseignés correctement.');
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $id = $form_state->get('request_id');
    $changed = \Drupal::service('tcl_support.repository')->update($id, (int) $form_state->getValue('revision'),
      $form_state->getValue('status'), trim((string) $form_state->getValue('owner')),
      trim((string) $form_state->getValue('note')), (int) $this->currentUser()->id());
    if (!$changed) {
      $this->messenger()->addError('Cette demande a été modifiée entre-temps. Rechargez-la avant de reprendre le traitement.');
      $form_state->setRebuild();
      return;
    }
    if ($form_state->getValue('retry')) {
      \Drupal::service('tcl_support.notifier')->send($id, (int) $this->currentUser()->id());
    }
    $this->messenger()->addStatus('Traitement et note de suivi enregistrés.');
    $form_state->setRedirect('tcl_support.edit', ['request_id' => $id]);
  }

}
