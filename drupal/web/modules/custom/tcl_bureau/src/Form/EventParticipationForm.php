<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tcl_bureau\EventParticipation as Replies;
use Drupal\tcl_bureau\EventSchedule;

final class EventParticipationForm extends FormBase {
  public function getFormId(): string { return 'tcl_event_participation'; }
  public function buildForm(array $form, FormStateInterface $form_state, ?string $event = NULL): array {
    $record = Replies::manage($this->currentUser(), $event);
    $dates = array_column(EventSchedule::inspect($record)['occurrences'], 'date');
    $date = (string) \Drupal::request()->query->get('date', $dates[0]);
    if (!in_array($date, $dates, TRUE)) { throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(); }
    $form_state->set('event', $event); $form_state->set('date', $date);
    $row = Replies::read($this->currentUser(), $event, $date);
    $form['#attributes']['class'] = ['tcl-bureau']; $form['#cache']['max-age'] = 0;
    $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['back'] = ['#type' => 'link', '#title' => 'Retour à l’agenda', '#url' => Url::fromRoute('tcl_bureau.events')];
    $form['title'] = ['#type' => 'html_tag', '#tag' => 'h2', 'text' => ['#plain_text' => $record['title'] . ' — ' . $date]];
    $form['notice'] = ['#markup' => '<p>Recette : noms fictifs et adresses de test uniquement. Les liens individuels durent sept jours. Régénérer un lien remplace le précédent sans effacer la réponse. Aucun courriel envoyé depuis cet écran.</p>'];
    $form['dates'] = ['#type' => 'details', '#title' => 'Choisir une date de cette série'];
    foreach ($dates as $item) { $form['dates'][$item] = ['#type' => 'link', '#title' => $item, '#url' => Url::fromRoute('tcl_bureau.event_participation', ['event' => $event], ['query' => ['date' => $item]]), '#suffix' => '<br>']; }
    $totals = Replies::totals($row, $record['revision']);
    $form['total'] = ['#type' => 'html_tag', '#tag' => 'h3', '#value' => $totals['people'] . ' personne(s) confirmée(s)'];
    $form['summary'] = ['#plain_text' => 'Oui : ' . $totals['yes'] . ' · Non : ' . $totals['no'] . ' · Incertains : ' . $totals['maybe'] . ' · Sans réponse : ' . $totals['unknown'] . ' · À reconfirmer : ' . $totals['reconfirm']];
    $rows = [];
    foreach ($row['people'] as $person) { $rows[] = [['data' => ['#plain_text' => $person['name']]], ['data' => ['#plain_text' => $person['email']]], Replies::ANSWERS[$person['answer']], $person['count'], $person['answer'] !== 'unknown' && $person['event_revision'] !== $record['revision'] ? 'À reconfirmer' : '']; }
    $form['people'] = ['#type' => 'table', '#header' => ['Nom', 'Courriel de recette', 'Réponse', 'Personnes', 'Suivi'], '#rows' => $rows, '#empty' => 'Aucun participant invité.'];
    if ($record['status'] === 'cancelled') { $form['closed'] = ['#plain_text' => 'Événement annulé : aucune nouvelle réponse possible.']; return $form; }
    $form['revision'] = ['#type' => 'hidden', '#default_value' => $row['revision']];
    $form['open'] = ['#type' => 'checkbox', '#title' => 'Ouvrir les réponses pour cette date', '#default_value' => $row['open']];
    $form['deadline'] = ['#type' => 'date', '#title' => 'Date limite de réponse (fin de journée, Paris)', '#default_value' => $row['deadline'] ?: $date];
    $options = [0 => 'Aucune — liens individuels seulement'];
    foreach (\Drupal::service('tcl_bureau.communication')->published() as $post) { $options[$post['id']] = $post['title']; }
    $form['registration_post'] = ['#type' => 'select', '#title' => 'Actualité avec inscription', '#options' => $options, '#default_value' => isset($options[$row['registration_post'] ?? 0]) ? ($row['registration_post'] ?? 0) : 0, '#description' => 'Associez une actualité publiée à cette seule date. Son bouton et ses courriels mèneront à une demande de lien personnel par email. Plusieurs dates sur la même actualité désactivent le bouton.'];
    $form['configure'] = ['#type' => 'submit', '#value' => 'Enregistrer l’ouverture', '#name' => 'configure'];
    $form['name'] = ['#type' => 'textfield', '#title' => 'Nom fictif du participant', '#maxlength' => 100];
    $form['email'] = ['#type' => 'email', '#title' => 'Courriel fictif ou de recette autorisé'];
    $form['invite'] = ['#type' => 'submit', '#value' => 'Créer ou remplacer le lien individuel', '#name' => 'invite'];
    if ($form_state->get('link')) { $form['link'] = ['#type' => 'textarea', '#title' => 'Lien personnel — à transmettre uniquement à ce participant', '#value' => $form_state->get('link'), '#attributes' => ['readonly' => 'readonly']]; }
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try {
      if (($form_state->getTriggeringElement()['#name'] ?? '') === 'configure') {
        Replies::configure($this->currentUser(), $form_state->get('event'), $form_state->get('date'), (int) $form_state->getValue('revision'), (bool) $form_state->getValue('open'), (string) $form_state->getValue('deadline'), (int) $form_state->getValue('registration_post'));
        $this->messenger()->addStatus('Ouverture des réponses enregistrée.');
      } else {
        $token = Replies::invite($this->currentUser(), $form_state->get('event'), $form_state->get('date'), (string) $form_state->getValue('name'), (string) $form_state->getValue('email'));
        $form_state->set('link', Url::fromRoute('tcl_bureau.event_response', [], ['absolute' => TRUE])->toString() . '#' . $token);
        $this->messenger()->addStatus('Lien créé. Aucun courriel envoyé.');
      }
    } catch (\InvalidArgumentException | \Symfony\Component\HttpKernel\Exception\ConflictHttpException $e) { $this->messenger()->addError($e->getMessage()); }
    $input = $form_state->getUserInput(); unset($input['revision']); $form_state->setUserInput($input);
    $form_state->setRebuild();
  }
}
