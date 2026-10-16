<?php

declare(strict_types=1);

namespace Drupal\tcl_site;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/** Native nodes/revisions, seeded once from the seven public file templates. */
final class PublicPageEditor {

  public const TYPE = 'tcl_public_page';
  public const PAGES = ['index', 'competitions', 'calendrier', 'disponibilites', 'equipes', 'espace', 'contact'];
  public const FIELD = 'field_tcl_intro';
  public const TEXTS = 'field_tcl_textes';

  public static function initialize(): void {
    $config = \Drupal::configFactory()->getEditable('tcl_site.public_pages');
    $pages = $config->get('pages');
    // Validate every template before the first write; never overwrite edits.
    $regions = [];
    foreach (self::PAGES as $key) {
      $file = dirname(DRUPAL_ROOT) . '/site-pages/' . $key . '.html';
      if (!is_readable($file)) {
        throw new \RuntimeException('Les sept modèles doivent être présents avant initialisation.');
      }
      $regions[$key] = PublicPageText::regions(file_get_contents($file));
      $regions[$key]['blocks'] = PublicPageText::blocks(file_get_contents($file));
      $regions[$key]['blocksDigest'] = PublicPageText::blocksDigest(file_get_contents($file));
    }
    if ($pages !== NULL) {
      if (array_keys($pages) !== self::PAGES) {
        throw new \RuntimeException('Rattachement éditorial incomplet ; examen manuel requis.');
      }
      foreach ($pages as $key => $record) {
        $node = Node::load($record['nid']);
        if (!$node || $node->bundle() !== self::TYPE || !$node->hasField(self::TEXTS)
            || !hash_equals($regions[$key]['sourceDigest'], $record['sourceDigest'])
            || !hash_equals($regions[$key]['blocksDigest'], $record['blocksDigest'] ?? '')) {
          throw new \RuntimeException('Contenu ou modèle éditorial incompatible ; aucune réinitialisation.');
        }
      }
      return;
    }
    if (NodeType::load(self::TYPE) || FieldStorageConfig::loadByName('node', self::FIELD)) {
      throw new \RuntimeException('Configuration éditoriale préexistante ; aucune substitution automatique.');
    }
    NodeType::create([
      'type' => self::TYPE,
      'name' => 'Page publique TC',
      'description' => 'Titre et présentation des sept pages du club.',
      'new_revision' => TRUE,
      'display_submitted' => FALSE,
    ])->save();
    FieldStorageConfig::create(['field_name' => self::FIELD, 'entity_type' => 'node', 'type' => 'string_long'])->save();
    FieldConfig::create([
      'field_name' => self::FIELD, 'entity_type' => 'node', 'bundle' => self::TYPE,
      'label' => 'Texte de présentation', 'required' => TRUE,
      'description' => 'Texte simple affiché sous le titre de la page ; les retours à la ligne sont conservés.',
    ])->save();
    FieldStorageConfig::create(['field_name' => self::TEXTS, 'entity_type' => 'node', 'type' => 'string_long'])->save();
    FieldConfig::create([
      'field_name' => self::TEXTS, 'entity_type' => 'node', 'bundle' => self::TYPE,
      'label' => 'Rubriques et légendes', 'required' => TRUE,
    ])->save();
    EntityFormDisplay::create([
      'targetEntityType' => 'node', 'bundle' => self::TYPE, 'mode' => 'default', 'status' => TRUE,
    ])->setComponent('title', ['type' => 'string_textfield', 'weight' => 0])
      ->setComponent(self::FIELD, ['type' => 'string_textarea', 'weight' => 1, 'settings' => ['rows' => 5]])
      ->setComponent(self::TEXTS, ['type' => 'tcl_page_texts', 'weight' => 2])
      ->setComponent('status', ['type' => 'boolean_checkbox', 'weight' => 2])
      ->setComponent('revision_log', ['type' => 'string_textarea', 'weight' => 3])->save();
    EntityViewDisplay::create([
      'targetEntityType' => 'node', 'bundle' => self::TYPE, 'mode' => 'default', 'status' => TRUE,
    ])->setComponent(self::FIELD, ['type' => 'basic_string'])
      ->removeComponent(self::TEXTS)->save();
    $pages = [];
    foreach ($regions as $key => $region) {
      $node = Node::create([
        'type' => self::TYPE, 'title' => $region['title'], self::FIELD => $region['intro'],
        self::TEXTS => json_encode(array_map(static fn(array $block): string => $block['text'], $region['blocks']), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        'uid' => 1, 'status' => TRUE,
      ]);
      $node->setRevisionLogMessage('Texte initial repris du modèle public du club.');
      $node->save();
      $pages[$key] = ['nid' => (int) $node->id(), 'sourceDigest' => $region['sourceDigest'], 'blocksDigest' => $region['blocksDigest']];
    }
    $config->set('pages', $pages)->save();
  }

  public static function node(string $key): ?\Drupal\node\NodeInterface {
    $record = \Drupal::config('tcl_site.public_pages')->get('pages.' . $key);
    if (!$record) {
      return NULL;
    }
    $node = Node::load($record['nid']);
    if (!$node || $node->bundle() !== self::TYPE || !$node->hasField(self::FIELD)) {
      throw new \RuntimeException('Contenu éditorial indisponible.');
    }
    return $node;
  }

}
