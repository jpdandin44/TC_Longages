<?php

declare(strict_types=1);

namespace Drupal\tcl_site\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tcl_site\PublicPageText;

#[FieldWidget(id: 'tcl_page_texts', label: new TranslatableMarkup('Textes des pages TC'), field_types: ['string_long'])]
final class PageTextsWidget extends WidgetBase {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $entity = $items->getEntity();
    $record = NULL;
    $page = NULL;
    foreach (\Drupal::config('tcl_site.public_pages')->get('pages') ?? [] as $key => $candidate) {
      if ((int) $candidate['nid'] === (int) $entity->id()) { $record = $candidate; $page = $key; break; }
    }
    if (!$record) { throw new \RuntimeException('Page non rattachée : utilisez Pages du club.'); }
    $html = file_get_contents(dirname(DRUPAL_ROOT) . '/site-pages/' . $page . '.html');
    if (!hash_equals(PublicPageText::blocksDigest($html), $record['blocksDigest'])) {
      throw new \RuntimeException('Le modèle a changé ; rapprocher les textes avant édition.');
    }
    $values = json_decode($items[$delta]->value ?? '{}', TRUE, 32, JSON_THROW_ON_ERROR);
    $element['regions'] = ['#type' => 'details', '#title' => 'Rubriques et légendes', '#open' => TRUE, '#tree' => TRUE];
    foreach (PublicPageText::blocks($html) as $key => $block) {
      $element['regions'][$key] = [
        '#type' => 'textarea', '#title' => $block['label'], '#rows' => 2,
        '#default_value' => $values[$key] ?? $block['text'], '#maxlength' => 4000, '#required' => TRUE,
      ];
    }
    return $element;
  }

  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    return array_map(static fn(array $item): array => ['value' => json_encode($item['regions'] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)], $values);
  }
}
