<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tcl_bureau\RegistrationRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class RegistrationForm extends FormBase {
  private ?int $registrationId = NULL;

  public function __construct(private RegistrationRepository $repository) {}

  public static function create(ContainerInterface $container) {
    return new static($container->get('tcl_bureau.registrations'));
  }

  public function getFormId(): string {
    return 'tcl_bureau_registration_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?string $registration = NULL): array {
    if (!$this->repository->allowed($this->currentUser())) {
      throw new AccessDeniedHttpException();
    }
    $this->registrationId = $registration !== NULL ? (int) $registration : NULL;
    $record = $this->registrationId !== NULL ? $this->repository->get($this->currentUser(), $this->registrationId) : NULL;
    if ($this->registrationId !== NULL && !$record) {
      throw new NotFoundHttpException();
    }
    $values = $record['values'] ?? [];
    $form['#attributes']['class'][] = 'tcl-bureau';
    $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['#cache']['max-age'] = 0;
    $form['submission_key'] = ['#type' => 'hidden', '#default_value' => $record['submission_key'] ?? bin2hex(random_bytes(16))];
    $form['revision'] = ['#type' => 'hidden', '#default_value' => $record['revision'] ?? 0];
    $form['identity'] = ['#type' => 'fieldset', '#title' => 'Identité de l’adhérent'];
    foreach (['first_name' => 'Prénom', 'last_name' => 'Nom', 'season' => 'Saison'] as $key => $label) {
      $form['identity'][$key] = ['#type' => 'textfield', '#title' => $label, '#required' => TRUE, '#maxlength' => $key === 'season' ? 9 : 120, '#default_value' => $values[$key] ?? ($key === 'season' ? $this->config('tcl_bureau.settings')->get('season') : '')];
    }
    $form['identity']['profile'] = ['#type' => 'select', '#title' => 'Type de dossier', '#options' => ['adult' => 'Adulte', 'minor' => 'Mineur — École de tennis'], '#default_value' => $values['profile'] ?? 'adult'];
    $form['identity']['birth_date'] = ['#type' => 'date', '#title' => 'Date de naissance', '#required' => TRUE, '#default_value' => $values['birth_date'] ?? '', '#attributes' => ['max' => date('Y-m-d')]];
    $form['contact'] = ['#type' => 'fieldset', '#title' => 'Coordonnées'];
    foreach (['email' => 'Email', 'phone' => 'Téléphone', 'address' => 'Adresse', 'postal_code' => 'Code postal', 'city' => 'Ville'] as $key => $label) {
      $form['contact'][$key] = ['#type' => $key === 'email' ? 'email' : 'textfield', '#title' => $label, '#maxlength' => 120, '#default_value' => $values[$key] ?? ''];
    }
    $form['guardian'] = ['#type' => 'fieldset', '#title' => 'Responsable légal', '#states' => ['visible' => [':input[name="profile"]' => ['value' => 'minor']]]];
    foreach (['guardian_name' => 'Nom et prénom du responsable légal', 'guardian_email' => 'Email du responsable légal', 'guardian_phone' => 'Téléphone du responsable légal'] as $key => $label) {
      $form['guardian'][$key] = ['#type' => str_ends_with($key, 'email') ? 'email' : 'textfield', '#title' => $label, '#maxlength' => 120, '#default_value' => $values[$key] ?? ''];
    }
    $form['practice'] = ['#type' => 'fieldset', '#title' => 'Demande et suivi du Bureau'];
    $form['practice']['formula'] = ['#type' => 'textfield', '#title' => 'Formule souhaitée', '#maxlength' => 120, '#default_value' => $values['formula'] ?? '', '#description' => 'Reporter la formule de la fiche reçue. Aucun tarif ni remise n’est calculé automatiquement.'];
    foreach (['training' => 'Souhaitez-vous participer aux cours / entraînements organisés par le club ?', 'previous_licence' => 'Déjà licencié'] as $key => $label) {
      $form['practice'][$key] = ['#type' => 'select', '#title' => $label, '#options' => ['unknown' => 'À préciser', 'yes' => 'Oui', 'no' => 'Non'], '#default_value' => $values[$key] ?? 'unknown'];
    }
    $form['practice']['licence_number'] = ['#type' => 'textfield', '#title' => 'N° de licence', '#maxlength' => 120, '#default_value' => $values['licence_number'] ?? ''];
    $form['practice']['status'] = ['#type' => 'select', '#title' => 'État du dossier', '#options' => RegistrationRepository::STATUSES, '#default_value' => $values['status'] ?? 'received'];
    $form['practice']['notes'] = ['#type' => 'textarea', '#title' => 'Commentaire interne du Bureau', '#default_value' => $values['notes'] ?? '', '#description' => '1 000 caractères maximum. Ne pas saisir de données médicales ni de données bancaires.'];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = ['#type' => 'submit', '#value' => 'Enregistrer le dossier'];
    $form['actions']['cancel'] = ['#type' => 'link', '#title' => 'Retour aux adhérents', '#url' => Url::fromRoute('tcl_bureau.registrations')];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    foreach ($this->repository->validate($form_state->getValues()) as $field => $message) {
      $form_state->setErrorByName($field, $message);
    }
    if ($this->registrationId !== NULL) {
      $row = $this->repository->get($this->currentUser(), $this->registrationId);
      if (!$row || (int) $row['revision'] !== (int) $form_state->getValue('revision')) {
        $form_state->setErrorByName('revision', 'Le dossier a changé depuis l’ouverture de cette fiche. Rechargez-le avant de réessayer.');
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try {
      $this->repository->save($this->currentUser(), $this->registrationId, $form_state->getValues(), (string) $form_state->getValue('submission_key'), (int) $form_state->getValue('revision'));
    }
    catch (ConflictHttpException $error) {
      $this->messenger()->addError($error->getMessage());
      $form_state->setRebuild();
      return;
    }
    $this->messenger()->addStatus('Le dossier est enregistré.');
    $form_state->setRedirect('tcl_bureau.registrations');
  }
}
