<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau\Form;

/** Explicit manager reset; shares invitation access, rate limit and transport. */
final class AccountPasswordResetForm extends AccountInvitationForm {
  protected bool $reset = TRUE;
}
