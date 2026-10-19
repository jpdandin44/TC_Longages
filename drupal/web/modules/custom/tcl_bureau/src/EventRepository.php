<?php

declare(strict_types=1);
namespace Drupal\tcl_bureau;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Isolated recipe store: never writes to the shared Google calendar. */
final class EventRepository {
  public const STATUSES = ['scheduled' => 'Programmé', 'cancelled' => 'Annulé'];
  public function __construct(private KeyValueFactoryInterface $stores, private LockBackendInterface $lock, private TeamRepository $access) {}
  public function allowed(AccountInterface $actor): bool {
    return SeasonCatalogue::recipeEnabled() && $this->access->allowed($actor, 'manage tcl communication');
  }
  private function guard(AccountInterface $actor): void {
    if (!$this->allowed($actor)) { throw new AccessDeniedHttpException(); }
  }
  public function all(AccountInterface $actor): array {
    $this->guard($actor);
    $rows = $this->stores->get('tcl_bureau.recipe_events')->getAll();
    uasort($rows, static fn(array $a, array $b): int => [$a['start_date'], $a['start_time'], $a['id']] <=> [$b['start_date'], $b['start_time'], $b['id']]);
    return $rows;
  }
  public function get(AccountInterface $actor, string $id): array {
    $this->guard($actor);
    $row = $this->stores->get('tcl_bureau.recipe_events')->get($id);
    if (!$row) { throw new NotFoundHttpException(); }
    return $row;
  }
  public function save(AccountInterface $actor, string $id, int $revision, array $input): string {
    $this->guard($actor);
    if (!preg_match('/^[a-f0-9]{32}$/D', $id) || EventSchedule::inspect($input)['errors'] || !isset(self::STATUSES[$input['status'] ?? ''])) { throw new \InvalidArgumentException('Événement invalide.'); }
    if (!$this->lock->acquire('tcl_bureau.recipe_events.write', 30)) { throw new ConflictHttpException('Une modification est en cours. Réessayez.'); }
    try {
      $store = $this->stores->get('tcl_bureau.recipe_events');
      $old = $store->get($id);
      if (($old['revision'] ?? 0) !== $revision) { throw new ConflictHttpException('Cet événement a changé. Rechargez sa fiche avant de modifier.'); }
      if (!$old && count($store->getAll()) >= 100) { throw new ConflictHttpException('La limite de 100 séries de recette est atteinte.'); }
      $row = array_intersect_key($input, array_flip([...EventSchedule::FIELDS, 'status']));
      $row += ['id' => $id, 'revision' => $revision + 1, 'created' => $old['created'] ?? time(), 'changed' => time(), 'changed_by' => (int) $actor->id()];
      $store->set($id, $row);
      return $id;
    }
    finally { $this->lock->release('tcl_bureau.recipe_events.write'); }
  }
}
