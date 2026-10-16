<?php

declare(strict_types=1);

namespace Drupal\tcl_support;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;

final class SupportRepository {

  public const STATUSES = [
    'new' => 'Nouvelle',
    'in_progress' => 'En cours',
    'needs_information' => 'Informations attendues',
    'resolved' => 'Résolue',
    'closed' => 'Classée',
  ];

  public const NOTIFICATIONS = [
    'pending' => 'À notifier',
    'captured_local' => 'Courriel de test capturé en local',
    'submitted_transport' => 'Accepté par le transport — réception à vérifier',
    'failed' => 'Échec de notification — demande conservée',
    'disabled' => 'Envoi désactivé — demande conservée',
  ];

  public function __construct(private readonly Connection $database) {}

  /** Returns the existing ID on a repeated submission, without a second mail. */
  public function create(array $values): array {
    $existing = $this->findSubmission($values['submission_id']);
    if ($existing !== FALSE) {
      return ['id' => $existing, 'created' => FALSE];
    }
    $transaction = $this->database->startTransaction();
    try {
      $now = time();
      $id = (int) $this->database->insert('tcl_support_request')->fields($values + [
        'created' => $now, 'updated' => $now, 'revision' => 1,
        'status' => 'new', 'owner' => '', 'notification' => 'pending',
      ])->execute();
      $this->event($id, 0, 'created', 'new', 'Demande enregistrée.');
      return ['id' => $id, 'created' => TRUE];
    }
    catch (\Throwable $exception) {
      $transaction->rollBack();
      // Another concurrent copy of the same POST may have won the unique key.
      $existing = $this->database->select('tcl_support_request', 'r')->fields('r', ['id'])
        ->condition('submission_id', $values['submission_id'])->execute()->fetchField();
      if ($existing !== FALSE) {
        return ['id' => (int) $existing, 'created' => FALSE];
      }
      throw $exception;
    }
  }

  public function findSubmission(string $key): int|false {
    $id = $this->database->select('tcl_support_request', 'r')->fields('r', ['id'])
      ->condition('submission_id', $key)->execute()->fetchField();
    return $id === FALSE ? FALSE : (int) $id;
  }

  public function load(int $id): object|false {
    return $this->database->select('tcl_support_request', 'r')->fields('r')
      ->condition('id', $id)->execute()->fetchObject();
  }

  public function list(string $status = ''): array {
    $query = $this->database->select('tcl_support_request', 'r')->fields('r');
    if (isset(self::STATUSES[$status])) {
      $query->condition('status', $status);
    }
    return $query->orderBy('id', 'DESC')->extend(PagerSelectExtender::class)
      ->limit(25)->execute()->fetchAll();
  }

  public function history(int $id): array {
    return $this->database->select('tcl_support_event', 'e')->fields('e')
      ->condition('request_id', $id)->orderBy('id', 'DESC')->execute()->fetchAll();
  }

  /** Optimistic concurrency protects the earlier person's processing notes. */
  public function update(int $id, int $revision, string $status, string $owner, string $note, int $actor): bool {
    if (!isset(self::STATUSES[$status]) || mb_strlen($owner) > 120 || mb_strlen($note) > 4000) {
      throw new \InvalidArgumentException('Traitement invalide.');
    }
    $transaction = $this->database->startTransaction();
    try {
      $changed = $this->database->update('tcl_support_request')
        ->fields(['status' => $status, 'owner' => $owner, 'updated' => time(), 'revision' => $revision + 1])
        ->condition('id', $id)->condition('revision', $revision)->execute();
      if (!$changed) {
        $transaction->rollBack();
        return FALSE;
      }
      $this->event($id, $actor, 'processed', $status, $note);
      return TRUE;
    }
    catch (\Throwable $exception) {
      $transaction->rollBack();
      throw $exception;
    }
  }

  public function notified(int $id, string $state, int $actor = 0): void {
    if (!isset(self::NOTIFICATIONS[$state])) {
      throw new \InvalidArgumentException('État de notification invalide.');
    }
    $transaction = $this->database->startTransaction();
    try {
      $this->database->update('tcl_support_request')->fields(['notification' => $state])
        ->condition('id', $id)->execute();
      $this->event($id, $actor, 'notification', $state, self::NOTIFICATIONS[$state]);
    }
    catch (\Throwable $exception) {
      $transaction->rollBack();
      throw $exception;
    }
  }

  private function event(int $id, int $actor, string $kind, string $state, string $note): void {
    $this->database->insert('tcl_support_event')->fields([
      'request_id' => $id, 'created' => time(), 'actor_uid' => $actor,
      'kind' => $kind, 'state' => $state, 'note' => $note,
    ])->execute();
  }

}
