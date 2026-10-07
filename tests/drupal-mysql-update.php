<?php

declare(strict_types=1);

use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

// A separate fixture; deliberately does not relax bin/local-bootstrap.php.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$project = dirname(__DIR__);
$fixture = json_decode(file_get_contents($project . '/.local/mysql-recette/access.json'), TRUE, 512, JSON_THROW_ON_ERROR);
if ($fixture['scope'] !== 'local-disposable-mysql' || $fixture['host'] !== '127.0.0.1' || $fixture['port'] !== 33080 || !preg_match('/^tcl_recette_[a-f0-9]{8}$/D', $fixture['database'])) {
  throw new RuntimeException('Recette refusée hors de la base locale jetable.');
}
$loader = require $project . '/drupal/web/autoload.php';
chdir($project . '/drupal/web');
$request = Request::create('http://127.0.0.1:4183/', 'GET', [], [], [], ['SCRIPT_NAME' => '/index.php']);
$kernel = DrupalKernel::createFromRequest($request, $loader, 'prod');
$options = Database::getConnectionInfo()['default'] ?? [];
if ($kernel->getSitePath() !== 'sites/tcl-mysql-recette' || ($options['driver'] ?? '') !== 'mysql' || ($options['host'] ?? '') !== '127.0.0.1' || (int) ($options['port'] ?? 0) !== 33080 || ($options['database'] ?? '') !== $fixture['database']) {
  throw new RuntimeException('Connexion différente de la fixture autorisée.');
}
$kernel->boot();
$kernel->preHandle($request);
$db = Database::getConnection();
$checks = [];
function checked(string $name, bool $value): void {
  global $checks;
  if (!$value) { throw new RuntimeException('Échec : ' . $name); }
  $checks[] = $name;
}
function denied(callable $action, string $name, string $class): void {
  try { $action(); } catch (Throwable $error) { checked($name, $error instanceof $class); return; }
  checked($name, FALSE);
}
function retained(): array {
  return [
    'users' => Drupal::database()->select('users_field_data', 'u')->fields('u')->orderBy('uid')->execute()->fetchAll(PDO::FETCH_ASSOC),
    'registrations' => Drupal::database()->select('tcl_bureau_registration', 'r')->fields('r')->orderBy('id')->execute()->fetchAll(PDO::FETCH_ASSOC),
  ];
}
$action = $argv[1] ?? '';
if ($action === 'seed') {
  checked('Existing Drupal V1 fixture has no Bureau', !Drupal::moduleHandler()->moduleExists('tcl_bureau'));
  Drupal::service('module_installer')->install(['tcl_site']);
  $account = User::create(['name' => 'existing-v1-fictional', 'mail' => 'v1@example.invalid', 'status' => 1]);
  $account->setPassword($fixture['accountPassword'])->save();
  file_put_contents($project . '/.local/mysql-recette/v1-users.json', json_encode($db->select('users_field_data', 'u')->fields('u')->orderBy('uid')->execute()->fetchAll(PDO::FETCH_ASSOC), JSON_THROW_ON_ERROR));
  Drupal::service('module_installer')->install(['tcl_bureau']);
  checked('V1 accounts preserved by additive module install', json_decode(file_get_contents($project . '/.local/mysql-recette/v1-users.json'), TRUE) === $db->select('users_field_data', 'u')->fields('u')->orderBy('uid')->execute()->fetchAll(PDO::FETCH_ASSOC));
  checked('Fresh schema includes communication', $db->schema()->tableExists('tcl_bureau_post'));
  checked('Bureau role installed', Role::load('tcl_bureau')->hasPermission('manage tcl registrations'));
  checked('Captain cannot edit registrations', !Role::load('tcl_capitaine')->hasPermission('manage tcl registrations'));
  checked('Team scope remains disabled', Drupal::config('tcl_bureau.settings')->get('teams_enabled') === FALSE);
  $bureau = User::create(['name' => 'mysql-bureau-fictional', 'mail' => 'bureau@example.invalid', 'status' => 1]);
  $bureau->addRole('tcl_bureau')->setPassword($fixture['accountPassword'])->save();
  $registrations = Drupal::service('tcl_bureau.registrations');
  $values = ['profile' => 'adult', 'first_name' => 'Élodie', 'last_name' => 'Recette fictive', 'birth_date' => '1990-02-03', 'season' => '2026-2027', 'email' => 'fiction@example.invalid', 'phone' => '', 'status' => 'received', 'training' => 'unknown', 'previous_licence' => 'unknown', 'notes' => 'Donnée fictive — conservée'];
  $key = bin2hex(random_bytes(16));
  $id = $registrations->save($bureau, NULL, $values, $key, 0);
  checked('Accented registration persisted in MySQL', $registrations->get($bureau, $id)['values']['first_name'] === 'Élodie');
  checked('Double submission returns same record', $registrations->save($bureau, NULL, $values, $key, 0) === $id);
  $values['notes'] = 'Révision fictive';
  $registrations->save($bureau, $id, $values, $key, 1);
  denied(fn() => $registrations->save($bureau, $id, $values, $key, 1), 'Stale registration revision refused', ConflictHttpException::class);
  // Reconstruct the earlier Bureau-only schema ONLY in this fresh disposable DB.
  $db->schema()->dropTable('tcl_bureau_post');
  Role::load('tcl_bureau')->revokePermission('manage tcl communication')->save();
  Drupal::service('update.update_hook_registry')->setInstalledVersion('tcl_bureau', 11000);
  file_put_contents($project . '/.local/mysql-recette/before-update.json', json_encode(retained(), JSON_THROW_ON_ERROR));
  checked('Earlier module revision prepared', !$db->schema()->tableExists('tcl_bureau_post'));
}
elseif ($action === 'verify') {
  checked('Native updatedb applied update 11001', Drupal::service('update.update_hook_registry')->getInstalledVersion('tcl_bureau') === 11001);
  checked('Communication table created by update', $db->schema()->tableExists('tcl_bureau_post'));
  checked('Communication permission added', Role::load('tcl_bureau')->hasPermission('manage tcl communication'));
  checked('Accounts and registrations unchanged after update', json_decode(file_get_contents($project . '/.local/mysql-recette/before-update.json'), TRUE) === retained());
  $bureau = user_load_by_name('mysql-bureau-fictional');
  $posts = Drupal::service('tcl_bureau.communication');
  $values = ['title' => 'Recette MySQL — été', 'category' => 'club', 'body' => 'Texte fictif', 'link' => '', 'tenup_visible' => 'no', 'image_alt' => 'Affiche fictive'];
  $image = imagecreatetruecolor(16, 16); ob_start(); imagejpeg($image); $binary = ob_get_clean(); imagedestroy($image);
  $key = bin2hex(random_bytes(16));
  $id = $posts->save($bureau, NULL, $values, $key, 0, $binary);
  checked('JPEG bytes conserved in MySQL BLOB', $posts->get($bureau, $id)['image_data'] === $binary);
  checked('News double submission idempotent', $posts->save($bureau, NULL, $values, $key, 0, $binary) === $id);
  checked('Draft absent from public selection', $posts->published($id) === []);
  denied(fn() => $posts->transition($bureau, $id, 1, 'publish'), 'Publishing unvalidated draft refused', ConflictHttpException::class);
  $posts->transition($bureau, $id, 1, 'validate');
  denied(fn() => $posts->transition($bureau, $id, 1, 'publish'), 'Stale publication revision refused', ConflictHttpException::class);
  $posts->transition($bureau, $id, 2, 'publish');
  checked('Approved exact revision visible', (int) $posts->published($id)['approved_revision'] === 3);
  $values['body'] = 'Correction fictive';
  $posts->save($bureau, $id, $values, $key, 3);
  checked('Editing immediately removes public news', $posts->published($id) === []);
  $posts->transition($bureau, $id, 4, 'archive');
  $posts->transition($bureau, $id, 5, 'restore');
  checked('Restored archive returns unapproved draft', $posts->get($bureau, $id)['status'] === 'draft' && (int) $posts->get($bureau, $id)['approved_revision'] === 0);
  $captain = User::create(['name' => 'mysql-captain-fictional', 'mail' => 'captain@example.invalid', 'status' => 1]);
  $captain->addRole('tcl_capitaine')->setPassword($fixture['accountPassword'])->save();
  denied(fn() => $posts->get($captain, $id), 'Captain denied private news', AccessDeniedHttpException::class);
  denied(fn() => Drupal::service('tcl_bureau.registrations')->list($captain), 'Captain denied private registrations', AccessDeniedHttpException::class);
  require_once $project . '/drupal/web/modules/custom/tcl_bureau/tcl_bureau.install';
  $before = retained(); $post = $posts->get($bureau, $id);
  tcl_bureau_update_11001();
  checked('Repeated update preserves accounts and registrations', retained() === $before);
  checked('Repeated update preserves draft and JPEG', $posts->get($bureau, $id) === $post);
}
else { throw new InvalidArgumentException('Action inconnue.'); }
echo json_encode(['status' => 'passed', 'checks' => $checks, 'drupal' => Drupal::VERSION, 'php' => PHP_VERSION, 'driver' => 'mysql', 'server' => $db->query('SELECT VERSION()')->fetchField()], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
