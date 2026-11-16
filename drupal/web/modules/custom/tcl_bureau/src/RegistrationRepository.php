<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** Private Bureau records; no FFT enrolment, payment or message side effects. */
final class RegistrationRepository {
  public const STATUSES = ['received' => 'Reçu', 'to_complete' => 'À compléter', 'checked' => 'Vérifié par le Bureau'];
  public const FIELDS = ['profile', 'first_name', 'last_name', 'birth_date', 'season', 'email', 'phone', 'address', 'postal_code', 'city', 'guardian_name', 'guardian_email', 'guardian_phone', 'formula', 'training', 'previous_licence', 'licence_number', 'status', 'notes', 'emergency_name', 'emergency_phone', 'last_licence_year', 'practice_years'];

  public function __construct(private Connection $database, private TeamRepository $access) {}

  public function allowed(AccountInterface $actor): bool {
    return $this->access->allowed($actor, 'manage tcl registrations');
  }

  private function guard(AccountInterface $actor): void {
    if (!$this->allowed($actor)) {
      throw new AccessDeniedHttpException();
    }
  }

  public function list(AccountInterface $actor, string $search = ''): array {
    $this->guard($actor);
    $query = $this->database->select('tcl_bureau_registration', 'r')->fields('r', ['id', 'first_name', 'last_name', 'season', 'status', 'revision'])->orderBy('changed', 'DESC')->orderBy('id', 'DESC');
    if ($search !== '') {
      $like = '%' . $this->database->escapeLike(mb_substr(trim($search), 0, 120)) . '%';
      $query->condition($query->orConditionGroup()->condition('first_name', $like, 'LIKE')->condition('last_name', $like, 'LIKE'));
    }
    return $query->range(0, 100)->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function get(AccountInterface $actor, int $id): ?array {
    $this->guard($actor);
    $row = $this->database->select('tcl_bureau_registration', 'r')->fields('r')->condition('id', $id)->execute()->fetchAssoc();
    if (!$row) {
      return NULL;
    }
    return $row + ['values' => json_decode($row['details'], TRUE, 512, JSON_THROW_ON_ERROR)];
  }

  public function validate(array $values): array {
    foreach (self::FIELDS as $field) {
      if (isset($values[$field]) && is_scalar($values[$field])) {
        $values[$field] = trim((string) $values[$field]);
      }
    }
    $errors = [];
    foreach (self::FIELDS as $field) {
      if (isset($values[$field]) && !is_scalar($values[$field])) {
        $errors[$field] = 'Valeur invalide.';
      }
      elseif (mb_strlen((string) ($values[$field] ?? '')) > ($field === 'notes' ? 1000 : 120)) {
        $errors[$field] = 'Cette valeur est trop longue.';
      }
    }
    if ($errors) {
      return $errors;
    }
    foreach (['first_name', 'last_name', 'birth_date', 'season'] as $field) {
      if (trim((string) ($values[$field] ?? '')) === '') {
        $errors[$field] = 'Ce champ est obligatoire.';
      }
    }
    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($values['birth_date'] ?? ''));
    if (!$date || $date->format('Y-m-d') !== ($values['birth_date'] ?? '') || $date > new \DateTimeImmutable('today')) {
      $errors['birth_date'] = 'Indiquez une date de naissance valide, non future.';
    }
    if (!preg_match('/^(\d{4})-(\d{4})$/D', (string) ($values['season'] ?? ''), $years) || (int) $years[2] !== (int) $years[1] + 1) {
      $errors['season'] = 'Indiquez une saison, par exemple 2026-2027.';
    }
    foreach (['profile' => ['adult', 'minor'], 'training' => ['unknown', 'yes', 'no'], 'previous_licence' => ['unknown', 'yes', 'no'], 'status' => array_keys(self::STATUSES)] as $field => $options) {
      if (!in_array($values[$field] ?? NULL, $options, TRUE)) {
        $errors[$field] = 'Choisissez une valeur proposée.';
      }
    }
    foreach (['email', 'guardian_email'] as $field) {
      if (!empty($values[$field]) && !filter_var($values[$field], FILTER_VALIDATE_EMAIL)) {
        $errors[$field] = 'Adresse de courriel invalide.';
      }
    }
    if (!empty($values['last_licence_year']) && (!preg_match('/^\d{4}$/D', $values['last_licence_year']) || (int) $values['last_licence_year'] > (int) date('Y'))) {
      $errors['last_licence_year'] = 'Indiquez une année passée ou courante, ou laissez vide.';
    }
    if (!empty($values['practice_years']) && (!preg_match('/^\d{1,3}$/D', $values['practice_years']) || (int) $values['practice_years'] > 120)) {
      $errors['practice_years'] = 'Indiquez un nombre d’années, ou laissez vide.';
    }
    if (($values['profile'] ?? '') === 'minor') {
      if (empty($values['guardian_name'])) {
        $errors['guardian_name'] = 'Renseignez le responsable légal.';
      }
      if (empty($values['guardian_email']) && empty($values['guardian_phone'])) {
        $errors['guardian_phone'] = 'Renseignez un moyen de contacter le responsable légal.';
      }
    }
    elseif (empty($values['email']) && empty($values['phone'])) {
      $errors['phone'] = 'Renseignez un courriel ou un téléphone de contact.';
    }
    return $errors;
  }

  public function save(AccountInterface $actor, ?int $id, array $input, string $submission, int $revision): int {
    $this->guard($actor);
    $metadata = $id !== NULL ? array_intersect_key($this->get($actor, $id)['values'] ?? [], array_flip(['catalogue_snapshot', 'origin', 'recipe', 'availability'])) : [];
    return $this->persist($actor, $id, $input, $submission, $revision, $metadata);
  }

  /** A separate, disabled-by-default entry point; private Bureau guards remain intact. */
  public function submitRecipe(AccountInterface $actor, array $input, string $submission, int $catalogueRevision): int {
    if (!SeasonCatalogue::recipeEnabled()) { throw new AccessDeniedHttpException(); }
    $catalogue = \Drupal::service('tcl_bureau.seasons')->published();
    if (!$catalogue || $catalogue['revision'] !== $catalogueRevision) { throw new ConflictHttpException('Les tarifs ont changé. Rechargez le formulaire.'); }
    $formula = NULL;
    foreach ($catalogue['formulas'] as $row) {
      if ($row['id'] === ($input['formula'] ?? '') && $row['profile'] === ($input['profile'] ?? '')) { $formula = $row; }
    }
    if (!$formula || empty($input['fictional_confirmation'])) { throw new \InvalidArgumentException('Choix de formule ou confirmation de recette invalide.'); }
    // Test-only conventions make accidental use of real contact details visible.
    if (!str_starts_with((string) ($input['last_name'] ?? ''), 'Recette') || !RecipeMail::inputAllowed((string) ($input['email'] ?? ''))) { throw new \InvalidArgumentException('Utilisez un nom commençant par Recette et un courriel @example.invalid ou une adresse de recette autorisée.'); }
    if (($input['profile'] ?? '') === 'minor' && (!str_starts_with((string) ($input['guardian_name'] ?? ''), 'Recette') || !RecipeMail::inputAllowed((string) ($input['guardian_email'] ?? '')))) { throw new \InvalidArgumentException('Le responsable fictif utilise aussi un nom Recette et un courriel @example.invalid ou une adresse de recette autorisée.'); }
    foreach (['phone', 'guardian_phone', 'emergency_phone'] as $field) {
      if (!empty($input[$field]) && $input[$field] !== '0600000000') { throw new \InvalidArgumentException('Téléphone de recette attendu : 0600000000, ou laissez vide.'); }
    }
    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($input['birth_date'] ?? ''));
    if (!$date || (($date->diff(new \DateTimeImmutable('today'))->y < 18) !== (($input['profile'] ?? '') === 'minor'))) { throw new \InvalidArgumentException('Vérifiez le profil adulte/mineur et la date de naissance fictive.'); }
    $availability = $input['availability'] ?? [];
    if (!is_array($availability) || array_diff($availability, $catalogue['slots'])) { throw new \InvalidArgumentException('Disponibilités invalides.'); }
    $input['season'] = $catalogue['season'];
    $input['formula'] = $formula['label'];
    $input['status'] = 'received';
    $input['notes'] = '';
    $input['licence_number'] = '';
    $snapshot = ['season' => $catalogue['season'], 'revision' => $catalogue['revision'], 'formula' => $formula, 'currency' => 'EUR', 'notice' => $catalogue['notice']];
    return $this->persist($actor, NULL, $input, $submission, 0, ['catalogue_snapshot' => $snapshot, 'origin' => 'web_recipe', 'recipe' => TRUE, 'availability' => $input['training'] === 'yes' ? array_values($availability) : []]);
  }

  private function persist(AccountInterface $actor, ?int $id, array $input, string $submission, int $revision, array $metadata = []): int {
    $values = [];
    foreach (self::FIELDS as $field) {
      $values[$field] = is_scalar($input[$field] ?? NULL) ? trim((string) $input[$field]) : '';
    }
    if ($this->validate($values) || !preg_match('/^[0-9a-f]{32}$/D', $submission)) {
      throw new \InvalidArgumentException('Dossier invalide : corrigez les champs signalés.');
    }
    if ($values['profile'] === 'adult') {
      foreach (['guardian_name', 'guardian_email', 'guardian_phone'] as $field) {
        $values[$field] = '';
      }
    }
    $identity = hash('sha256', json_encode([mb_strtolower($values['first_name']), mb_strtolower($values['last_name']), $values['birth_date'], $values['season']], JSON_THROW_ON_ERROR));
    $details = json_encode($values + $metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    $duplicate = $this->database->select('tcl_bureau_registration', 'r')->fields('r', ['id'])->condition('identity_key', $identity)->execute()->fetchField();
    if ($duplicate && (int) $duplicate !== $id) {
      $old = $this->database->select('tcl_bureau_registration', 'r')->fields('r', ['submission_key', 'details'])->condition('id', (int) $duplicate)->execute()->fetchAssoc();
      if ($old['submission_key'] === $submission && $old['details'] === $details) {
        return (int) $duplicate;
      }
      throw new ConflictHttpException('Un dossier de cette personne existe déjà pour cette saison.');
    }
    $record = ['identity_key' => $identity, 'first_name' => $values['first_name'], 'last_name' => $values['last_name'], 'season' => $values['season'], 'status' => $values['status'], 'details' => $details, 'changed' => time(), 'changed_by' => (int) $actor->id()];
    if ($id === NULL) {
      try {
        return (int) $this->database->insert('tcl_bureau_registration')->fields($record + ['submission_key' => $submission, 'revision' => 1, 'created' => time(), 'created_by' => (int) $actor->id()])->execute();
      }
      catch (IntegrityConstraintViolationException $error) {
        // A concurrent double submission may have passed the first lookup.
        $existing = $this->database->select('tcl_bureau_registration', 'r')->fields('r', ['id', 'details'])->condition('submission_key', $submission)->execute()->fetchAssoc();
        if ($existing && $existing['details'] === $details) {
          return (int) $existing['id'];
        }
        throw new ConflictHttpException('Un dossier de cette personne existe déjà pour cette saison.', $error);
      }
    }
    if (!$this->get($actor, $id)) {
      throw new AccessDeniedHttpException();
    }
    try {
      $changed = $this->database->update('tcl_bureau_registration')->fields($record + ['revision' => $revision + 1])->condition('id', $id)->condition('revision', $revision)->execute();
    }
    catch (IntegrityConstraintViolationException $error) {
      throw new ConflictHttpException('Un dossier de cette personne existe déjà pour cette saison.', $error);
    }
    if ($changed !== 1) {
      throw new ConflictHttpException('Le dossier a changé. Rechargez-le avant de réessayer.');
    }
    return $id;
  }
}
