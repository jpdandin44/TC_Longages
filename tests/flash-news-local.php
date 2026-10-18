<?php
declare(strict_types=1);

// Real Drupal database/render integration, isolated SQLite only, rolled back.
require dirname(__DIR__) . '/drupal/bin/local-bootstrap.php';

use Drupal\tcl_bureau\Controller\CommunicationController;

$database = Drupal::database();
$transaction = $database->startTransaction();
$checks = [];
function verify(string $label, bool $ok): void {
  global $checks;
  $checks[] = ['label' => $label, 'passed' => $ok];
  if (!$ok) { throw new RuntimeException($label); }
}
try {
  // Existing local content is masked only inside this rolled-back transaction.
  $database->update('tcl_bureau_post')->fields(['status' => 'draft'])->execute();
  $controller = new CommunicationController(Drupal::service('tcl_bureau.communication'));
  $render = static fn(): string => $controller->front()->getContent();
  verify('No banner without published news', !str_contains($render(), 'id="flash-news"'));
  $row = ['submission_key' => bin2hex(random_bytes(16)), 'title' => 'Ancienne actualité fictive', 'category' => 'club', 'body' => 'Texte fictif.', 'link' => '', 'image_alt' => '', 'image_data' => '', 'status' => 'published', 'revision' => 3, 'approved_revision' => 3, 'published_at' => 100, 'created' => 1, 'changed' => 1, 'changed_by' => 0];
  $first = $database->insert('tcl_bureau_post')->fields($row)->execute();
  $row['submission_key'] = bin2hex(random_bytes(16));
  $row['title'] = 'Nouvelle <script>alert(1)</script> actualité';
  $row['body'] = 'Résumé <img src=x onerror=alert(2)> ' . str_repeat('été ', 80);
  $row['published_at'] = 200;
  $row['status'] = 'draft';
  $second = $database->insert('tcl_bureau_post')->fields($row)->execute();
  $banner = static function () use ($render): string {
    preg_match('~<section id="flash-news".*?</section>~s', $render(), $match);
    return $match[0] ?? '';
  };
  verify('New draft stays private', !str_contains($render(), 'Nouvelle'));
  $database->update('tcl_bureau_post')->fields(['status' => 'validated'])->condition('id', $second)->execute();
  verify('Validation alone stays private', !str_contains($render(), 'Nouvelle'));
  $database->update('tcl_bureau_post')->fields(['status' => 'published', 'approved_revision' => 2])->condition('id', $second)->execute();
  verify('Obsolete validation stays private', !str_contains($render(), 'Nouvelle'));
  $database->update('tcl_bureau_post')->fields(['approved_revision' => 3])->condition('id', $second)->execute();
  $html = $banner();
  verify('Latest publication selected', str_contains($html, 'Nouvelle') && !str_contains($html, 'Ancienne'));
  verify('Title and summary escaped', !str_contains($html, '<script>') && !str_contains($html, '<img') && str_contains($html, '&lt;script&gt;') && str_contains($html, '&lt;img'));
  verify('Summary bounded without corrupting accents', str_contains($html, '…') && mb_check_encoding($html, 'UTF-8') && mb_strlen($html) < 1000);
  verify('Public detail and index links', str_contains($html, '/actualites/' . $second) && str_contains($html, 'Toutes les actualités'));
  verify('Banner near top of main', preg_match('~<main\b[^>]*><style>.*?id="flash-news"~s', $render()) === 1);
  verify('Private no-store response retained', str_contains($controller->front()->headers->get('Cache-Control'), 'no-store'));
  $database->update('tcl_bureau_post')->fields(['body' => ''])->condition('id', $second)->execute();
  verify('Poster-only news has no empty summary', !str_contains($banner(), '<p></p>'));
  foreach (['validated', 'draft', 'archived'] as $status) {
    $database->update('tcl_bureau_post')->fields(['status' => $status])->condition('id', $second)->execute();
    verify('Fallback after ' . $status, str_contains($banner(), 'Ancienne') && !str_contains($banner(), 'Nouvelle'));
  }
  $database->update('tcl_bureau_post')->fields(['status' => 'archived'])->condition('id', $first)->execute();
  verify('Banner disappears after last withdrawal', $banner() === '');
} finally {
  $transaction->rollBack();
  file_put_contents(dirname(__DIR__) . '/.local/flash-news-tests.json', json_encode(['checkedAt' => gmdate('c'), 'scope' => 'isolated SQLite Drupal query and homepage render; no hosted qualification', 'checks' => $checks], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
echo count($checks) . " Flash News integration checks passed.\n";
