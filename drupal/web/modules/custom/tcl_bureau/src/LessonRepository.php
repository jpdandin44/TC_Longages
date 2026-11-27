<?php

declare(strict_types=1);
namespace Drupal\tcl_bureau;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class LessonRepository {

  public function __construct(
    private KeyValueFactoryInterface $stores,
    private LockBackendInterface $lock,
    private EventRepository $events,
    private TimeInterface $time,
  ) {}

  public function allowed(AccountInterface $actor): bool {
    return $this->events->allowed($actor);
  }

  public function now(): int {
    return $this->time->getRequestTime();
  }

  private function collection(AccountInterface $actor): string {
    if (!$this->allowed($actor)) {
      throw new AccessDeniedHttpException();
    }
    return EventRepository::storeName() . '.lesson_groups';
  }

  public function all(AccountInterface $actor): array {
    $rows = $this->stores->get($this->collection($actor))->getAll();
    uasort($rows, static fn(array $a, array $b): int => [$b['season'], $a['name']] <=> [$a['season'], $b['name']]);
    return $rows;
  }

  public function get(AccountInterface $actor, string $group): array {
    return $this->stores->get($this->collection($actor))->get($group) ?? throw new NotFoundHttpException();
  }

  public function create(AccountInterface $actor, string $id, array $input): void {
    $collection = $this->collection($actor);
    $prepared = LessonSchedule::creation($input, $this->now(), (int) $actor->id());
    if (!preg_match('/^[a-f0-9]{32}$/D', $id) || $prepared['errors']) {
      throw new \InvalidArgumentException('Le groupe contient des informations invalides.');
    }
    $lock = $collection . '.create';
    if (!$this->lock->acquire($lock)) {
      throw new ConflictHttpException('Une création est en cours. Réessayez.');
    }
    try {
      $store = $this->stores->get($collection);
      if ($store->has($id)) {
        throw new ConflictHttpException('Ce groupe a déjà été enregistré.');
      }
      $group = $prepared['group'];
      foreach ($store->getAll() as $existing) {
        if ($existing['season'] === $group['season'] && mb_strtolower($existing['name']) === mb_strtolower($group['name'])) {
          throw new ConflictHttpException('Un groupe porte déjà ce nom pour cette saison.');
        }
      }
      $store->set($id, $group + ['id' => $id, 'revision' => 1]);
    }
    finally {
      $this->lock->release($lock);
    }
  }

  public function change(AccountInterface $actor, string $id, string $date, int $revision, string $action, array $input): void {
    $collection = $this->collection($actor);
    $lock = $collection . '.' . $id;
    if (!$this->lock->acquire($lock)) {
      throw new ConflictHttpException('Le groupe est en cours de modification. Réessayez.');
    }
    try {
      $store = $this->stores->get($collection);
      $group = $store->get($id);
      if (!isset($group['sessions'][$date])) {
        throw new NotFoundHttpException();
      }
      if ($group['revision'] !== $revision) {
        throw new ConflictHttpException('Le groupe a changé. Rechargez la séance avant de réessayer.');
      }
      $group = LessonSchedule::change($group, $date, $action, $input, $this->now(), (int) $actor->id());
      $group['revision']++;
      $store->set($id, $group);
    }
    finally {
      $this->lock->release($lock);
    }
  }

}
