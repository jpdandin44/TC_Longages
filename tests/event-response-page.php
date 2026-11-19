<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau {
  final class EventParticipation { public const MAX_PARTICIPANTS = 10; public static function enabled(): bool { return TRUE; } }
  final class RecipeMail { public static function communicationEnabled(): bool { return TRUE; } }
}
namespace {
  require ($argv[1] ?? dirname(__DIR__) . '/drupal/vendor/autoload.php');
  require dirname(__DIR__) . '/drupal/web/modules/custom/tcl_bureau/src/Controller/EventResponseController.php';
  $response = (new \Drupal\tcl_bureau\Controller\EventResponseController())->page();
  $html = $response->getContent(); $policy = $response->headers->get('Content-Security-Policy');
  foreach (['script', 'style'] as $tag) {
    if (!preg_match('~<' . $tag . '>(.*?)</' . $tag . '>~s', $html, $match)) throw new \RuntimeException('Missing ' . $tag);
    $hash = base64_encode(hash('sha256', $match[1], TRUE));
    if (!str_contains($policy, $tag . "-src 'sha256-" . $hash . "'")) throw new \RuntimeException('CSP mismatch: ' . $tag);
    echo 'PASS exact CSP hash for ' . $tag . PHP_EOL;
  }
  if (str_contains($html, '<script src=') || str_contains($html, 'rel="stylesheet"') || str_contains($policy, 'unsafe-inline')) throw new \RuntimeException('External assets or unsafe policy');
  if (substr_count($html, '<option value=') !== 10 || !str_contains($policy, "connect-src 'self'") || !str_contains($response->headers->get('Cache-Control'), 'no-store')) throw new \RuntimeException('Response safeguards');
  echo 'PASS standalone rendering, ten choices and response safeguards' . PHP_EOL;
}
