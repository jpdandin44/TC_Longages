<?php
declare(strict_types=1);
require dirname(__DIR__) . '/drupal/bin/local-bootstrap.php';
use Drupal\tcl_bureau\SeasonCatalogue;
use Symfony\Component\HttpFoundation\Request;

$checks = [];
function checkCatalogue(string $label, bool $ok): void {
  global $checks;
  $checks[] = ['label' => $label, 'passed' => $ok];
  if (!$ok) { throw new RuntimeException($label); }
}
$seed = json_decode(file_get_contents(dirname(__DIR__) . '/drupal/web/modules/custom/tcl_bureau/resources/season-seed.json'), TRUE);
checkCatalogue('Ten initial formulas validate', count($seed['formulas']) === 10 && !SeasonCatalogue::validate($seed));
checkCatalogue('No slots or final notice invented', $seed['slots'] === [] && $seed['notice'] === '');
$bad = $seed; $bad['formulas'][0]['amountCents'] = -1;
checkCatalogue('Negative amount rejected', isset(SeasonCatalogue::validate($bad)['formulas']));
$bad['formulas'][0]['amountCents'] = '7000';
checkCatalogue('Non integer amount rejected', isset(SeasonCatalogue::validate($bad)['formulas']));
$bad = $seed; $bad['formulas'][1]['id'] = $bad['formulas'][0]['id'];
checkCatalogue('Duplicate formula rejected', isset(SeasonCatalogue::validate($bad)['formulas']));
$bad = $seed; $bad['season'] = '2026-2028';
checkCatalogue('Invalid season rejected', isset(SeasonCatalogue::validate($bad)['season']));
$bad = $seed; $bad['slots'] = ['Exemple', 'Exemple'];
checkCatalogue('Duplicate slots rejected', isset(SeasonCatalogue::validate($bad)['slots']));
$bad = $seed; $bad['formulas'][0]['label'] = str_repeat('a', 121);
checkCatalogue('Oversized label rejected', isset(SeasonCatalogue::validate($bad)['formulas']));
$stack = Drupal::requestStack();
$stack->push(Request::create('https://tclongages.fr/adherer'));
try { checkCatalogue('Production hostname always denied even with recipe flag', !SeasonCatalogue::recipeEnabled()); }
finally { $stack->pop(); }
$store = Drupal::keyValue('tcl_bureau.seasons');
$previous = $store->get('state');
try {
  $store->set('state', ['revision' => 1, 'draft' => $seed, 'published' => $seed, 'history' => []]);
  $service = Drupal::service('tcl_bureau.seasons');
  $page = '<main><section id="adherer"><div class="join-intro"><h2>Texte conservé</h2><a href="/contact.html">Nous écrire</a></div><div class="join-steps">Étapes conservées</div></section></main>';
  $rendered = $service->renderHomepage($page);
  checkCatalogue('Editorial homepage receives one fictional registration entry', substr_count($rendered, 'id="tcl-registration-entry"') === 1 && str_contains($rendered, '/adherer') && str_contains($rendered, 'données fictives'));
  checkCatalogue('Editorial contents and contact entry preserved', str_contains($rendered, '<h2>Texte conservé</h2>') && str_contains($rendered, '<a href="/contact.html">Nous écrire</a>') && str_contains($rendered, 'Étapes conservées'));
  checkCatalogue('Homepage entry is idempotent', $service->renderHomepage($rendered) === $rendered);
  $store->set('state', ['revision' => 1, 'draft' => $seed, 'published' => NULL, 'history' => []]);
  checkCatalogue('Unpublished catalogue does not expose entry', $service->renderHomepage($page) === $page);
}
finally {
  if ($previous === NULL) { $store->delete('state'); }
  else { $store->set('state', $previous); }
}
file_put_contents(dirname(__DIR__) . '/.local/season-catalogue-tests.json', json_encode(['checks' => $checks, 'passed' => TRUE], JSON_PRETTY_PRINT));
echo count($checks) . " catalogue checks passed.\n";
