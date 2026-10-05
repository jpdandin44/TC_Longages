<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** Stores internal teams and enforces fresh server-side account/assignment checks. */
final class TeamRepository {

  public function __construct(private Connection $database, private EntityTypeManagerInterface $entities) {}

  public function active(AccountInterface $account): bool {
    if (!$account->isAuthenticated()) {
      return FALSE;
    }
    $storage = $this->entities->getStorage('user');
    $storage->resetCache([(int) $account->id()]);
    $user = $storage->load($account->id());
    return $user instanceof UserInterface && $user->isActive();
  }

  public function allowed(AccountInterface $account, string $permission): bool {
    if (!$this->active($account)) {
      return FALSE;
    }
    // Reload roles as well: a removed permission must take effect immediately.
    $this->entities->getStorage('user_role')->resetCache();
    $user = $this->entities->getStorage('user')->load($account->id());
    return $user->hasPermission($permission);
  }

  public function canManageAccount(AccountInterface $actor, ?UserInterface $target = NULL): bool {
    $admin = $this->allowed($actor, 'administer tcl bureau accounts');
    if (!$admin && !$this->allowed($actor, 'manage tcl captain accounts')) {
      return FALSE;
    }
    if ($target === NULL) {
      return TRUE;
    }
    if ((int) $target->id() === 1 || (int) $target->id() === (int) $actor->id()) {
      return FALSE;
    }
    $roles = array_diff($target->getRoles(), ['authenticated']);
    $allowed = $admin ? ['tcl_bureau', 'tcl_capitaine'] : ['tcl_capitaine'];
    return count($roles) === 1 && !array_diff($roles, $allowed);
  }

  public function get(int $id): ?array {
    $row = $this->database->select('tcl_bureau_team', 't')->fields('t')->condition('id', $id)->execute()->fetchAssoc();
    if (!$row) {
      return NULL;
    }
    $row['captains'] = array_map('intval', $this->database->select('tcl_bureau_assignment', 'a')->fields('a', ['uid'])->condition('team_id', $id)->execute()->fetchCol());
    return $row;
  }

  public function canEdit(AccountInterface $account, int $id): bool {
    if (!$this->get($id) || !$this->allowed($account, 'access tcl bureau')) {
      return FALSE;
    }
    if ($this->allowed($account, 'manage tcl teams')) {
      return TRUE;
    }
    return $this->allowed($account, 'view assigned tcl teams') && (bool) $this->database->select('tcl_bureau_assignment', 'a')->fields('a', ['uid'])->condition('team_id', $id)->condition('uid', $account->id())->execute()->fetchField();
  }

  public function visible(AccountInterface $account): array {
    if (!$this->allowed($account, 'access tcl bureau')) {
      throw new AccessDeniedHttpException();
    }
    $query = $this->database->select('tcl_bureau_team', 't')->fields('t', ['id'])->orderBy('name');
    if (!$this->allowed($account, 'manage tcl teams')) {
      if (!$this->allowed($account, 'view assigned tcl teams')) {
        return [];
      }
      $query->innerJoin('tcl_bureau_assignment', 'a', 'a.team_id = t.id');
      $query->condition('a.uid', $account->id());
    }
    return array_map(fn($id) => $this->get((int) $id), $query->execute()->fetchCol());
  }

  public function captains(): array {
    $storage = $this->entities->getStorage('user');
    $ids = $storage->getQuery()->accessCheck(FALSE)->condition('roles', 'tcl_capitaine')->condition('status', 1)->sort('name')->execute();
    $result = [];
    foreach ($storage->loadMultiple($ids) as $user) {
      // Accounts with an extra privileged role are not valid captain targets.
      if (!array_diff($user->getRoles(), ['authenticated', 'tcl_capitaine'])) {
        $result[(int) $user->id()] = $user->getAccountName();
      }
    }
    return $result;
  }

  public function version(array $team): string {
    return hash('sha256', json_encode([$team['name'], $team['category'], $team['notes'], $team['changed'], $team['captains']], JSON_THROW_ON_ERROR));
  }

  public function save(AccountInterface $actor, ?int $id, array $values, ?string $expectedVersion = NULL): int {
    $manager = $this->allowed($actor, 'manage tcl teams');
    if (($id === NULL && !$manager) || ($id !== NULL && !$this->canEdit($actor, $id))) {
      throw new AccessDeniedHttpException();
    }
    $old = $id !== NULL ? $this->get($id) : NULL;
    if ($old !== NULL && $expectedVersion !== NULL && !hash_equals($this->version($old), $expectedVersion)) {
      throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException('L’équipe a changé. Rechargez la fiche avant de réessayer.');
    }
    $name = $manager ? trim((string) ($values['name'] ?? '')) : $old['name'];
    $category = $manager ? trim((string) ($values['category'] ?? '')) : $old['category'];
    $notes = trim((string) ($values['notes'] ?? ''));
    if ($name === '' || mb_strlen($name) > 120 || mb_strlen($category) > 120 || mb_strlen($notes) > 2000) {
      throw new \InvalidArgumentException('Nom, catégorie ou notes invalides.');
    }
    $captains = $manager ? array_unique(array_map('intval', array_filter($values['captains'] ?? []))) : $old['captains'];
    if ($manager && array_diff($captains, array_keys($this->captains()))) {
      throw new \InvalidArgumentException('Le capitaine sélectionné doit être un compte Capitaine actif.');
    }
    $transaction = $this->database->startTransaction();
    try {
      $record = ['name' => $name, 'category' => $category, 'notes' => $notes, 'changed' => time()];
      if ($id === NULL) {
        $id = (int) $this->database->insert('tcl_bureau_team')->fields($record)->execute();
      }
      else {
        $this->database->update('tcl_bureau_team')->fields($record)->condition('id', $id)->execute();
      }
      if ($manager) {
        $this->database->delete('tcl_bureau_assignment')->condition('team_id', $id)->execute();
        foreach ($captains as $uid) {
          $this->database->insert('tcl_bureau_assignment')->fields(['team_id' => $id, 'uid' => $uid])->execute();
        }
      }
    }
    catch (\Throwable $error) {
      $transaction->rollBack();
      throw $error;
    }
    return $id;
  }

}
