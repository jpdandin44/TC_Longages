<?php
declare(strict_types=1);
namespace Drupal\Core\Session { interface AccountInterface {} }
namespace Drupal\Core\Controller { class ControllerBase {} }
namespace {
  final class Drupal {
    public static string $origin = 'https://preprod.tclongages.fr';
    public static bool $allowed = false;
    public static function request(): object {
      return new class {
        public function getSchemeAndHttpHost(): string { return Drupal::$origin; }
      };
    }
    public static function service(string $name): object {
      if ($name !== 'tcl_bureau.communication') { throw new RuntimeException('Unexpected service'); }
      return new class {
        public function allowed($account): bool { return Drupal::$allowed; }
      };
    }
  }
  require __DIR__ . '/../drupal/web/modules/custom/tcl_bureau/src/Controller/ClubFollowupController.php';
  $actor = new class implements \Drupal\Core\Session\AccountInterface {};
  foreach ([
    ['https://preprod.tclongages.fr', false, false],
    ['https://preprod.tclongages.fr', true, true],
    ['https://tclongages.fr', true, false],
    ['http://preprod.tclongages.fr', true, false],
    ['https://other.example', true, false],
  ] as [$origin, $allowed, $expected]) {
    Drupal::$origin = $origin;
    Drupal::$allowed = $allowed;
    if (\Drupal\tcl_bureau\Controller\ClubFollowupController::enabled($actor) !== $expected) {
      throw new RuntimeException('Access boundary failure');
    }
  }
  echo "Five access-boundary checks passed.\n";
}
