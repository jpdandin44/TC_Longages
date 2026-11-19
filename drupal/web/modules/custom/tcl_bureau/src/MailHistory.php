<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

/** Read-only projection; never contacts Google and never sends mail. */
final class MailHistory {
  public const LABELS = ['accepted' => 'Accepté par le serveur', 'failed' => 'Échec', 'bounced' => 'Rejet confirmé', 'pending' => 'Résultat à vérifier'];

  public static function retryable(?array $record): bool {
    return $record !== NULL && in_array($record['status'] ?? '', ['failed', 'bounced'], TRUE);
  }

  public static function rows(array $records, array $addresses, bool $production): array {
    $lookup = [];
    foreach ($addresses as $email) {
      $email = strtolower(trim((string) $email));
      if (filter_var($email, FILTER_VALIDATE_EMAIL)) { $lookup[hash('sha256', $email)] = $email; }
    }
    $rows = [];
    foreach ($records as $key => $record) {
      if (!is_array($record) || !preg_match('/^(\d+)\.(\d+)\.([a-f0-9]{64})$/D', (string) $key, $m)) { continue; }
      $email = (string) ($record['recipient'] ?? $lookup[$m[3]] ?? '');
      if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !hash_equals($m[3], hash('sha256', $email))) { $email = ''; }
      // Stable pseudonym across filters/revisions; no real email or error text in recipe.
      if (!$production && !str_ends_with($email, '@example.invalid')) { $email = 'recette-' . substr($m[3], 0, 10) . '@example.invalid'; }
      $attempts = [];
      for ($r = $record; is_array($r); $r = $r['previous'] ?? NULL) {
        $attempts[] = $r;
        if (count($attempts) >= 100) { break; }
      }
      foreach ($attempts as $i => $r) {
        $detail = isset($r['http_status']) ? 'HTTP ' . (int) $r['http_status'] : '';
        if (($r['status'] ?? '') === 'bounced') { $detail = 'Rejet confirmé après acceptation'; }
        $rows[] = ['post' => (int) $m[1], 'revision' => (int) $m[2], 'email' => $email ?: 'Adresse non retrouvée',
          'at' => (int) ($r['at'] ?? 0), 'status' => $r['status'] ?? 'unknown', 'transport' => $r['transport'] ?? 'Ancien transport',
          'attempt' => count($attempts) - $i, 'latest' => $i === 0, 'retryable' => $i === 0 && self::retryable($r), 'detail' => $detail];
      }
    }
    usort($rows, static fn($a, $b) => $b['at'] <=> $a['at']);
    return $rows;
  }
}
