<?php

declare(strict_types=1);

namespace Drupal\tcl_support\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Site\Settings;

final class SupportAccess {

  public static function enabled(): AccessResult {
    return AccessResult::allowedIf(Settings::get('tcl_support_enabled', FALSE) === TRUE)
      ->setCacheMaxAge(0);
  }

}
