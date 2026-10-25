<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tcl_bureau\CommunicationImage;
use Drupal\tcl_bureau\CommunicationRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CommunicationForm extends FormBase {
  private ?int $postId = NULL;

  public function __construct(protected CommunicationRepository $repository) {}

  public static function create(ContainerInterface $container) {
    return new static($container->get('tcl_bureau.communication'));
  }

  public function getFormId(): string {
    return 'tcl_bureau_communication_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?string $post = NULL): array {
    if (!$this->repository->allowed($this->currentUser())) {
      throw new AccessDeniedHttpException();
    }
    $this->postId = $post !== NULL ? (int) $post : NULL;
    $record = $this->postId !== NULL ? $this->repository->get($this->currentUser(), $this->postId) : NULL;
    if ($this->postId !== NULL && (!$record || $record['status'] === 'archived')) {
      throw new NotFoundHttpException();
    }
    $form['#attributes']['class'][] = 'tcl-bureau';
    $form['#attributes']['class'][] = 'tcl-communication-editor';
    $form['navigation'] = ['#type' => 'html_tag', '#tag' => 'nav', '#attributes' => ['aria-label' => 'Navigation du Bureau'],
      'home' => ['#type' => 'link', '#title' => 'Accueil du Bureau', '#url' => Url::fromRoute('tcl_bureau.registrations')],
      'communications' => ['#type' => 'link', '#title' => 'Communications', '#url' => Url::fromRoute('tcl_bureau.communication')],
    ];
    $form['#attached']['library'][] = 'tcl_bureau/communication';
    $form['#attached']['library'][] = 'tcl_bureau/poster_preview';
    $form['#cache']['max-age'] = 0;
    $form['intro'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'Enregistrez un brouillon, relisez les aperçus, validez puis utilisez les canaux choisis. Toute modification enregistrée retire la publication et demande une nouvelle validation.'];
    $form['submission_key'] = ['#type' => 'hidden', '#default_value' => $record['submission_key'] ?? bin2hex(random_bytes(16))];
    $form['revision'] = ['#type' => 'hidden', '#default_value' => $record['revision'] ?? 0];
    $form['title'] = ['#type' => 'textfield', '#title' => 'Titre', '#required' => TRUE, '#required_error' => 'Le titre est obligatoire.', '#maxlength' => 160, '#default_value' => $record['title'] ?? ''];
    $form['category'] = ['#type' => 'select', '#title' => 'Catégorie', '#options' => CommunicationRepository::CATEGORIES, '#default_value' => $record['category'] ?? 'club'];
    $form['channels'] = ['#type' => 'checkboxes', '#title' => 'Canaux de publication', '#options' => CommunicationRepository::CHANNELS, '#default_value' => explode(',', $record['channels'] ?? 'site'), '#description' => 'Choisissez au moins un canal. Facebook et WhatsApp : partage manuel après validation. Enregistrer ne publie rien.'];
    $form['body'] = ['#type' => 'textarea', '#title' => 'Texte de l’actualité', '#description' => '5 000 caractères maximum. Facultatif si une affiche est jointe.', '#default_value' => $record['body'] ?? ''];
    $variants = \Drupal\tcl_bureau\ChannelContent::decode($record['channel_texts'] ?? NULL);
    $form['channel_help'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'Choisissez un canal pour adapter son message. Les champs laissés vides reprennent le contenu commun.', '#attributes' => ['class' => ['tcl-note']]];
    $form['channel_copy'] = ['#type' => 'vertical_tabs', '#title' => 'Personnaliser par canal'];
    foreach (CommunicationRepository::CHANNELS as $channel => $label) {
      $form['copy_' . $channel] = ['#type' => 'details', '#title' => $label, '#group' => 'channel_copy', '#tree' => TRUE, '#attributes' => ['class' => ['tcl-channel-panel', 'tcl-channel-' . $channel]]];
      $form['copy_' . $channel]['title'] = ['#type' => 'textfield', '#title' => $channel === 'email' ? 'Objet du courriel' : 'Titre — ' . $label, '#maxlength' => 160, '#default_value' => $variants[$channel]['title'] ?? '', '#description' => 'Laissez vide pour reprendre le titre commun.'];
      $form['copy_' . $channel]['body'] = ['#type' => 'textarea', '#title' => 'Texte — ' . $label, '#default_value' => $variants[$channel]['body'] ?? '', '#description' => 'Laissez vide pour reprendre le texte commun. 5 000 caractères maximum. L’affiche et les informations de l’événement restent communes.'];
    }
    $form['link'] = ['#type' => 'url', '#title' => 'Lien utile', '#maxlength' => 1000, '#description' => 'Facultatif. Une adresse HTTPS complète.', '#default_value' => $record['link'] ?? ''];
    $form['poster'] = ['#type' => 'file', '#title' => 'Image ou affiche', '#description' => 'PDF, JPEG, PNG ou WebP, 8 Mo maximum. PDF conservé avec aperçu de la première page ; images préparées en JPEG de 1 600 pixels et 500 ko maximum.', '#attributes' => ['accept' => 'application/pdf,image/jpeg,image/png,image/webp']];
    if (!empty($record['image_data'])) {
      $form['current_image'] = ['#theme' => 'image', '#uri' => Url::fromRoute('tcl_bureau.communication_image', ['post' => $this->postId], ['query' => ['v' => $record['revision']]])->toString(), '#alt' => $record['image_alt'], '#attributes' => ['class' => ['tcl-post-image']]];
      if (CommunicationImage::isPdf($record['image_data'])) {
        $form['current_image'] = ['#type' => 'link', '#title' => 'Télécharger l’affiche PDF actuelle', '#url' => Url::fromRoute('tcl_bureau.communication_image', ['post' => $this->postId])];
        $form['current_pdf_preview'] = ['#type' => 'container', '#attributes' => ['class' => ['tcl-pdf-preview'], 'data-pdf-url' => Url::fromRoute('tcl_bureau.communication_image', ['post' => $this->postId], ['query' => ['v' => $record['revision']]])->toString()], 'loading' => ['#plain_text' => 'Chargement de l’aperçu PDF…']];
      }
      $form['remove_image'] = ['#type' => 'checkbox', '#title' => 'Retirer l’affiche actuelle'];
    }
    $form['image_alt'] = ['#type' => 'textfield', '#title' => 'Description de l’affiche', '#maxlength' => 240, '#description' => 'Obligatoire quand une affiche est jointe.', '#default_value' => $record['image_alt'] ?? ''];
    $form['tenup_visible'] = ['#type' => 'radios', '#title' => 'ADOC — Visible sur Ten’Up', '#options' => ['no' => 'Non', 'yes' => 'Oui'], '#default_value' => $record['tenup_visible'] ?? 'no', '#description' => 'Choix à reporter manuellement dans ADOC ; aucune modification de visibilité FFT depuis ce formulaire.'];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = ['#type' => 'submit', '#value' => 'Enregistrer le brouillon'];
    $form['actions']['cancel'] = ['#type' => 'link', '#title' => 'Retour à la communication', '#url' => Url::fromRoute('tcl_bureau.communication')];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $record = $this->postId !== NULL ? $this->repository->get($this->currentUser(), $this->postId) : NULL;
    if ($record && (int) $record['revision'] !== (int) $form_state->getValue('revision')) {
      $form_state->setErrorByName('revision', 'L’actualité a changé depuis l’ouverture. Rechargez-la avant de réessayer.');
    }
    $upload = \Drupal::request()->files->get('files', [])['poster'] ?? NULL;
    $image = NULL;
    if ($upload) {
      try {
        $image = CommunicationImage::prepare($upload);
      }
      catch (\InvalidArgumentException $error) {
        $form_state->setErrorByName('poster', $error->getMessage());
      }
    }
    if ($image !== NULL && $form_state->getValue('remove_image')) {
      $form_state->setErrorByName('poster', 'Choisissez entre remplacer l’image et la retirer.');
    }
    $hasImage = !$form_state->getValue('remove_image') && ($image !== NULL || !empty($record['image_data']));
    $selected = array_values(array_filter((array) $form_state->getValue('channels', [])));
    $form_state->setValue('channels', implode(',', $selected));
    $values = $form_state->getValues();
    $copy = [];
    foreach (CommunicationRepository::CHANNELS as $channel => $label) { $copy[$channel] = $values['copy_' . $channel] ?? []; }
    $values['channel_texts'] = json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $form_state->setValue('channel_texts', $values['channel_texts']);
    foreach (CommunicationRepository::FIELDS as $field) {
      if (is_scalar($values[$field] ?? NULL)) {
        $values[$field] = trim((string) $values[$field]);
      }
    }
    foreach ($this->repository->validate($values, $hasImage) as $field => $message) {
      $form_state->setErrorByName($field, $message);
    }
    $form_state->setValue('channels', $selected);
    $form_state->set('prepared_poster', $image);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $copy = [];
    foreach (CommunicationRepository::CHANNELS as $channel => $label) { $copy[$channel] = $values['copy_' . $channel] ?? []; }
    $values['channel_texts'] = json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $form_state->setValue('channel_texts', $values['channel_texts']);
    $values['channels'] = implode(',', array_values(array_filter((array) ($values['channels'] ?? []))));
    try {
      $id = $this->repository->save($this->currentUser(), $this->postId, $values, (string) $form_state->getValue('submission_key'), (int) $form_state->getValue('revision'), $form_state->get('prepared_poster'), (bool) $form_state->getValue('remove_image'));
    }
    catch (ConflictHttpException $error) {
      $this->messenger()->addError($error->getMessage());
      $form_state->setRebuild();
      return;
    }
    $this->messenger()->addStatus('Brouillon enregistré. Relisez les aperçus avant de valider.');
    $form_state->setRedirect('tcl_bureau.communication_review', ['post' => $id]);
  }
}
