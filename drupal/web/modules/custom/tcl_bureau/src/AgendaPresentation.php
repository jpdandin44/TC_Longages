<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

/** Presentation only: keeps the existing calendar and its access unchanged. */
final class AgendaPresentation {
  public static function render(string $html): string {
    if (!SeasonCatalogue::recipeEnabled() || str_contains($html, 'data-agenda-tools')) { return $html; }
    $tools = '<div class="tcl-agenda-tools" data-agenda-tools hidden><label>Vue <select data-agenda-view><option value="MONTH">Mois</option><option value="WEEK">Semaine</option><option value="AGENDA">Liste</option></select></label><label>Ambiance <select data-agenda-theme><option value="club">Club — bordeaux</option><option value="nature">Nature — vert</option><option value="clair">Clair — bleu</option></select></label><button type="button" data-agenda-reset>Réinitialiser</button><p>Préférences conservées dans ce navigateur. La palette habille la page ; les couleurs du calendrier sont gérées dans Google Agenda.</p><p role="status" aria-live="polite" data-agenda-status></p></div>';
    $html = preg_replace_callback('~<iframe\b[^>]*class="club-calendar"[^>]*>.*?</iframe>~s', static fn(array $m): string => '<div class="tcl-agenda-panel" data-agenda-panel>' . $tools . $m[0] . '</div>', $html, 1, $count);
    if (!$count) { return $html; }
    $cards = '';
    $repository = \Drupal::service('tcl_bureau.communication');
    $shown = 0;
    foreach ($repository->published() as $post) {
      if ($post['category'] !== 'event') { continue; }
      $post = $repository->published((int) $post['id']);
      if (!$post) { continue; }
      if (++$shown > 6) { break; }
      $detail = Html::escape(Url::fromRoute('tcl_bureau.public_post', ['post' => $post['id']])->toString());
      $cards .= '<article class="tcl-agenda-card"><h3><a href="' . $detail . '">' . Html::escape($post['title']) . '</a></h3>';
      if ($post['image_data'] !== '') {
        $image = Html::escape(Url::fromRoute('tcl_bureau.public_image', ['post' => $post['id']], ['query' => ['v' => $post['revision']]])->toString());
        $cards .= CommunicationImage::isPdf($post['image_data'])
          ? '<div class="tcl-pdf-preview" data-pdf-url="' . $image . '">Aperçu de l’affiche PDF…</div>'
          : '<img loading="lazy" src="' . $image . '" alt="' . Html::escape($post['image_alt']) . '">';
      }
      $cards .= '<p><a href="' . $detail . '">Voir l’événement et son affiche</a></p></article>';
    }
    if ($cards !== '') { $html = str_replace('</main>', '<section class="tcl-agenda-announcements"><h2>Affiches des événements du club</h2><p>Communications publiées par le Bureau. Consultez le calendrier pour les dates à jour.</p><div class="tcl-agenda-cards">' . $cards . '</div></section></main>', $html); }
    $base = base_path() . 'modules/custom/tcl_bureau/';
    return str_replace('</head>', '<link rel="stylesheet" href="' . $base . 'css/agenda.css"><script type="module" src="' . $base . 'js/agenda-tools.js"></script><script type="module" src="' . $base . 'js/poster-preview.js"></script></head>', $html);
  }
}
