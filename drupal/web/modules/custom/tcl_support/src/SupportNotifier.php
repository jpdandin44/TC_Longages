<?php

declare(strict_types=1);

namespace Drupal\tcl_support;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Core\Mail\Plugin\Mail\TestMailCollector;
use Drupal\Core\Site\Settings;

final class SupportNotifier {

  public function __construct(
    private readonly SupportRepository $repository,
    private readonly MailManagerInterface $mailManager,
  ) {}

  public function send(int $id, int $actor = 0): string {
    $ticket = $this->repository->load($id);
    if (!$ticket) {
      throw new \InvalidArgumentException('Demande absente.');
    }
    $state = 'disabled';
    try {
      $mode = Settings::get('tcl_support_mail_mode', 'disabled');
      $plugin = $this->mailManager->getInstance(['module' => 'tcl_support', 'key' => 'report']);
      // A NullMail success can never qualify an actual notification.
      $capture = $mode === 'capture' && $plugin instanceof TestMailCollector;
      $transport = $mode === 'transport' && $plugin instanceof PhpMail
        && Settings::get('tcl_support_transport_qualified', FALSE) === TRUE;
      if ($capture || $transport) {
        $result = $this->mailManager->mail('tcl_support', 'report', 'support@tclongages.fr',
          'fr', ['ticket' => $ticket], NULL, TRUE);
        $state = ($result['result'] ?? FALSE) === TRUE
          ? ($capture ? 'captured_local' : 'submitted_transport') : 'failed';
      }
    }
    catch (\Throwable) {
      $state = 'failed';
      \Drupal::logger('tcl_support')->warning('Notification indisponible ; demande conservée.');
    }
    $this->repository->notified($id, $state, $actor);
    return $state;
  }

}
