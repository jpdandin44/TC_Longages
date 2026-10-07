<?php

declare(strict_types=1);

namespace Drupal\tcl_site;

/** Replaces two text regions without rewriting navigation, forms or scripts. */
final class PublicPageText {

  private const PATTERN = '~(<main\b[^>]*>[\s\S]*?<h1\b[^>]*>)([\s\S]*?)(</h1>[\s\S]*?<p\b[^>]*>)([\s\S]*?)(</p>)~i';

  public static function regions(string $html): array {
    if (!preg_match(self::PATTERN, $html, $matches)) {
      throw new \RuntimeException('Présentation de page non reconnue ; aucune édition appliquée.');
    }
    return [
      'title' => preg_replace('~\s+~u', ' ', self::text($matches[2])),
      'intro' => self::text($matches[4]),
      'sourceDigest' => hash('sha256', $matches[2] . "\0" . $matches[4]),
    ];
  }

  private static function text(string $html): string {
    $html = preg_replace('~<span\b[^>]*aria-hidden=["\x27]true["\x27][^>]*>[\s\S]*?</span>~i', '', $html);
    $html = preg_replace('~</(?:span|strong)>~i', "\n", $html);
    $text = preg_replace('~<br\s*[^>]*>~i', "\n", $html);
    return trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
  }

  public static function render(string $html, string $title, string $intro, string $sourceDigest): string {
    $original = self::regions($html);
    if (!hash_equals($original['sourceDigest'], $sourceDigest)) {
      throw new \RuntimeException('Le modèle a changé ; rapprocher les textes enregistrés avant reprise.');
    }
    return preg_replace_callback(self::PATTERN, static function(array $match) use ($original, $title, $intro): string {
      // Keep the original markup byte for byte until its text is edited.
      $heading = $title === $original['title'] ? $match[2] : htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $lead = $intro === $original['intro'] ? $match[4] : nl2br(htmlspecialchars($intro, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), FALSE);
      return $match[1] . $heading . $match[3] . $lead . $match[5];
    }, $html, 1);
  }

  /** Named text blocks inside main; navigation, links and controls stay intact. */
  public static function blocks(string $html): array {
    if (!preg_match('~<main\b[^>]*>([\s\S]*?)</main>~i', $html, $main)) {
      throw new \RuntimeException('Contenu principal absent.');
    }
    preg_match_all('~<(h[2-4]|p|figcaption|summary)\b[^>]*>([\s\S]*?)</\1>~i', $main[1], $matches, PREG_SET_ORDER);
    $blocks = [];
    $firstIntro = TRUE;
    $intro = self::regions($html)['intro'];
    $section = 'Présentation';
    foreach ($matches as $index => $match) {
      if (strtolower($match[1]) === 'p' && $firstIntro && self::text($match[2]) === $intro) {
        $firstIntro = FALSE;
        continue;
      }
      if (preg_match('~<(?:a|button|input|select|textarea|script|style|img|iframe)\b~i', $match[2])) {
        continue;
      }
      $text = self::text($match[2]);
      if ($text === '') { continue; }
      $tag = strtolower($match[1]);
      if (str_starts_with($tag, 'h')) { $section = str_replace("\n", ' ', $text); }
      $kind = str_starts_with($tag, 'h') ? 'Titre' : ($tag === 'figcaption' ? 'Légende' : ($tag === 'summary' ? 'Question' : 'Texte'));
      $blocks['text_' . $index] = ['label' => $kind . ' — ' . $section, 'text' => $text, 'source' => $match[2]];
    }
    return $blocks;
  }

  public static function blocksDigest(string $html): string {
    return hash('sha256', json_encode(self::blocks($html), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
  }

  public static function renderBlocks(string $html, array $values, string $digest): string {
    if (!hash_equals(self::blocksDigest($html), $digest)) {
      throw new \RuntimeException('Les rubriques du modèle ont changé ; rapprocher les textes avant reprise.');
    }
    $blocks = self::blocks($html);
    foreach ($values as $key => $value) {
      if (!isset($blocks[$key]) || !is_string($value) || mb_strlen($value) > 4000 || trim($value) === '') {
        throw new \RuntimeException('Texte éditorial invalide.');
      }
    }
    return preg_replace_callback('~(<main\b[^>]*>)([\s\S]*?)(</main>)~i', static function(array $main) use ($blocks, $values): string {
      $index = 0;
      $body = preg_replace_callback('~(<(h[2-4]|p|figcaption|summary)\b[^>]*>)([\s\S]*?)(</\2>)~i', static function(array $match) use (&$index, $blocks, $values): string {
        $key = 'text_' . $index++;
        if (!isset($blocks[$key], $values[$key]) || $values[$key] === $blocks[$key]['text']) { return $match[0]; }
        preg_match_all('~<span\b[^>]*aria-hidden=["\x27]true["\x27][^>]*>[\s\S]*?</span>~i', $match[3], $decorations);
        return $match[1] . nl2br(htmlspecialchars($values[$key], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), FALSE) . implode('', $decorations[0]) . $match[4];
      }, $main[2]);
      return $main[1] . $body . $main[3];
    }, $html, 1);
  }

}
