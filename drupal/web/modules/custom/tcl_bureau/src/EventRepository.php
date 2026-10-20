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
  public const CATEGORIES = ['lesson' => ['Cours de tennis', '🎾'], 'festival' => ['Manifestation', '🎉'], 'court' => ['Réservation des terrains', '📅'], 'meal' => ['Repas du club', '🍽️'], 'meeting' => ['Assemblée générale', '👥'], 'competition' => ['Compétition', '🏆'], 'other' => ['Autre rendez-vous', '📌']];
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
  public function presentationErrors(AccountInterface $actor, array $input): array {
    $this->guard($actor);
    $errors = [];
    if (!is_string($input['category'] ?? NULL) || !isset(self::CATEGORIES[$input['category']])) { $errors['category'] = 'Choisissez une catégorie proposée.'; }
    $poster = $input['poster_post'] ?? '';
    if (!is_string($poster) || !preg_match('/^(?:0|[1-9][0-9]{0,9})$/D', $poster)) { $errors['poster_post'] = 'Choisissez une communication proposée.'; }
    elseif ($poster !== '0') {
      $post = \Drupal::service('tcl_bureau.communication')->get($actor, (int) $poster);
      if (!$post || $post['status'] === 'archived' || $post['image_data'] === '') { $errors['poster_post'] = 'Cette communication n’a plus d’affiche disponible. Choisissez-en une autre ou Aucune.'; }
    }
    return $errors;
  }
  public function save(AccountInterface $actor, string $id, int $revision, array $input): string {
    $this->guard($actor);
    if (!preg_match('/^[a-f0-9]{32}$/D', $id) || EventSchedule::inspect($input)['errors'] || $this->presentationErrors($actor, $input) || !isset(self::STATUSES[$input['status'] ?? ''])) { throw new \InvalidArgumentException('Événement invalide.'); }
    if (!$this->lock->acquire('tcl_bureau.recipe_events.write', 30)) { throw new ConflictHttpException('Une modification est en cours. Réessayez.'); }
    try {
      $store = $this->stores->get('tcl_bureau.recipe_events');
      $old = $store->get($id);
      if (($old['revision'] ?? 0) !== $revision) { throw new ConflictHttpException('Cet événement a changé. Rechargez sa fiche avant de modifier.'); }
      if (!$old && count($store->getAll()) >= 100) { throw new ConflictHttpException('La limite de 100 séries de recette est atteinte.'); }
      $row = array_intersect_key($input, array_flip([...EventSchedule::FIELDS, 'status', 'category', 'poster_post']));
      $row += ['id' => $id, 'revision' => $revision + 1, 'created' => $old['created'] ?? time(), 'changed' => time(), 'changed_by' => (int) $actor->id()];
      $store->set($id, $row);
      return $id;
    }
    finally { $this->lock->release('tcl_bureau.recipe_events.write'); }
  }
}
