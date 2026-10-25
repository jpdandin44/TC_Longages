<?php
declare(strict_types=1);

namespace Drupal\tcl_bureau;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** Seasonal drafts and immutable published revisions; no automatic activation. */
final class SeasonCatalogue {
  public function __construct(private KeyValueFactoryInterface $stores, private LockBackendInterface $lock, private TeamRepository $access) {}

  public static function recipeEnabled(): bool {
    return Settings::get('tcl_registration_recipe_enabled', FALSE) === TRUE
      && in_array(\Drupal::request()->getHost(), ['127.0.0.1', 'localhost', 'preprod.tclongages.fr'], TRUE);
  }

  public function allowed(AccountInterface $actor): bool {
    return $this->access->allowed($actor, 'manage tcl registrations');
  }

  private function state(): array {
    $saved = $this->stores->get('tcl_bureau.seasons')->get('state');
    if ($saved !== NULL) { return $saved; }
    $seed = json_decode(file_get_contents(dirname(__DIR__) . '/resources/season-seed.json'), TRUE, 32, JSON_THROW_ON_ERROR);
    if (self::validate($seed)) { throw new \RuntimeException('Catalogue initial invalide.'); }
    return ['revision' => 0, 'draft' => $seed, 'published' => NULL, 'history' => []];
  }

  public function read(AccountInterface $actor): array {
    if (!$this->allowed($actor)) { throw new AccessDeniedHttpException(); }
    return $this->state();
  }

  public function published(): ?array {
    return self::recipeEnabled() ? $this->state()['published'] : NULL;
  }

  public function renderHomepage(string $html): string {
    $catalogue = $this->published();
    if (!$catalogue) { return $html; }
    $escape = static fn(string $text): string => \Drupal\Component\Utility\Html::escape($text);
    // The editorial entry and tariff grid may coexist; update both independently.
    // Add the recipe entry without replacing its managed texts or external links.
    if (str_contains($html, 'id="adherer"') && !str_contains($html, 'id="tcl-registration-entry"')) {
      $entry = '<div id="tcl-registration-entry"><p><strong>Recette fictive — saison ' . $escape($catalogue['season']) . '</strong><br>Aucune adhésion réelle : utilisez uniquement des données fictives.</p><a class="button button-dark" href="' . $escape(\Drupal\Core\Url::fromRoute('tcl_bureau.registration_recipe')->toString()) . '">Formulaire d’adhésion — test</a></div>';
      $result = preg_replace_callback('~(<section\\b[^>]*\\bid="adherer"[^>]*>.*?<div class="join-intro">.*?)(</div>)~s', static fn(array $match): string => $match[1] . $entry . $match[2], $html, 1, $count);
      if ($count !== 1) { throw new \RuntimeException('Rubrique adhésion non reconnue : rapprochement nécessaire.'); }
      $html = $result;
    }
    if (!str_contains($html, 'class="tariff-grid"')) { return $html; }
    $table = '<div class="tariff-grid"><p class="tariff-season">Saison ' . $escape($catalogue['season']) . ' — RECETTE FICTIVE</p><p>Catalogue de test publié par le Bureau ; aucune inscription réelle.</p>';
    foreach (['adult' => 'Adultes', 'minor' => 'Mineurs'] as $profile => $label) {
      $table .= '<table class="tariff-table"><caption>' . $label . '</caption><thead><tr><th scope="col">Formule</th><th scope="col">Tarif de référence</th></tr></thead><tbody>';
      foreach ($catalogue['formulas'] as $formula) {
        if ($formula['profile'] === $profile) { $table .= '<tr><th scope="row">' . $escape($formula['label']) . '</th><td>' . number_format($formula['amountCents'] / 100, 2, ',', ' ') . ' €</td></tr>'; }
      }
      $table .= '</tbody></table>';
    }
    $table .= '<p><a href="' . $escape(\Drupal\Core\Url::fromRoute('tcl_bureau.registration_recipe')->toString()) . '">Tester une demande fictive</a></p></div>';
    $result = preg_replace_callback('~<div class="tariff-grid">.*?</div>~s', static fn() => $table, $html, -1, $count);
    if ($count !== 1) { throw new \RuntimeException('Tableau tarifaire non reconnu : rapprochement nécessaire.'); }
    return $result;
  }

  public static function validate(array $input): array {
    $errors = [];
    $season = $input['season'] ?? '';
    if (!is_string($season) || !preg_match('/^(\d{4})-(\d{4})$/D', $season, $m) || (int) $m[2] !== (int) $m[1] + 1) { $errors['season'] = 'Saison attendue : 2026-2027.'; }
    if (!is_array($input['formulas'] ?? NULL) || count($input['formulas']) < 1 || count($input['formulas']) > 30) { return $errors + ['formulas' => 'Renseignez entre 1 et 30 formules.']; }
    $ids = [];
    foreach ($input['formulas'] as $row) {
      if (!is_array($row) || !is_string($row['id'] ?? NULL) || !preg_match('/^[a-z0-9-]{1,60}$/D', $row['id']) || isset($ids[$row['id']]) || !is_string($row['label'] ?? NULL) || trim($row['label']) === '' || mb_strlen($row['label']) > 120 || !in_array($row['profile'] ?? '', ['adult', 'minor'], TRUE) || !is_int($row['amountCents'] ?? NULL) || $row['amountCents'] < 0 || $row['amountCents'] > 1000000) {
        $errors['formulas'] = 'Vérifiez les libellés, profils, montants et identifiants uniques des formules.';
        break;
      }
      $ids[$row['id']] = TRUE;
    }
    if (!is_string($input['notice'] ?? NULL) || mb_strlen($input['notice']) > 3000) { $errors['notice'] = 'Le texte doit rester inférieur à 3 000 caractères.'; }
    if (!is_array($input['slots'] ?? NULL) || count($input['slots']) > 30) { $errors['slots'] = 'Au maximum 30 plages.'; }
    else {
      foreach ($input['slots'] as $slot) {
        if (!is_string($slot) || trim($slot) === '' || mb_strlen($slot) > 120) { $errors['slots'] = 'Chaque plage doit contenir entre 1 et 120 caractères.'; }
      }
      if (count(array_unique($input['slots'], SORT_REGULAR)) !== count($input['slots'])) { $errors['slots'] = 'Chaque plage doit être unique.'; }
    }
    return $errors;
  }

  public function save(AccountInterface $actor, array $input, int $expected, bool $publish = FALSE): int {
    if (!$this->allowed($actor) || ($publish && !self::recipeEnabled())) { throw new AccessDeniedHttpException(); }
    if (self::validate($input)) { throw new \InvalidArgumentException('Catalogue invalide.'); }
    if (!$this->lock->acquire('tcl_bureau.seasons.write', 30)) { throw new ConflictHttpException('Une modification est en cours. Réessayez.'); }
    try {
      $state = $this->state();
      if ($state['revision'] !== $expected) { throw new ConflictHttpException('Le catalogue a changé. Rechargez la page.'); }
      $revision = $expected + 1;
      $draft = ['season' => $input['season'], 'formulas' => array_values($input['formulas']), 'slots' => array_values($input['slots']), 'notice' => $input['notice']];
      $state['draft'] = $draft;
      $state['revision'] = $revision;
      if ($publish) {
        $state['published'] = $draft + ['revision' => $revision, 'publishedAt' => time(), 'publishedBy' => (int) $actor->id(), 'mode' => 'recipe'];
        $state['history'][] = $state['published'];
      }
      $this->stores->get('tcl_bureau.seasons')->set('state', $state);
      return $revision;
    }
    finally { $this->lock->release('tcl_bureau.seasons.write'); }
  }
}
