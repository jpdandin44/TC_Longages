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
  public function getFormId(): string { return 'tcl_member_mail'; }
  public function buildForm(array $form, FormStateInterface $form_state, ?string $post = NULL): array {
    $row = MemberMail::post($this->currentUser(), (int) $post);
    $form_state->set('post', (int) $post);
    $form['#cache']['max-age'] = 0; $form['#attributes']['class'] = ['tcl-bureau'];
    $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['back'] = ['#type' => 'link', '#title' => 'Retour à la communication', '#url' => Url::fromRoute('tcl_bureau.communication_review', ['post' => $post])];
    $form['help'] = ['#markup' => '<p>Envoi de recette uniquement, individuel, sans exposer les autres destinataires. La liste autorisée reste obligatoire même avec Google Contacts. L’affiche n’est pas jointe : un lien vers le support est inclus si cette actualité est publiée sur le site.</p>'];
    $form['google_state'] = ['#plain_text' => MemberContacts::configured() ? 'Connexion Google configurée ; lecture à vérifier lors de la préparation.' : 'Google Contacts non connecté. Le libellé adhérents et la connexion en lecture seule restent à configurer.'];
    $form['source'] = ['#type' => 'radios', '#title' => 'Source des destinataires', '#options' => ['recipe' => 'Adresses de recette autorisées', 'google' => 'Libellé adhérents dans Google Contacts'], '#default_value' => 'recipe'];
    $form['prepare'] = ['#type' => 'submit', '#name' => 'prepare', '#value' => 'Préparer l’aperçu'];
    $snapshot = $form_state->get('snapshot');
    if ($snapshot) {
      $form['preview'] = ['#type' => 'details', '#title' => 'Aperçu avant envoi', '#open' => TRUE];
      $form['preview']['source'] = ['#plain_text' => $form_state->get('source_label') . ' — Révision ' . $snapshot['revision'] . '. Exclus : ' . $snapshot['excluded'] . '.'];
      $form['preview']['subject'] = ['#type' => 'textfield', '#title' => 'Objet', '#value' => '[RECETTE PRÉPRODUCTION] ' . $snapshot['subject'], '#attributes' => ['readonly' => 'readonly']];
      $form['preview']['body'] = ['#type' => 'textarea', '#title' => 'Message exact', '#value' => $snapshot['body'], '#attributes' => ['readonly' => 'readonly'], '#rows' => 10];
      $labels = ['not_attempted' => 'Non envoyé', 'accepted' => 'Accepté par le serveur', 'failed' => 'Échec — aucune relance automatique', 'pending' => 'Résultat incertain — aucune relance automatique'];
      $rows = [];
      foreach (MemberMail::history($this->currentUser(), $snapshot) as $entry) { $rows[] = [['data' => ['#plain_text' => $entry['email']]], $labels[$entry['status']]]; }
      $form['preview']['history'] = ['#type' => 'table', '#header' => ['Adresse', 'Suivi de cette révision'], '#rows' => $rows];
      $options = array_combine($snapshot['recipients'], $snapshot['recipients']);
      $form['recipients'] = ['#type' => 'checkboxes', '#title' => 'Destinataires à tester', '#options' => $options, '#description' => 'Sélectionnez explicitement les adresses à contacter. Aucune case présélectionnée.'];
      $form['confirm'] = ['#type' => 'checkbox', '#title' => 'J’ai relu le message et les destinataires et je confirme cet envoi de recette.'];
      $form['send'] = ['#type' => 'submit', '#name' => 'send', '#value' => 'Envoyer les courriels de recette', '#disabled' => !$snapshot['recipients']];
    }
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state): void {
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
        $contacts = $google ? MemberContacts::recipients() : ['recipients' => array_map(static fn($email) => ['email' => $email], RecipeMail::settings()['allowed_recipients'] ?? []), 'excluded' => 0, 'group' => 'Liste de recette'];
        $snapshot = MemberMail::snapshot($this->currentUser(), $form_state->get('post'), $contacts['recipients']);
        $snapshot['excluded'] += $contacts['excluded'];
        $form_state->set('snapshot', $snapshot); $form_state->set('source_label', $contacts['group']);
        $input = $form_state->getUserInput(); unset($input['confirm'], $input['recipients']); $form_state->setUserInput($input);
      }
    } catch (\InvalidArgumentException | \RuntimeException $e) { $this->messenger()->addError($e->getMessage()); }
    $form_state->setRebuild();
  }
}
