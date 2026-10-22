<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\tcl_bureau\RegistrationRepository;
use Drupal\tcl_bureau\SeasonCatalogue;
use Drupal\tcl_bureau\RecipeMail;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** Public-facing recipe on the protected preproduction, never real collection. */
final class PublicRegistrationForm extends FormBase {
  public function __construct(protected RegistrationRepository $repository, protected SeasonCatalogue $catalogue) {}
  public static function create(ContainerInterface $container) { return new static($container->get('tcl_bureau.registrations'), $container->get('tcl_bureau.seasons')); }
  public function getFormId(): string { return 'tcl_registration_recipe'; }
  public function access() { return AccessResult::allowedIf(SeasonCatalogue::recipeEnabled())->setCacheMaxAge(0); }
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if (!SeasonCatalogue::recipeEnabled()) { throw new AccessDeniedHttpException(); }
    $form['#cache']['max-age'] = 0;
    $form['#attributes']['class'][] = 'tcl-bureau';
    $form['#token'] = 'tcl_registration_recipe';
    // Drupal only adds its token field for authenticated users. This anonymous
    // two-step form starts a session and supplies the same Form API CSRF token.
    \Drupal::request()->getSession()->start();
    \Drupal::request()->getSession()->set('tcl_registration_recipe_started', TRUE);
    $form['form_token'] = ['#type' => 'hidden', '#default_value' => \Drupal::service('csrf_token')->get('tcl_registration_recipe'), '#parents' => ['form_token']];
    $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['warning'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'RECETTE UNIQUEMENT — saisissez des informations fictives. Aucune adhésion, licence, réservation, facturation ou notification réelle. Les autorisations et textes définitifs seront complétés ensuite.'];
    if (RecipeMail::settings()) { $form['warning']['#value'] = 'RECETTE — données personnelles fictives, courriel @example.invalid ou adresse de test autorisée. Le club reçoit un courriel réel de notification ; aucun accusé au demandeur. Aucune adhésion, licence, réservation ou facturation réelle. Textes et créneaux à compléter.'; }
    if ($form_state->get('done')) {
      $form['done'] = ['#plain_text' => 'La demande fictive a été reçue. Le Bureau peut la retrouver dans son suivi privé.'];
      if ($form_state->get('mail_status') === 'accepted') { $form['mail'] = ['#plain_text' => 'Notification au club acceptée par le serveur d’envoi. Sa réception reste à vérifier.']; }
      elseif (in_array($form_state->get('mail_status'), ['failed', 'pending'], TRUE)) { $form['mail'] = ['#plain_text' => 'Demande conservée, mais notification non confirmée. Contactez le Bureau sans redéposer la demande.']; }
      return $form;
    }
    $catalogue = $this->catalogue->published();
    if (!$catalogue) {
      $form['closed'] = ['#plain_text' => 'Le catalogue de recette n’est pas encore publié. Aucun dépôt possible.'];
      return $form;
    }
    if (!$form_state->get('submission')) { $form_state->set('submission', bin2hex(random_bytes(16))); }
    if (!$form_state->get('catalogue')) { $form_state->set('catalogue', $catalogue); }
    $catalogue = $form_state->get('catalogue');
    $form['season'] = ['#type' => 'container', 'text' => ['#plain_text' => 'Saison ' . $catalogue['season'] . ' — catalogue de recette']];
    $form['notice'] = ['#type' => 'container', 'text' => ['#plain_text' => $catalogue['notice'] ?: 'Information de collecte : texte à compléter avant toute utilisation réelle.']];
    if ($form_state->get('review')) {
      $values = $form_state->get('review_values');
      $rows = [];
      foreach (['profile' => 'Profil', 'first_name' => 'Prénom fictif', 'last_name' => 'Nom fictif', 'birth_date' => 'Naissance', 'email' => 'Courriel fictif', 'phone' => 'Téléphone fictif', 'address' => 'Adresse fictive', 'postal_code' => 'Code postal', 'city' => 'Ville fictive', 'guardian_name' => 'Responsable fictif', 'guardian_email' => 'Courriel du responsable', 'guardian_phone' => 'Téléphone du responsable', 'emergency_name' => 'Personne fictive à prévenir', 'emergency_phone' => 'Téléphone d’urgence', 'last_licence_year' => 'Dernière année de licence', 'practice_years' => 'Années de pratique', 'previous_licence' => 'Déjà licencié', 'training' => 'Cours demandés'] as $key => $label) {
        $value = (string) ($values[$key] ?? '');
        if ($value === '' || ($values['profile'] === 'adult' && str_starts_with($key, 'guardian_'))) { continue; }
        if (in_array($key, ['profile', 'training', 'previous_licence'], TRUE)) { $value = ['adult' => 'Adulte', 'minor' => 'Mineur', 'yes' => 'Oui', 'no' => 'Non', 'unknown' => 'À préciser'][$value] ?? $value; }
        $rows[] = [$label, ['data' => ['#plain_text' => $value]]];
      }
      foreach ($catalogue['formulas'] as $row) {
        if ($row['id'] === $values['formula']) { $rows[] = ['Formule souhaitée', ['data' => ['#plain_text' => $row['label'] . ' — ' . number_format($row['amountCents'] / 100, 2, ',', ' ') . ' €']]]; }
      }
      $rows[] = ['Disponibilités', ['data' => ['#plain_text' => implode(', ', $values['availability'] ?? []) ?: 'À compléter']]];
      $form['summary'] = ['#type' => 'table', '#header' => ['Votre demande fictive', 'Valeur'], '#rows' => $rows];
      $form['fictional_confirmation'] = ['#type' => 'checkbox', '#title' => 'Je confirme que les informations personnelles sont fictives, hors adresse de test autorisée, et souhaite déposer ce test.', '#required' => TRUE];
      $form['actions']['#type'] = 'actions';
      $form['actions']['send'] = ['#type' => 'submit', '#value' => 'Déposer la demande fictive', '#name' => 'send'];
      $form['actions']['back'] = ['#type' => 'submit', '#value' => 'Corriger', '#name' => 'back', '#limit_validation_errors' => [], '#submit' => ['::back']];
      return $form;
    }
    $values = $form_state->get('review_values') ?? [];
    $form['profile'] = ['#type' => 'select', '#title' => 'Profil', '#options' => ['adult' => 'Adulte', 'minor' => 'Mineur'], '#default_value' => $values['profile'] ?? 'adult'];
    foreach (['first_name' => 'Prénom fictif', 'last_name' => 'Nom fictif (commence par Recette)', 'birth_date' => 'Date de naissance fictive', 'email' => 'Courriel de test (@example.invalid ou adresse autorisée)', 'phone' => 'Téléphone fictif (0600000000 ou vide)', 'address' => 'Adresse fictive', 'postal_code' => 'Code postal fictif', 'city' => 'Ville fictive', 'emergency_name' => 'Personne fictive à prévenir', 'emergency_phone' => 'Téléphone d’urgence fictif (0600000000 ou vide)', 'last_licence_year' => 'Dernière année de licence (facultatif)', 'practice_years' => 'Années de pratique (facultatif)'] as $key => $label) {
      $form[$key] = ['#type' => $key === 'birth_date' ? 'date' : ($key === 'email' ? 'email' : 'textfield'), '#title' => $label, '#maxlength' => 120, '#required' => in_array($key, ['first_name', 'last_name', 'birth_date', 'email']), '#default_value' => $values[$key] ?? ''];
    }
    foreach (['guardian_name' => 'Nom du responsable fictif (commence par Recette)', 'guardian_email' => 'Courriel du responsable (@example.invalid ou adresse autorisée)', 'guardian_phone' => 'Téléphone du responsable fictif (0600000000 ou vide)'] as $key => $label) {
      $form[$key] = ['#type' => $key === 'guardian_email' ? 'email' : 'textfield', '#title' => $label, '#maxlength' => 120, '#default_value' => $values[$key] ?? '', '#states' => ['visible' => [':input[name="profile"]' => ['value' => 'minor']]]];
    }
    $options = [];
    foreach ($catalogue['formulas'] as $row) { $options[$row['id']] = ($row['profile'] === 'adult' ? 'Adulte — ' : 'Mineur — ') . $row['label'] . ' — ' . number_format($row['amountCents'] / 100, 2, ',', ' ') . ' €'; }
    $form['formula'] = ['#type' => 'select', '#title' => 'Formule souhaitée', '#options' => $options, '#empty_option' => 'Choisir une formule', '#required' => TRUE, '#default_value' => $values['formula'] ?? ''];
    foreach (['training' => 'Souhaitez-vous participer aux cours ?', 'previous_licence' => 'Déjà licencié ?'] as $key => $label) {
      $form[$key] = ['#type' => 'select', '#title' => $label, '#options' => ['unknown' => 'À préciser', 'yes' => 'Oui', 'no' => 'Non'], '#default_value' => $values[$key] ?? 'unknown'];
    }
    if ($catalogue['slots']) {
      $form['availability'] = ['#type' => 'checkboxes', '#title' => 'Disponibilités proposées — sans réservation', '#options' => array_combine($catalogue['slots'], $catalogue['slots']), '#default_value' => $values['availability'] ?? [], '#states' => ['visible' => [':input[name="training"]' => ['value' => 'yes']]]];
    }
    else { $form['slots_pending'] = ['#type' => 'container', 'text' => ['#plain_text' => 'Créneaux à compléter : vous pouvez tester la demande sans choisir de plage.']]; }
    $form['authorizations_pending'] = ['#type' => 'container', 'text' => ['#plain_text' => 'Autorisations urgence et images : textes et modalités à compléter. Aucun consentement ni signature réelle recueillis dans cette recette.']];
    $form['actions']['#type'] = 'actions';
    $form['actions']['review'] = ['#type' => 'submit', '#value' => 'Vérifier ma demande fictive', '#name' => 'review'];
    return $form;
  }
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if ($form_state->get('review')) { return; }
    $values = $form_state->getValues();
    $values['season'] = $form_state->get('catalogue')['season'];
    $values['status'] = 'received';
    foreach ($this->repository->validate($values) as $field => $error) { $form_state->setErrorByName($field, $error); }
    $valid = FALSE;
    foreach ($form_state->get('catalogue')['formulas'] as $row) { if ($row['id'] === ($values['formula'] ?? '') && $row['profile'] === $values['profile']) { $valid = TRUE; } }
    if (!$valid) { $form_state->setErrorByName('formula', 'Choisissez une formule correspondant au profil.'); }
    if (!str_starts_with((string) $values['last_name'], 'Recette') || !RecipeMail::inputAllowed((string) $values['email'])) { $form_state->setErrorByName('last_name', 'Pour ce test : nom commençant par Recette et courriel @example.invalid ou adresse de recette autorisée.'); }
    $values['availability'] = array_values(array_filter($values['availability'] ?? [], static fn($v) => $v !== 0 && $v !== '0'));
    $form_state->set('review_values', array_intersect_key($values, array_flip(array_merge(RegistrationRepository::FIELDS, ['availability']))));
  }
  public function back(array &$form, FormStateInterface $form_state): void { $form_state->set('review', FALSE)->setRebuild(); }
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if (!$form_state->get('review')) { $form_state->set('review', TRUE)->setRebuild(); return; }
    $flood = \Drupal::service('flood');
    if (!$flood->isAllowed('tcl.registration.recipe', 20, 3600)) { $this->messenger()->addError('Trop de demandes de test. Réessayez plus tard.'); $form_state->setRebuild(); return; }
    try {
      $id = $this->repository->submitRecipe($this->currentUser(), $form_state->get('review_values') + ['fictional_confirmation' => $form_state->getValue('fictional_confirmation')], $form_state->get('submission'), $form_state->get('catalogue')['revision']);
    }
    catch (\InvalidArgumentException | ConflictHttpException $error) {
      $this->messenger()->addError($error->getMessage());
      $form_state->set('review', FALSE)->set('catalogue', NULL)->setRebuild();
      return;
    }
    $form_state->set('mail_status', RecipeMail::registration($id));
    $flood->register('tcl.registration.recipe', 3600);
    $form_state->set('done', TRUE)->setRebuild();
  }
}
