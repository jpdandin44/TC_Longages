<?php
declare(strict_types=1);

require dirname(__DIR__) . '/drupal/web/modules/custom/tcl_site/src/PublicPageText.php';
use Drupal\tcl_site\PublicPageText;

$patch = json_decode(stream_get_contents(STDIN), TRUE, 512, JSON_THROW_ON_ERROR);
$html = file_get_contents(dirname(__DIR__) . '/config/launch-beta/source/drupal/site-pages/index.html');
$next = str_replace($patch['oldRow'], $patch['newRows'], $html, $count);
if ($count !== 1) { throw new RuntimeException('Unexpected row.'); }
$blocks = PublicPageText::blocks($html);
$values = array_map(static fn(array $block): string => $block['text'], $blocks);
$keys = array_keys(array_filter($values, static fn(string $value): bool => $value === $patch['oldNote']));
if (count($keys) !== 1) { throw new RuntimeException('Unexpected note.'); }
$values[$keys[0]] = $patch['newNote'];
$custom = array_key_first($values);
if ($custom === $keys[0]) { throw new RuntimeException('Custom fixture overlaps note.'); }
$values[$custom] = 'Texte personnalise preserve & confirme';
$rendered = PublicPageText::renderBlocks($next, $values, PublicPageText::blocksDigest($html));
echo json_encode([
  'blocksDigestPreserved' => PublicPageText::blocksDigest($html) === PublicPageText::blocksDigest($next),
  'titleIntroPreserved' => PublicPageText::regions($html) === PublicPageText::regions($next),
  'customTextPreserved' => str_contains($rendered, 'Texte personnalise preserve &amp; confirme'),
  'oldNoteRemoved' => !str_contains($rendered, $patch['oldNote']) && str_contains($rendered, $patch['newNote']),
  'tariffRows' => substr_count($rendered, 'scope="row"'),
], JSON_THROW_ON_ERROR);
