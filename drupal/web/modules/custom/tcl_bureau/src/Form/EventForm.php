<?php

declare(strict_types=1);
namespace Drupal\tcl_bureau\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tcl_bureau\EventRepository;
use Drupal\tcl_bureau\EventSchedule;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class EventForm extends FormBase {
  // FormBase must serialize this service when a preview rebuilds the form.
  public function __construct(protected EventRepository $events) {}
  public static function create(ContainerInterface $container) { return new static($container->get('tcl_bureau.events')); }
  public function getFormId(): string { return 'tcl_bureau_event'; }
  public function buildForm(array $form, FormStateInterface $form_state, ?string $event = NULL): array {
    $state = $form_state;
    if (!$this->events->allowed($this->currentUser())) { throw new AccessDeniedHttpException(); }
    // Route identity is trusted; the submitted revision is checked against storage.
    if ($state->get('event_record') === NULL) {
      $state->set('event_record', $event ? $this->events->get($this->currentUser(), $event) : ['id' => bin2hex(random_bytes(16)), 'revision' => 0, 'status' => 'scheduled', 'frequency' => 'once']);
    }
    $row = $state->get('event_record');
    if (!$event && !$state->isRebuilding() && !$state->isSubmitted()) {
      $date = $this->getRequest()->query->get('date', '');
      $parsed = is_string($date) ? \DateTimeImmutable::createFromFormat('!Y-m-d', $date) : FALSE;
      if ($parsed && $parsed->format('Y-m-d') === $date) {
        $row['start_date'] = $row['end_date'] = $date;
      }
    }
    $form['#cache']['max-age'] = 0;
    $form['#attributes']['class'][] = 'tcl-bureau';
    $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['revision'] = ['#type' => 'hidden', '#default_value' => $row['revision']];
    $form['submission_id'] = ['#type' => 'hidden', '#default_value' => $row['id']];
    $form['back'] = ['#type' => 'link', '#title' => 'Retour aux événements', '#url' => Url::fromRoute('tcl_bureau.events')];
    $form['calendar'] = ['#type' => 'link', '#title' => 'Retour au calendrier du site', '#url' => Url::fromUserInput('/calendrier.html', ['fragment' => 'agenda-bureau'])];
    $form['notice'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'Recette fictive, sans diffusion. Horaires Europe/Paris. Les modifications et le statut Annulé concernent toute la série, y compris ses dates passées. Pour retirer une seule occurrence, renseignez sa date dans Dates exclues.'];
    if (\Drupal\tcl_bureau\GoogleCalendarConnection::target()) {
      $form['notice']['#value'] = 'Enregistrement dans Google « TC Longages — Recette V2 », sans invitation ni calendrier officiel. Données fictives, horaires Europe/Paris. Modifier agit sur toute la série. Annulé ajoute [ANNULÉ] dans Google et libère le créneau, sans suppression.';
      $form['google_status'] = ['#plain_text' => !empty($row['google']) ? 'Cet événement est relié au calendrier Google de recette.' : 'Cet événement sera ajouté à Google lors de son prochain enregistrement.'];
    }
    foreach (['title' => ['Titre', 120], 'location' => ['Lieu', 200]] as $field => [$label, $max]) {
      $form[$field] = ['#type' => 'textfield', '#title' => $label, '#maxlength' => $max, '#required' => $field === 'title', '#default_value' => $row[$field] ?? ''];
    }
    $options = [];
    foreach (EventRepository::CATEGORIES as $key => [$label, $icon]) { $options[$key] = $icon . ' ' . $label; }
    $form['category'] = ['#type' => 'select', '#title' => 'Type de rendez-vous', '#options' => $options, '#default_value' => $row['category'] ?? 'other'];
    $posters = ['0' => 'Aucune affiche — utiliser l’icône de la catégorie'];
    $communications = \Drupal::service('tcl_bureau.communication');
    foreach ($communications->list($this->currentUser()) as $post) {
      $full = $communications->get($this->currentUser(), (int) $post['id']);
      if ($full && $full['image_data'] !== '') { $posters[(string) $post['id']] = $post['title']; }
    }
    $form['poster_post'] = ['#type' => 'select', '#title' => 'Affiche de la communication', '#options' => $posters, '#default_value' => isset($posters[$row['poster_post'] ?? '0']) ? ($row['poster_post'] ?? '0') : '0', '#description' => 'Réutilise l’affiche PDF ou image importée dans Communication. Les changements de cette affiche seront aussi visibles ici. Aucune publication déclenchée.'];
    $form['communication_link'] = ['#type' => 'link', '#title' => 'Gérer les communications et importer une affiche', '#url' => Url::fromRoute('tcl_bureau.communication')];
    $form['description'] = ['#type' => 'textarea', '#title' => 'Description', '#maxlength' => 3000, '#default_value' => $row['description'] ?? ''];
    foreach (['start' => 'Début', 'end' => 'Fin'] as $prefix => $label) {
      $form[$prefix . '_date'] = ['#type' => 'date', '#title' => $label . ' — date', '#required' => TRUE, '#default_value' => $row[$prefix . '_date'] ?? ''];
      $form[$prefix . '_time'] = ['#type' => 'textfield', '#title' => $label . ' — heure (HH:MM)', '#required' => TRUE, '#maxlength' => 5, '#size' => 8, '#default_value' => $row[$prefix . '_time'] ?? '', '#attributes' => ['placeholder' => '18:30', 'pattern' => '[0-9]{2}:[0-9]{2}']];
    }
    $form['frequency'] = ['#type' => 'select', '#title' => 'Répétition', '#options' => EventSchedule::FREQUENCIES, '#default_value' => $row['frequency']];
    $form['until'] = ['#type' => 'date', '#title' => 'Répéter jusqu’au (inclus)', '#default_value' => $row['until'] ?? '', '#description' => 'Obligatoire si répétition, au maximum un an. Pour le 29, 30 ou 31 mensuel, les mois sans ce jour sont ignorés.'];
    $form['excluded'] = ['#type' => 'textarea', '#title' => 'Dates exclues', '#default_value' => $row['excluded'] ?? '', '#maxlength' => 5000, '#description' => 'Une date de début par ligne, au format AAAA-MM-JJ. Pour déplacer une seule occurrence, excluez sa date puis créez un événement séparé.'];
    $form['status'] = ['#type' => 'select', '#title' => 'État de toute la série', '#options' => EventRepository::STATUSES, '#default_value' => $row['status']];
    $form['confirm'] = ['#type' => 'checkbox', '#title' => 'Je confirme ces données fictives et la modification de toute la série.'];
    $form['actions']['#type'] = 'actions';
    $form['actions']['preview'] = ['#type' => 'submit', '#value' => 'Vérifier les dates', '#name' => 'preview'];
    $form['actions']['save'] = ['#type' => 'submit', '#value' => 'Enregistrer l’événement', '#name' => 'save'];
    $preview = $state->get('schedule_preview');
    if ($preview === NULL && $row['revision'] > 0) { $preview = EventSchedule::inspect($row)['occurrences']; }
    if ($preview !== NULL) {
      $rows = array_map(static fn(array $item): array => [(new \DateTimeImmutable($item['start']))->format('d/m/Y H:i'), (new \DateTimeImmutable($item['end']))->format('d/m/Y H:i')], $preview);
      $form['preview'] = ['#type' => 'details', '#title' => count($rows) . ' date(s) calculée(s) — vérifier avant enregistrement', '#open' => TRUE, 'table' => ['#type' => 'table', '#header' => ['Début — Paris', 'Fin — Paris'], '#rows' => $rows]];
    }
    return $form;
  }
  private function input(FormStateInterface $state): array {
    $values = [];
    foreach ([...EventSchedule::FIELDS, 'status', 'category', 'poster_post'] as $field) {
      $value = $state->getValue($field, '');
      $values[$field] = is_string($value) ? trim($value) : $value;
    }
    return $values;
  }
  public function validateForm(array &$form, FormStateInterface $state): void {
    $input = $this->input($state);
    if (!preg_match('/^[a-f0-9]{32}$/D', (string) $state->getValue('submission_id')) || !ctype_digit((string) $state->getValue('revision'))) { $state->setErrorByName('title', 'Rechargez le formulaire avant de réessayer.'); }
    foreach ($this->events->presentationErrors($this->currentUser(), $input) as $field => $error) { $state->setErrorByName($field, $error); }
    foreach (EventSchedule::inspect($input)['errors'] as $field => $error) { $state->setErrorByName($field, $error); }
    if (($state->getTriggeringElement()['#name'] ?? '') === 'save' && !$state->getValue('confirm')) { $state->setErrorByName('confirm', 'Confirmez les données fictives et la portée de la modification.'); }
  }
  public function submitForm(array &$form, FormStateInterface $state): void {
    $input = $this->input($state);
    if (($state->getTriggeringElement()['#name'] ?? '') === 'preview') {
      $state->set('schedule_preview', EventSchedule::inspect($input)['occurrences']);
      $state->setRebuild(); return;
    }
    $row = $state->get('event_record');
    try { $id = $this->events->save($this->currentUser(), $row['revision'] > 0 ? $row['id'] : (string) $state->getValue('submission_id'), $row['revision'] > 0 ? (int) $state->getValue('revision') : 0, $input); }
    catch (ConflictHttpException $error) { $this->messenger()->addError($error->getMessage()); $state->setRebuild(); return; }
    catch (\RuntimeException $error) { $this->messenger()->addError($error->getMessage()); $state->setRebuild(); return; }
    $this->messenger()->addStatus(\Drupal\tcl_bureau\GoogleCalendarConnection::target() ? 'Événement enregistré dans Google « TC Longages — Recette V2 ». Aucune invitation envoyée.' : 'Événement de recette enregistré. Aucune diffusion effectuée.');
    $state->setRedirect('tcl_bureau.event_edit', ['event' => $id]);
  }
}
