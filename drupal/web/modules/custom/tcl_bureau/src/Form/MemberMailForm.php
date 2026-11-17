<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tcl_bureau\MemberContacts;
use Drupal\tcl_bureau\MemberMail;
use Drupal\tcl_bureau\RecipeMail;

final class MemberMailForm extends FormBase {
  private function selectionOnly(): bool {
    return $this->getRequest()->getHost() === 'preprod.tclongages.fr';
  }

  public function getFormId(): string { return 'tcl_member_mail'; }
  public function buildForm(array $form, FormStateInterface $form_state, ?string $post = NULL): array {
    $row = MemberMail::post($this->currentUser(), (int) $post);
    $form_state->set('post', (int) $post);
    $form['#cache']['max-age'] = 0; $form['#attributes']['class'] = ['tcl-bureau'];
    $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['back'] = ['#type' => 'link', '#title' => 'Retour à la communication', '#url' => Url::fromRoute('tcl_bureau.communication_review', ['post' => $post])];
    $production = MemberContacts::production();
    $selectionOnly = $this->selectionOnly();
    $snapshot = $form_state->get('snapshot');
    $source = $form_state->get('source_label');
    if ($selectionOnly) {
      $snapshot = MemberMail::snapshot($this->currentUser(), (int) $post, []);
      $snapshot['recipients'] = ['adherent-01@example.invalid', 'adherent-02@example.invalid', 'adherent-03@example.invalid', 'adherent-04@example.invalid'];
      $source = 'Adhérents fictifs';
      $form['help'] = ['#plain_text' => 'Testez la sélection sur quatre adhérents fictifs. L’envoi est désactivé.'];
    } elseif ($production) {
      try {
        $directory = MemberContacts::deliveryDirectory();
      } catch (\RuntimeException $e) {
        $form['directory'] = ['#plain_text' => $e->getMessage()];
        return $form;
      }
      $form['directory'] = ['#plain_text' => $directory['group'] . ' — ' . count($directory['recipients']) . ' destinataires disponibles.'];
    } else {
      $form['help'] = ['#markup' => '<p>Envoi de recette uniquement, individuel, sans exposer les autres destinataires. La liste autorisée reste obligatoire même avec Google Contacts. L’affiche n’est pas jointe : un lien vers le support est inclus si cette actualité est publiée sur le site.</p>'];
      $form['google_state'] = ['#plain_text' => MemberContacts::configured() ? 'Connexion Google configurée ; lecture à vérifier lors de la préparation.' : 'Google Contacts non connecté. Le libellé adhérents et la connexion en lecture seule restent à configurer.'];
      $form['source'] = ['#type' => 'radios', '#title' => 'Source des destinataires', '#options' => ['recipe' => 'Adresses de recette autorisées', 'google' => 'Libellé adhérents dans Google Contacts'], '#default_value' => 'recipe'];
    }
    $form['prepare'] = ['#access' => !$selectionOnly, '#type' => 'submit', '#name' => 'prepare', '#value' => 'Préparer l’aperçu'];
    if ($snapshot) {
      $form['preview'] = ['#type' => 'details', '#title' => 'Aperçu avant envoi', '#open' => TRUE];
      $form['preview']['source'] = ['#plain_text' => $source . ' — Révision ' . $snapshot['revision'] . '. Exclus : ' . $snapshot['excluded'] . '.'];
      $form['preview']['subject'] = ['#type' => 'textfield', '#title' => 'Objet', '#value' => ($production ? '' : '[RECETTE PRÉPRODUCTION] ') . $snapshot['subject'], '#attributes' => ['readonly' => 'readonly']];
      $form['preview']['body'] = ['#type' => 'textarea', '#title' => 'Version texte (liens personnels ajoutés à l’envoi)', '#value' => $snapshot['body'], '#attributes' => ['readonly' => 'readonly'], '#rows' => 10];
      $responses = (bool) \Drupal\tcl_bureau\EventParticipation::mailEntry($this->currentUser(), (int) $post);
      $form['preview']['visual'] = ['#markup' => \Drupal\Core\Render\Markup::create(MemberMail::html($row, [], $responses))];
      $form['preview']['personal_help'] = ['#access' => $responses, '#plain_text' => 'Aperçu sans lien personnel : les trois boutons deviennent actifs dans le courriel individuel. Leur ouverture ne modifie aucune réponse.'];
      $labels = ['not_attempted' => 'Non envoyé', 'accepted' => 'Accepté par le serveur', 'failed' => 'Échec — aucune relance automatique', 'pending' => 'Résultat incertain — aucune relance automatique'];
      $rows = [];
      foreach (MemberMail::history($this->currentUser(), $snapshot) as $entry) { $rows[] = [['data' => ['#plain_text' => $entry['email']]], $labels[$entry['status']]]; }
      $form['preview']['history'] = ['#type' => 'table', '#header' => ['Adresse', 'Suivi de cette révision'], '#rows' => $rows];
      $options = array_combine($snapshot['recipients'], $snapshot['recipients']);
      if ($options) {
        $form['#attached']['library'][] = 'tcl_bureau/recipient_selection';
        $form['selection'] = ['#type' => 'container', '#attributes' => ['data-recipient-selection' => ''], '#optional' => FALSE];
      }
      $form['recipients'] = ['#type' => 'checkboxes', '#title' => $production ? 'Destinataires' : 'Destinataires à tester', '#options' => $options, '#description' => 'Sélectionnez explicitement les adresses à contacter. Aucune case présélectionnée.'];
      $form['recipients']['#prefix'] = '<div data-recipient-list>';
      $form['recipients']['#suffix'] = '</div>';
      if ($selectionOnly) { return $form; }
      $form['confirm'] = ['#type' => 'checkbox', '#title' => 'J’ai relu le message et les destinataires et je confirme cet envoi.'];
      $form['send'] = ['#type' => 'submit', '#name' => 'send', '#value' => $production ? 'Envoyer aux adhérents sélectionnés' : 'Envoyer les courriels de recette', '#disabled' => !$snapshot['recipients']];
    }
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($this->selectionOnly()) { $form_state->setRebuild(); return; }
    try {
      if (($form_state->getTriggeringElement()['#name'] ?? '') === 'send') {
        $snapshot = $form_state->get('snapshot');
        $selected = array_values(array_filter($form_state->getValue('recipients', [])));
        if (!$snapshot || !$selected || !$form_state->getValue('confirm') || array_diff($selected, $snapshot['recipients'])) { throw new \InvalidArgumentException('Choisissez les destinataires et confirmez la relecture.'); }
        $snapshot['recipients'] = $selected;
        $r = MemberMail::send($this->currentUser(), $snapshot);
        $this->messenger()->addStatus('Acceptés par le serveur : ' . $r['accepted'] . '. Échecs : ' . $r['failed'] . '. Non relancés : ' . $r['skipped'] . '. La réception doit être vérifiée dans la boîte mail.');
        $form_state->set('snapshot', NULL);
      } else {
        $form_state->set('snapshot', NULL);
        $google = $form_state->getValue('source') === 'google';
        $contacts = MemberContacts::production() ? MemberContacts::deliveryDirectory() : ($google ? MemberContacts::recipients() : ['recipients' => array_map(static fn($email) => ['email' => $email], RecipeMail::settings()['allowed_recipients'] ?? []), 'excluded' => 0, 'group' => 'Liste de recette']);
        $snapshot = MemberMail::snapshot($this->currentUser(), $form_state->get('post'), $contacts['recipients']);
        $snapshot['excluded'] += $contacts['excluded'];
        $form_state->set('snapshot', $snapshot); $form_state->set('source_label', $contacts['group']);
        $input = $form_state->getUserInput(); unset($input['confirm'], $input['recipients']); $form_state->setUserInput($input);
      }
    } catch (\InvalidArgumentException | \RuntimeException $e) { $this->messenger()->addError($e->getMessage()); }
    $form_state->setRebuild();
  }
}
