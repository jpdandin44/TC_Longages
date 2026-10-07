<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tcl_bureau\TeamRepository;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class AccountForm extends FormBase {

  private ?int $userId = NULL;

  public function __construct(private TeamRepository $repository) {}

  public static function create(ContainerInterface $container) {
    return new static($container->get('tcl_bureau.repository'));
  }

  public function getFormId(): string {
    return 'tcl_bureau_account_form';
  }

  private function target(): ?UserInterface {
    if ($this->userId === NULL) {
      return NULL;
    }
    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $storage->resetCache([$this->userId]);
    $target = $storage->load($this->userId);
    if (!$target instanceof UserInterface) {
      throw new AccessDeniedHttpException();
    }
    return $target;
  }

  private function version(UserInterface $user): string {
    return hash('sha256', json_encode([$user->id(), $user->getEmail(), $user->isActive(), $user->getRoles(), $user->getPassword()], JSON_THROW_ON_ERROR));
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?UserInterface $user = NULL): array {
    $this->userId = $user !== NULL ? (int) $user->id() : NULL;
    if (!$this->repository->canManageAccount($this->currentUser(), $user)) {
      throw new AccessDeniedHttpException();
    }
    $admin = $this->repository->allowed($this->currentUser(), 'administer tcl bureau accounts');
    $form['#attributes']['class'][] = 'tcl-bureau';
    $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['#cache']['max-age'] = 0;
    $form['account_version'] = ['#type' => 'hidden', '#default_value' => $user ? $this->version($user) : ''];
    if ($user === NULL) {
      $form['name'] = ['#type' => 'textfield', '#title' => 'Identifiant', '#required' => TRUE, '#maxlength' => 60];
    }
    else {
      $form['identity'] = ['#type' => 'item', '#title' => 'Identifiant', '#plain_text' => $user->getAccountName()];
    }
    $form['mail'] = ['#type' => 'email', '#title' => 'Adresse de courriel', '#required' => TRUE, '#default_value' => $user?->getEmail() ?? ''];
    $form['password'] = ['#type' => 'password_confirm', '#title' => $user === NULL ? 'Mot de passe initial' : 'Nouveau mot de passe', '#required' => $user === NULL, '#description' => 'Au moins 16 caractères. ' . ($user !== NULL ? 'Laissez vide pour conserver le mot de passe actuel. ' : '') . 'Aucun courriel automatique n’est envoyé.'];
    if ($admin) {
      $form['role'] = ['#type' => 'select', '#title' => 'Rôle', '#options' => ['tcl_capitaine' => 'Capitaine', 'tcl_bureau' => 'Bureau'], '#default_value' => $user?->hasRole('tcl_bureau') ? 'tcl_bureau' : 'tcl_capitaine'];
    }
    else {
      $form['role_label'] = ['#type' => 'item', '#title' => 'Rôle', '#plain_text' => 'Capitaine'];
    }
    $form['active'] = ['#type' => 'checkbox', '#title' => 'Compte actif', '#default_value' => $user?->isActive() ?? TRUE, '#description' => 'Décocher pour bloquer le compte et ses accès.'];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = ['#type' => 'submit', '#value' => $user === NULL ? 'Créer le compte' : 'Enregistrer le compte'];
    $form['actions']['cancel'] = ['#type' => 'link', '#title' => 'Retour aux comptes', '#url' => \Drupal\Core\Url::fromRoute('tcl_bureau.accounts')];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $target = $this->target();
    if (!$this->repository->canManageAccount($this->currentUser(), $target)) {
      throw new AccessDeniedHttpException();
    }
    if ($target && !hash_equals($this->version($target), (string) $form_state->getValue('account_version'))) {
      $form_state->setErrorByName('account_version', 'Le compte a changé depuis l’ouverture de cette fiche. Rechargez-la avant de réessayer.');
    }
    $password = (string) $form_state->getValue('password');
    if (($target === NULL || $password !== '') && mb_strlen($password) < 16) {
      $form_state->setErrorByName('password', 'Le mot de passe doit comporter au moins 16 caractères.');
    }
    $name = $target?->getAccountName() ?? trim((string) $form_state->getValue('name'));
    $storage = \Drupal::entityTypeManager()->getStorage('user');
    foreach (['name' => $name, 'mail' => trim((string) $form_state->getValue('mail'))] as $field => $value) {
      $query = $storage->getQuery()->accessCheck(FALSE)->condition($field, $value);
      if ($target !== NULL) {
        $query->condition('uid', $target->id(), '<>');
      }
      if ($query->execute()) {
        $form_state->setErrorByName($field, $field === 'name' ? 'Cet identifiant est déjà utilisé.' : 'Cette adresse de courriel est déjà utilisée.');
      }
    }
    if ($target === NULL) {
      foreach (User::create(['name' => $name, 'mail' => $form_state->getValue('mail')])->validate() as $violation) {
        if (str_starts_with($violation->getPropertyPath(), 'name')) {
          $form_state->setErrorByName('name', $violation->getMessage());
        }
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $target = $this->target();
    if (!$this->repository->canManageAccount($this->currentUser(), $target)) {
      throw new AccessDeniedHttpException();
    }
    if ($target && !hash_equals($this->version($target), (string) $form_state->getValue('account_version'))) {
      throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException('Le compte a changé. Rechargez sa fiche.');
    }
    $admin = $this->repository->allowed($this->currentUser(), 'administer tcl bureau accounts');
    $role = $admin ? (string) $form_state->getValue('role') : 'tcl_capitaine';
    if (!in_array($role, $admin ? ['tcl_bureau', 'tcl_capitaine'] : ['tcl_capitaine'], TRUE)) {
      throw new AccessDeniedHttpException();
    }
    $target ??= User::create(['name' => trim((string) $form_state->getValue('name'))]);
    $target->setEmail(trim((string) $form_state->getValue('mail')));
    foreach (['tcl_bureau', 'tcl_capitaine'] as $businessRole) {
      $target->removeRole($businessRole);
    }
    $target->addRole($role);
    $form_state->getValue('active') ? $target->activate() : $target->block();
    if ($password = (string) $form_state->getValue('password')) {
      $target->setPassword($password);
    }
    $target->save();
    $this->messenger()->addStatus('Le compte est enregistré.');
    $form_state->setRedirect('tcl_bureau.accounts');
  }

}
