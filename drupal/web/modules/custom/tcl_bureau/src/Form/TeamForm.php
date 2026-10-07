<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tcl_bureau\TeamRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class TeamForm extends FormBase {

  private ?int $teamId = NULL;

  public function __construct(private TeamRepository $repository) {}

  public static function create(ContainerInterface $container) {
    return new static($container->get('tcl_bureau.repository'));
  }

  public function getFormId(): string {
    return 'tcl_bureau_team_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?string $team = NULL): array {
    $this->teamId = $team !== NULL ? (int) $team : NULL;
    $manager = $this->repository->allowed($this->currentUser(), 'manage tcl teams');
    if (($this->teamId === NULL && !$manager) || ($this->teamId !== NULL && !$this->repository->canEdit($this->currentUser(), $this->teamId))) {
      throw new AccessDeniedHttpException();
    }
    $row = $this->teamId !== NULL ? $this->repository->get($this->teamId) : NULL;
    $form['#attributes']['class'][] = 'tcl-bureau';
    $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['#cache']['max-age'] = 0;
    $form['team_version'] = ['#type' => 'hidden', '#default_value' => $row ? $this->repository->version($row) : ''];
    if ($manager) {
      $form['name'] = ['#type' => 'textfield', '#title' => 'Nom de l’équipe', '#required' => TRUE, '#maxlength' => 120, '#default_value' => $row['name'] ?? ''];
      $form['category'] = ['#type' => 'textfield', '#title' => 'Catégorie', '#maxlength' => 120, '#default_value' => $row['category'] ?? ''];
      $form['captains'] = ['#type' => 'checkboxes', '#title' => 'Capitaines autorisés', '#options' => $this->repository->captains(), '#default_value' => $row['captains'] ?? [], '#description' => 'Seuls les capitaines cochés auront accès à cette équipe.'];
    }
    else {
      $form['team_name'] = ['#type' => 'item', '#title' => 'Équipe', '#plain_text' => $row['name']];
      $form['category_name'] = ['#type' => 'item', '#title' => 'Catégorie', '#plain_text' => $row['category']];
    }
    $form['notes'] = ['#type' => 'textarea', '#title' => 'Notes de l’équipe', '#default_value' => $row['notes'] ?? '', '#description' => 'Maximum 2 000 caractères. Ces notes sont réservées au Bureau et aux capitaines attribués.'];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = ['#type' => 'submit', '#value' => 'Enregistrer l’équipe'];
    $form['actions']['cancel'] = ['#type' => 'link', '#title' => 'Retour à l’espace du club', '#url' => \Drupal\Core\Url::fromRoute('tcl_bureau.home')];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if ($this->teamId !== NULL) {
      $current = $this->repository->get($this->teamId);
      if (!$current || !hash_equals($this->repository->version($current), (string) $form_state->getValue('team_version'))) {
        $form_state->setErrorByName('team_version', 'L’équipe a changé depuis l’ouverture de cette fiche. Rechargez-la avant de réessayer.');
      }
    }
    if (mb_strlen(trim((string) $form_state->getValue('notes'))) > 2000) {
      $form_state->setErrorByName('notes', 'Les notes ne doivent pas dépasser 2 000 caractères.');
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // The repository rechecks the live account and assignment before writing.
    $this->repository->save($this->currentUser(), $this->teamId, $form_state->getValues(), (string) $form_state->getValue('team_version'));
    $this->messenger()->addStatus('L’équipe est enregistrée.');
    $form_state->setRedirect('tcl_bureau.home');
  }

}
