<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\tcl_bureau\SeasonCatalogue;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class SeasonCatalogueForm extends FormBase {
  public function __construct(protected SeasonCatalogue $catalogue) {}
  public static function create(ContainerInterface $container) { return new static($container->get('tcl_bureau.seasons')); }
  public function getFormId(): string { return 'tcl_bureau_season_catalogue'; }
  public function access(AccountInterface $account) { return AccessResult::allowedIf($this->catalogue->allowed($account))->setCacheMaxAge(0); }
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $state = $this->catalogue->read($this->currentUser());
    $draft = $state['draft'];
    $form['#cache']['max-age'] = 0;
    $form['#attributes']['class'][] = 'tcl-bureau';
    $form['#attributes']['class'][] = 'tcl-season-catalogue';
    $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['intro'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'Préparez la saison en brouillon, puis publiez explicitement pour la recette. Les dossiers déjà déposés conservent leur tarif. Aucun règlement, remise ou licence n’est calculé.'];
    $form['try'] = ['#type' => 'link', '#title' => 'Tester le formulaire fictif', '#attributes' => ['class' => ['tcl-season-try']], '#url' => \Drupal\Core\Url::fromRoute('tcl_bureau.registration_recipe'), '#access' => SeasonCatalogue::recipeEnabled()];
    $form['revision'] = ['#type' => 'hidden', '#default_value' => $state['revision']];
    $form['season'] = ['#type' => 'textfield', '#title' => 'Saison', '#required' => TRUE, '#maxlength' => 9, '#default_value' => $draft['season']];
    $form['formulas'] = ['#type' => 'table', '#tree' => TRUE, '#header' => ['Identifiant stable', 'Libellé', 'Profil', 'Prix en euros']];
    $form['formulas']['#prefix'] = '<div class="tcl-table">';
    $form['formulas']['#suffix'] = '</div>';
    // Empty rows allow additions without an AJAX dependency; blank rows are ignored.
    foreach (array_pad($draft['formulas'], min(30, count($draft['formulas']) + 3), []) as $n => $row) {
      $form['formulas'][$n]['id'] = ['#type' => 'textfield', '#title' => 'Identifiant de la formule ' . ($n + 1), '#title_display' => 'invisible', '#maxlength' => 60, '#default_value' => $row['id'] ?? ''];
      $form['formulas'][$n]['label'] = ['#type' => 'textfield', '#title' => 'Libellé de la formule ' . ($n + 1), '#title_display' => 'invisible', '#maxlength' => 120, '#default_value' => $row['label'] ?? ''];
      $form['formulas'][$n]['profile'] = ['#type' => 'select', '#title' => 'Profil de la formule ' . ($n + 1), '#title_display' => 'invisible', '#options' => ['adult' => 'Adulte', 'minor' => 'Mineur'], '#default_value' => $row['profile'] ?? 'adult'];
      $form['formulas'][$n]['price'] = ['#type' => 'textfield', '#title' => 'Prix de la formule ' . ($n + 1), '#title_display' => 'invisible', '#size' => 10, '#maxlength' => 9, '#default_value' => isset($row['amountCents']) ? number_format($row['amountCents'] / 100, 2, ',', '') : ''];
    }
    $form['slots'] = ['#type' => 'textarea', '#title' => 'Plages proposées — une par ligne', '#default_value' => implode("\n", $draft['slots']), '#description' => 'Laissez vide tant que les créneaux ne sont pas définis. Les disponibilités ne constituent pas une réservation.'];
    $form['notice'] = ['#type' => 'textarea', '#title' => 'Information de campagne — brouillon de recette', '#default_value' => $draft['notice'], '#description' => 'Les textes définitifs seront complétés ensuite ; ce texte ne permet pas d’ouvrir une collecte réelle.'];
    $form['confirm'] = ['#type' => 'checkbox', '#title' => 'J’ai relu ce catalogue et je souhaite le publier pour la recette fictive.'];
    $form['actions']['#type'] = 'actions';
    $form['actions']['save'] = ['#type' => 'submit', '#value' => 'Enregistrer le brouillon', '#name' => 'save'];
    $form['actions']['publish'] = ['#type' => 'submit', '#value' => 'Publier pour la recette', '#name' => 'publish', '#access' => SeasonCatalogue::recipeEnabled()];
    return $form;
  }
  private function input(FormStateInterface $state): array {
    $rows = [];
    foreach ($state->getValue('formulas', []) as $row) {
      if (trim($row['id']) === '' && trim($row['label']) === '' && trim($row['price']) === '') { continue; }
      $price = str_replace(',', '.', trim($row['price']));
      $parts = explode('.', $price);
      $amount = preg_match('/^\d{1,5}(?:\.\d{1,2})?$/D', $price) ? ((int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0')) : -1;
      $rows[] = ['id' => trim($row['id']), 'label' => trim($row['label']), 'profile' => $row['profile'], 'amountCents' => $amount];
    }
    return ['season' => trim((string) $state->getValue('season')), 'formulas' => $rows, 'slots' => array_values(array_filter(array_map('trim', explode("\n", (string) $state->getValue('slots'))), static fn($s) => $s !== '')), 'notice' => trim((string) $state->getValue('notice'))];
  }
  public function validateForm(array &$form, FormStateInterface $state): void {
    foreach (SeasonCatalogue::validate($this->input($state)) as $field => $error) { $state->setErrorByName($field, $error); }
    if (($state->getTriggeringElement()['#name'] ?? '') === 'publish' && !$state->getValue('confirm')) { $state->setErrorByName('confirm', 'Confirmez la publication pour la recette.'); }
  }
  public function submitForm(array &$form, FormStateInterface $state): void {
    try { $this->catalogue->save($this->currentUser(), $this->input($state), (int) $state->getValue('revision'), ($state->getTriggeringElement()['#name'] ?? '') === 'publish'); }
    catch (ConflictHttpException $error) { $this->messenger()->addError($error->getMessage()); $state->setRebuild(); return; }
    $this->messenger()->addStatus('Catalogue enregistré.');
    $state->setRedirect('tcl_bureau.seasons');
  }
}
