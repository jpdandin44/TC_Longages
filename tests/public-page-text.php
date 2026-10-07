<?php

declare(strict_types=1);

use Drupal\tcl_site\PublicPageText;

require dirname(__DIR__) . '/drupal/web/modules/custom/tcl_site/src/PublicPageText.php';

ini_set('pcre.backtrack_limit', '1000000');
$html = file_get_contents(dirname(__DIR__) . '/officiel/index.html');
if (strlen($html) < 2000000 || !str_contains($html, 'data:image/png;base64,')) {
  throw new RuntimeException('Le test exige la vraie page avec la nouvelle photo PNG.');
}
$regions = PublicPageText::regions($html);
$blocks = PublicPageText::blocks($html);
if (count($blocks) < 10 || !array_filter($blocks, static fn(array $block): bool => str_starts_with($block['label'], 'Légende'))) {
  throw new RuntimeException('Les textes et la légende doivent rester éditables.');
}
$digest = PublicPageText::blocksDigest($html);
if (PublicPageText::renderBlocks($html, [], $digest) !== $html
    || PublicPageText::render($html, $regions['title'], $regions['intro'], $regions['sourceDigest']) !== $html) {
  throw new RuntimeException('Une lecture sans édition doit conserver la page et la photo octet pour octet.');
}
$edited = PublicPageText::render($html, '<script>titre</script>', 'Présentation', $regions['sourceDigest']);
if (!str_contains($edited, '&lt;script&gt;titre&lt;/script&gt;') || str_contains($edited, '<script>titre</script>')) {
  throw new RuntimeException('Les textes saisis doivent rester échappés.');
}
foreach (['<html><h1>Titre</h1></html>', '<main><h1>Titre</h1><p>Texte</p>'] as $invalid) {
  try {
    PublicPageText::blocks($invalid);
    throw new LogicException('Une page sans contenu principal complet doit être refusée.');
  } catch (RuntimeException $expected) {
    if ($expected->getMessage() !== 'Contenu principal absent.') { throw $expected; }
  }
}
echo json_encode(['passed' => TRUE, 'bytes' => strlen($html), 'blocks' => count($blocks), 'roundTripPreserved' => TRUE], JSON_THROW_ON_ERROR) . "\n";
