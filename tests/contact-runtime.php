<?php
declare(strict_types=1);

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\tcl_site\Controller\ClubPageController;
use Drupal\tcl_site\PublicPageEditor;
use Symfony\Component\HttpFoundation\Request;

$project = dirname(__DIR__);
$loader = require $project . '/drupal/web/autoload.php';
chdir($project . '/drupal/web');
$request = Request::create('http://127.0.0.1:4183/');
$kernel = DrupalKernel::createFromRequest($request,$loader,'prod'); $kernel->boot(); $kernel->preHandle($request);
$options = Drupal::database()->getConnectionOptions();
if ($options['driver'] !== 'sqlite' || realpath($options['database']) !== realpath($project.'/.local/drupal-runtime/site.sqlite')
  || Settings::get('tcl_contact_mail_mode') !== 'capture') { throw new RuntimeException('Local capture scope required.'); }
$captured = array_values(array_filter(Drupal::state()->get('system.test_mail_collector', []), static fn($mail) => $mail['id'] === 'tcl_site_contact'));
if (count($captured) !== 5) { throw new RuntimeException('Invalid contact delivery count or duplicate.'); }
foreach ($captured as $mail) {
  if ($mail['to'] !== 'tclongages@gmail.com' || $mail['from'] !== 'support@tclongages.fr'
    || $mail['subject'] !== '[TC Longages] Message au club' || isset($mail['headers']['Bcc'])) {
    throw new RuntimeException('Unexpected destination or headers.');
  }
}
if ($captured[0]['reply-to'] !== 'testeur@example.invalid' || $captured[1]['reply-to'] !== NULL) { throw new RuntimeException('Reply-To must be email only.'); }
$captureCountBeforeFailure = count(Drupal::state()->get('system.test_mail_collector',[]));
$manager = Drupal::service('plugin.manager.mail');
$container = Drupal::getContainer();
$container->set('plugin.manager.mail',new class($manager) {
  public function __construct(private $manager) {}
  public function getInstance($options) { return $this->manager->getInstance($options); }
  public function mail(...$args) { return ['result'=>FALSE]; }
});
try {
  $state = new Drupal\Core\Form\FormState(); $form = [];
  $state->setValues(['name'=>'Essai fictif','reply'=>'testeur@example.invalid','message'=>'Texte à conserver après refus',
    'contact_token'=>Drupal::csrfToken()->get('tcl_club_contact'),'submission_nonce'=>bin2hex(random_bytes(16))]);
  (new Drupal\tcl_site\Form\ClubContactForm())->submitForm($form,$state);
  if (!$state->isRebuilding() || $state->getValue('message') !== 'Texte à conserver après refus'
    || count(Drupal::state()->get('system.test_mail_collector',[])) !== $captureCountBeforeFailure) { throw new RuntimeException('Mail failure must preserve input.'); }
} finally { $container->set('plugin.manager.mail',$manager); }
// Logged-in users without native entity access must not see editing commands.
$account = new Drupal\Core\Session\UserSession(['uid'=>999999,'roles'=>['authenticated']]);
$switcher = Drupal::service('account_switcher'); $switcher->switchTo($account);
try {
  foreach (PublicPageEditor::PAGES as $page) {
    if (str_contains((new ClubPageController())->page($page)->getContent(),'tcl-edit-bar')) { throw new RuntimeException('Read-only account sees edit controls.'); }
  }
} finally { $switcher->switchBack(); }
echo json_encode(['passed'=>TRUE,'capturedContacts'=>count($captured),'duplicateSuppressed'=>TRUE,
  'fixedRecipient'=>TRUE,'phoneHasNoReplyTo'=>TRUE,'readOnlyAccountHidden'=>TRUE,'failedMailPreservesText'=>TRUE,'outgoingMail'=>FALSE]),"\n";
