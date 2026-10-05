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
  public const FIELDS = ['profile', 'first_name', 'last_name', 'birth_date', 'season', 'email', 'phone', 'address', 'postal_code', 'city', 'guardian_name', 'guardian_email', 'guardian_phone', 'formula', 'training', 'previous_licence', 'licence_number', 'status', 'notes'];

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
    $details = json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    $duplicate = $this->database->select('tcl_bureau_registration', 'r')->fields('r', ['id'])->condition('identity_key', $identity)->execute()->fetchField();
    if ($duplicate && (int) $duplicate !== $id) {
      $old = $this->get($actor, (int) $duplicate);
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
