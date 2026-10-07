<?php

declare(strict_types=1);

namespace Drupal\tcl_site;

use Drupal\tcl_site\Form\ClubContactForm;

/** Embeds a non-Ajax native form while retaining the existing page and edits. */
final class ClubContactPage {

  public static function render(string $html): string {
    $formStart = strpos($html, '<form id="contact-form"');
    if ($formStart === FALSE || substr_count($html, '<form id="contact-form"') !== 1) {
      throw new \RuntimeException('Modèle du formulaire de contact incompatible.');
    }
    $section = strrpos(substr($html, 0, $formStart), '<section class="action-card">');
    $headingEnd = $section === FALSE ? FALSE : strpos($html, '</h2>', $section);
    $preview = strpos($html, '<section id="contact-preview"', $formStart);
    $end = $preview === FALSE ? FALSE : strpos($html, '</section></section>', $preview);
    if ($headingEnd === FALSE || $headingEnd > $formStart || $end === FALSE) {
      throw new \RuntimeException('Région du formulaire de contact incompatible.');
    }
    $form = \Drupal::formBuilder()->getForm(ClubContactForm::class);
    $messages = ['#type' => 'status_messages'];
    $renderer = \Drupal::service('renderer');
    // All validation and submission happen on the server; no Ajax or JS library.
    $body = (string) $renderer->renderRoot($messages) . (string) $renderer->renderRoot($form);
    $html = str_replace('Boîte créée. Envoi depuis le site : activation à confirmer après recette. Pour le moment, utilisez l’adresse du club ci-dessus.',
      'Pour signaler un problème sur le site, utilisez le bouton « Signaler un problème ».', $html);
    $style = '<style>.tcl-club-contact .form-item{margin:1rem 0}.tcl-club-contact label{display:block;font-weight:600}.tcl-club-contact input:not([type=hidden]):not([type=checkbox]),.tcl-club-contact textarea{width:100%;box-sizing:border-box;padding:.7rem;border:1px solid #777;border-radius:.25rem;font:inherit}.tcl-club-contact textarea{min-height:9rem}.tcl-club-contact .description{font-size:.9rem}.tcl-club-contact .form-item--error-message,[role=alert]{color:#8b1025}.tcl-club-contact .tcl-contact-honeypot{display:none}.tcl-club-contact input[type=submit]{cursor:pointer;background:#741323;color:white;font-weight:600}.tcl-club-contact input:focus-visible,.tcl-club-contact textarea:focus-visible{outline:3px solid #a6192e;outline-offset:2px}</style>';
    return substr($html, 0, $headingEnd + 5) . $style . $body . '</section>'
      . substr($html, $end + strlen('</section></section>'));
  }

}
