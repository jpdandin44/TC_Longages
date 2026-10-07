<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** Server-side drafts and explicit review/publication of the exact revision. */
final class CommunicationRepository {
  public const CATEGORIES = ['club' => 'Vie du club', 'competition' => 'Compétitions', 'school' => 'École de tennis', 'event' => 'Événement'];
  public const STATUSES = ['draft' => 'Brouillon', 'validated' => 'Validée', 'published' => 'Publiée', 'archived' => 'Archivée'];
  public const FIELDS = ['title', 'category', 'body', 'link', 'tenup_visible', 'image_alt'];

  public function __construct(private Connection $database, private TeamRepository $access) {}

  public function allowed(AccountInterface $actor): bool {
    return $this->access->allowed($actor, 'manage tcl communication');
  }

  private function guard(AccountInterface $actor): void {
    if (!$this->allowed($actor)) {
      throw new AccessDeniedHttpException();
    }
  }

  public function list(AccountInterface $actor, bool $archived = FALSE): array {
    $this->guard($actor);
    return $this->database->select('tcl_bureau_post', 'p')->fields('p', ['id', 'title', 'status', 'revision', 'changed'])
      ->condition('status', 'archived', $archived ? '=' : '<>')->orderBy('changed', 'DESC')->orderBy('id', 'DESC')->range(0, 100)->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function get(AccountInterface $actor, int $id): ?array {
    $this->guard($actor);
    return $this->load($id);
  }

  private function load(int $id, bool $public = FALSE): ?array {
    $query = $this->database->select('tcl_bureau_post', 'p')->fields('p')->condition('id', $id);
    if ($public) {
      $query->condition('status', 'published')->where('approved_revision = revision');
    }
    $row = $query->execute()->fetchAssoc();
    if (!$row) {
      return NULL;
    }
    if (is_resource($row['image_data'])) {
      $row['image_data'] = stream_get_contents($row['image_data']);
    }
    return $row;
  }

  public function published(?int $id = NULL): array {
    if ($id !== NULL) {
      return $this->load($id, TRUE) ?? [];
    }
    // Never expose private images, authors or drafts in the public listing.
    return $this->database->select('tcl_bureau_post', 'p')->fields('p', ['id', 'title', 'category', 'body', 'link', 'published_at'])
      ->condition('status', 'published')->where('approved_revision = revision')->orderBy('published_at', 'DESC')->orderBy('id', 'DESC')->range(0, 100)->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function validate(array $values, bool $hasImage): array {
    $errors = [];
    foreach (['title' => 160, 'category' => 20, 'body' => 5000, 'link' => 1000, 'tenup_visible' => 3, 'image_alt' => 240] as $field => $maximum) {
      if (!is_scalar($values[$field] ?? NULL) || mb_strlen((string) $values[$field]) > $maximum) {
        $errors[$field] = 'Valeur invalide ou trop longue.';
      }
    }
    if ($errors) {
      return $errors;
    }
    if (trim($values['title']) === '') {
      $errors['title'] = 'Le titre est obligatoire.';
    }
    if (!$hasImage && trim($values['body']) === '') {
      $errors['body'] = 'Ajoutez un texte ou une affiche.';
    }
    if ($hasImage && trim($values['image_alt']) === '') {
      $errors['image_alt'] = 'La description de l’affiche est obligatoire.';
    }
    if (!isset(self::CATEGORIES[$values['category']])) {
      $errors['category'] = 'Choisissez une catégorie proposée.';
    }
    if (!in_array($values['tenup_visible'], ['yes', 'no'], TRUE)) {
      $errors['tenup_visible'] = 'Choisissez Oui ou Non.';
    }
    if ($values['link'] !== '' && (!filter_var($values['link'], FILTER_VALIDATE_URL) || parse_url($values['link'], PHP_URL_SCHEME) !== 'https' || parse_url($values['link'], PHP_URL_USER) !== NULL || parse_url($values['link'], PHP_URL_PASS) !== NULL)) {
      $errors['link'] = 'Indiquez une adresse HTTPS complète sans identifiant ni mot de passe.';
    }
    return $errors;
  }

  public function save(AccountInterface $actor, ?int $id, array $input, string $submission, int $revision, ?string $image = NULL, bool $remove = FALSE): int {
    $this->guard($actor);
    $old = $id !== NULL ? $this->get($actor, $id) : NULL;
    if ($id !== NULL && (!$old || $old['status'] === 'archived')) {
      throw new ConflictHttpException('Cette actualité n’est plus modifiable.');
    }
    $data = [];
    foreach (self::FIELDS as $field) {
      $data[$field] = is_scalar($input[$field] ?? NULL) ? trim((string) $input[$field]) : '';
    }
    $imageData = $remove ? '' : ($image ?? ($old['image_data'] ?? ''));
    if ($this->validate($data, $imageData !== '') || strlen($imageData) > 512000 || !preg_match('/^[0-9a-f]{32}$/D', $submission)) {
      throw new \InvalidArgumentException('Actualité invalide.');
    }
    if ($imageData === '') {
      $data['image_alt'] = '';
    }
    $data += ['image_data' => $imageData, 'status' => 'draft', 'approved_revision' => 0, 'validated_by' => 0, 'validated_at' => 0, 'published_at' => 0, 'changed' => time(), 'changed_by' => (int) $actor->id()];
    if ($id === NULL) {
      try {
        return (int) $this->database->insert('tcl_bureau_post')->fields($data + ['submission_key' => $submission, 'revision' => 1, 'created' => time()])->execute();
      }
      catch (IntegrityConstraintViolationException $error) {
        $duplicate = $this->database->select('tcl_bureau_post', 'p')->fields('p', ['id'])->condition('submission_key', $submission)->execute()->fetchField();
        $existing = $duplicate ? $this->load((int) $duplicate) : NULL;
        if ($existing && (int) $existing['revision'] === 1 && (int) $existing['changed_by'] === (int) $actor->id()) {
          foreach (array_merge(self::FIELDS, ['image_data']) as $field) {
            if ($existing[$field] !== $data[$field]) {
              throw new ConflictHttpException('Ce formulaire a déjà été utilisé.', $error);
            }
          }
          return (int) $duplicate;
        }
        throw new ConflictHttpException('Ce formulaire a déjà été utilisé.', $error);
      }
    }
    $changed = $this->database->update('tcl_bureau_post')->fields($data + ['revision' => $revision + 1])->condition('id', $id)->condition('revision', $revision)->condition('status', 'archived', '<>')->execute();
    if ($changed !== 1) {
      throw new ConflictHttpException('L’actualité a changé depuis l’ouverture. Rechargez-la avant de réessayer.');
    }
    return $id;
  }

  public function transition(AccountInterface $actor, int $id, int $revision, string $action): void {
    $this->guard($actor);
    $old = $this->get($actor, $id);
    if (!$old || (int) $old['revision'] !== $revision || ($old['status'] === 'archived' && $action !== 'restore')) {
      throw new ConflictHttpException('L’actualité a changé. Rechargez-la avant de réessayer.');
    }
    $data = ['changed' => time(), 'changed_by' => (int) $actor->id(), 'revision' => $revision + 1];
    if ($action === 'validate' && $old['status'] === 'draft') {
      $data += ['status' => 'validated', 'approved_revision' => $revision + 1, 'validated_at' => time(), 'validated_by' => (int) $actor->id()];
    }
    elseif ($action === 'publish' && $old['status'] === 'validated' && (int) $old['approved_revision'] === $revision) {
      $data += ['status' => 'published', 'approved_revision' => $revision + 1, 'published_at' => time()];
    }
    elseif ($action === 'withdraw' && $old['status'] === 'published') {
      $data += ['status' => 'validated', 'approved_revision' => $revision + 1, 'published_at' => 0];
    }
    elseif ($action === 'archive') {
      $data += ['status' => 'archived', 'approved_revision' => 0, 'published_at' => 0];
    }
    elseif ($action === 'restore' && $old['status'] === 'archived') {
      $data += ['status' => 'draft', 'approved_revision' => 0, 'validated_by' => 0, 'validated_at' => 0, 'published_at' => 0];
    }
    else {
      throw new ConflictHttpException('Cette action demande une actualité dans l’état attendu.');
    }
    // The compare-and-swap also protects concurrent review/publication/editing.
    $count = $this->database->update('tcl_bureau_post')->fields($data)->condition('id', $id)->condition('revision', $revision)->condition('status', $old['status'])->execute();
    if ($count !== 1) {
      throw new ConflictHttpException('L’actualité a changé. Rechargez-la avant de réessayer.');
    }
  }
}
