<?php
declare(strict_types=1);
require dirname(__DIR__) . '/drupal/bin/local-bootstrap.php';
use Drupal\tcl_bureau\AgendaPresentation;
use Symfony\Component\HttpFoundation\Request;
$db = Drupal::database();
$transaction = $db->startTransaction();
$checks = [];
function checkAgenda(string $label, bool $ok): void {
  global $checks;
  $checks[] = ['label' => $label, 'passed' => $ok];
  if (!$ok) { throw new RuntimeException($label); }
}
$page = '<html><head></head><body><main><iframe class="club-calendar" title="Agenda" src="https://calendar.google.com/calendar/embed?src=fixture%40example.invalid&amp;ctz=Europe%2FParis"></iframe></main></body></html>';
try {
  $db->update('tcl_bureau_post')->fields(['status' => 'draft'])->execute();
  $rendered = AgendaPresentation::render($page);
  checkAgenda('Existing calendar URL and timezone preserved', str_contains($rendered, 'src=fixture%40example.invalid&amp;ctz=Europe%2FParis'));
  checkAgenda('Presentation controls added once', str_contains($rendered, 'data-agenda-tools') && AgendaPresentation::render($rendered) === $rendered);
  checkAgenda('Removed palette not rendered', !str_contains($rendered, 'Ambiance') && !str_contains($rendered, 'data-agenda-theme'));
  $scriptHash = substr(hash_file('sha256', dirname(__DIR__) . '/drupal/web/modules/custom/tcl_bureau/js/agenda-tools.js'), 0, 12);
  checkAgenda('Script URL changes with content to invalidate old browser caches', str_contains($rendered, 'agenda-tools.js?v=' . $scriptHash));
  checkAgenda('Missing calendar leaves page unchanged', AgendaPresentation::render('<head></head><main>À venir</main>') === '<head></head><main>À venir</main>');
  checkAgenda('No empty event section', !str_contains($rendered, 'tcl-agenda-announcements'));
  $row = ['submission_key' => bin2hex(random_bytes(16)), 'title' => 'Événement <script>fictif</script>', 'category' => 'event', 'body' => 'Recette', 'link' => '', 'image_alt' => '', 'image_data' => '', 'status' => 'published', 'channels' => 'site', 'revision' => 3, 'approved_revision' => 3, 'published_at' => 100, 'created' => 1, 'changed' => 1, 'changed_by' => 0];
  $id = $db->insert('tcl_bureau_post')->fields($row)->execute();
  $rendered = AgendaPresentation::render($page);
  checkAgenda('Published event linked and title escaped', str_contains($rendered, '/actualites/' . $id) && str_contains($rendered, '&lt;script&gt;fictif&lt;/script&gt;'));
  foreach (['draft', 'validated', 'archived'] as $state) {
    $db->update('tcl_bureau_post')->fields(['status' => $state])->condition('id', $id)->execute();
    checkAgenda($state . ' stays absent', !str_contains(AgendaPresentation::render($page), 'tcl-agenda-announcements'));
  }
  $db->update('tcl_bureau_post')->fields(['status' => 'published', 'approved_revision' => 2])->condition('id', $id)->execute();
  checkAgenda('Obsolete approval stays absent', !str_contains(AgendaPresentation::render($page), 'tcl-agenda-announcements'));
  $db->update('tcl_bureau_post')->fields(['approved_revision' => 3, 'channels' => 'whatsapp'])->condition('id', $id)->execute();
  checkAgenda('Social-only event stays absent', !str_contains(AgendaPresentation::render($page), 'tcl-agenda-announcements'));
  Drupal::requestStack()->push(Request::create('https://tclongages.fr/calendrier.html'));
  try { checkAgenda('Production rendering unchanged', AgendaPresentation::render($page) === $page); }
  finally { Drupal::requestStack()->pop(); }
} finally {
  $transaction->rollBack();
  file_put_contents(dirname(__DIR__) . '/.local/agenda-presentation-tests.json', json_encode(['checkedAt' => gmdate('c'), 'scope' => 'isolated local SQLite; no hosted qualification', 'checks' => $checks], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
echo count($checks) . " agenda checks passed.\n";
