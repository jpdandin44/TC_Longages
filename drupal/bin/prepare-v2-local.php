<?php

declare(strict_types=1);

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\user\Entity\User;

require __DIR__ . '/local-bootstrap.php';

\Drupal::service('module_installer')->install(['language', 'locale', 'tcl_bureau']);
// Only this fresh local fixture site: reconcile a module developed in-place.
\Drupal::moduleHandler()->loadInclude('tcl_bureau', 'install');
foreach (tcl_bureau_schema() as $name => $definition) {
  if (!\Drupal::database()->schema()->tableExists($name)) {
    \Drupal::database()->schema()->createTable($name, $definition);
  }
}
\Drupal::configFactory()->getEditable('tcl_bureau.settings')->set('teams_enabled', FALSE)->set('season', '2026-2027')->save();
\Drupal\user\Entity\Role::load('tcl_bureau')->grantPermission('manage tcl registrations')->save();
if (!ConfigurableLanguage::load('fr')) {
  ConfigurableLanguage::createFromLangcode('fr')->save();
}
\Drupal::configFactory()->getEditable('system.site')->set('name', 'TC Longages — V2 locale')->set('default_langcode', 'fr')->save();
\Drupal::configFactory()->getEditable('user.settings')->set('register', 'admin_only')->save();
\Drupal::state()->set('system.maintenance_mode', FALSE);
// A small local translation set supports the native login/password controls.
$locale = \Drupal::service('locale.storage');
foreach (['Username' => 'Identifiant', 'Password' => 'Mot de passe', 'Confirm password' => 'Confirmer le mot de passe', 'Log in' => 'Se connecter', 'Log out' => 'Se déconnecter', 'Access denied' => 'Accès refusé', 'Save' => 'Enregistrer'] as $source => $translation) {
  $string = $locale->findString(['source' => $source, 'context' => '']);
  $string ??= $locale->createString(['source' => $source, 'context' => ''])->save();
  // Only these explicit local labels: upsert also repairs an empty target row.
  \Drupal::database()->merge('locales_target')
    ->keys(['lid' => $string->getId(), 'language' => 'fr'])
    ->fields(['translation' => $translation, 'customized' => 1])->execute();
}
$credentialsPath = dirname($tclRoot) . '/.local/v2-local-accounts.json';
$credentials = is_file($credentialsPath) ? json_decode(file_get_contents($credentialsPath), TRUE, 512, JSON_THROW_ON_ERROR) : [];
$fixtures = ['bureau' => ['bureau-recette', 'tcl_bureau'], 'capitaineA' => ['capitaine-a-recette', 'tcl_capitaine'], 'capitaineB' => ['capitaine-b-recette', 'tcl_capitaine']];
$users = [];
foreach ($fixtures as $key => [$name, $role]) {
  $ids = \Drupal::entityQuery('user')->accessCheck(FALSE)->condition('name', $name)->execute();
  if ($ids) {
    $user = User::load(reset($ids));
    if (!isset($credentials[$key]) || (int) $credentials[$key]['uid'] !== (int) $user->id()) {
      throw new RuntimeException('Collision avec un compte existant : aucun mot de passe remplacé.');
    }
  }
  else {
    $password = bin2hex(random_bytes(20));
    $user = User::create(['name' => $name, 'mail' => $name . '@example.invalid', 'status' => 1, 'roles' => [$role], 'langcode' => 'fr', 'preferred_langcode' => 'fr', 'preferred_admin_langcode' => 'fr']);
    $user->setPassword($password);
    $user->save();
    $credentials[$key] = ['username' => $name, 'password' => $password, 'uid' => (int) $user->id(), 'localOnly' => TRUE];
  }
  $users[$key] = $user;
}
$admin = User::load(1);
$admin->set('preferred_langcode', 'fr')->set('preferred_admin_langcode', 'fr')->save();
$repository = \Drupal::service('tcl_bureau.repository');
if (!$repository->allowed($admin, 'manage tcl teams')) {
  throw new RuntimeException('Le compte administrateur local doit disposer des permissions natives avant la recette.');
}
$teamIds = [];
foreach (['A' => 'capitaineA', 'B' => 'capitaineB'] as $letter => $key) {
  $name = 'Équipe ' . $letter . ' — recette';
  $exists = \Drupal::database()->select('tcl_bureau_team', 't')->fields('t', ['id'])->condition('name', $name)->execute()->fetchField();
  if (!$exists) {
    $exists = $repository->save($admin, NULL, ['name' => $name, 'category' => 'Exemple fictif', 'notes' => 'Notes privées de l’équipe ' . $letter . ' — données fictives.', 'captains' => [$users[$key]->id()]]);
  }
  $teamIds[$letter] = (int) $exists;
}
file_put_contents(dirname($tclRoot) . '/.local/v2-local-team-ids.json', json_encode($teamIds) . "\n");
file_put_contents($credentialsPath, json_encode($credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
\Drupal::service('router.builder')->rebuild();
\Drupal::service('cache_tags.invalidator')->invalidateTags(['config:system.site', 'locale']);
echo json_encode(['status' => 'prepared', 'origin' => 'http://127.0.0.1:4182', 'database' => 'private local SQLite', 'defaultLanguage' => 'fr', 'fixtureAccounts' => count($users), 'fictitiousTeams' => 2, 'mailBackend' => \Drupal::config('system.mail')->get('interface.default'), 'externalDeployment' => FALSE], JSON_UNESCAPED_SLASHES) . "\n";
