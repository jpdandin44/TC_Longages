<?php
declare(strict_types=1);

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\Core\Form\FormState;
use Drupal\tcl_site\PublicPageEditor;
use Drupal\tcl_site\Controller\ClubPageController;
use Drupal\tcl_site\Form\ClubContactForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$phase = 'scope';
try {
  [$script,$operation,$root,$candidate] = $argv;
  $private = '/home2/daje5127/tcl-preproduction/private';
  $active = '/home2/daje5127/tcl-preproduction/drupal';
  if (!in_array($operation,['before','rebuild','verify','rehearse'],TRUE)
    || realpath($root) !== $root || realpath($candidate) !== $candidate
    || !preg_match('~^'.preg_quote($private,'~').'/site-fix-[a-f0-9]{12}-[a-f0-9]{12}$~D',$candidate)
    || !($root === $active || str_starts_with($root,$private.'/restorations/restore-') && str_ends_with($root,'/drupal'))) {
    throw new RuntimeException('Target scope');
  }
  $pins = json_decode(file_get_contents($candidate.'/candidate-pins.json'),TRUE,32,JSON_THROW_ON_ERROR);
  $manifest = json_decode(file_get_contents($candidate.'/manifest.json'),TRUE,32,JSON_THROW_ON_ERROR);
  if ($manifest['sourceSha'] !== $pins['sourceSha'] || $manifest['targetHost'] !== 'preprod.tclongages.fr'
    || $manifest['productionAllowed'] !== FALSE
    || basename($candidate) !== 'site-fix-'.substr($pins['sourceSha'],0,12).'-'.substr($pins['artifactSha256'],0,12)) { throw new RuntimeException('Candidate scope'); }
  $phase = 'bootstrap';
  $loader = require $root.'/web/autoload.php'; chdir($root.'/web');
  $request = Request::create('https://preprod.tclongages.fr/contact.html');
  $kernel = DrupalKernel::createFromRequest($request,$loader,'prod'); $kernel->boot(); $kernel->preHandle($request);
  $options = Drupal::database()->getConnectionOptions();
  $expectedPrefix = $root === $active ? '' : 'sr_'.substr($pins['artifactSha256'],0,12).'_';
  if (getenv('TCL_ENVIRONMENT') !== 'preproduction' || getenv('TCL_PUBLIC_INDEXING') === '1'
    || $options['database'] !== 'daje5127_tclpreprod' || $options['username'] !== 'daje5127_tcl'
    || ($options['prefix'] ?? '') !== $expectedPrefix) { throw new RuntimeException('Database scope'); }
  $phase = $operation;
  $editorial = [];
  foreach (PublicPageEditor::PAGES as $page) {
    $node = PublicPageEditor::node($page);
    if (!$node || !$node->isPublished()) { throw new RuntimeException('Editorial content'); }
    $editorial[$page] = [$node->id(),$node->getRevisionId(),$node->label(),$node->get(PublicPageEditor::FIELD)->value,$node->get(PublicPageEditor::TEXTS)->value];
  }
  $result = ['success'=>TRUE,'operation'=>$operation,'observedAt'=>gmdate('c'),
    'sourceSha'=>$pins['sourceSha'],'artifactSha256'=>$pins['artifactSha256'],
    'maintenanceEnabled'=>(bool) Drupal::state()->get('system.maintenance_mode'),
    'pages'=>count($editorial),'editorialDigest'=>hash('sha256',json_encode($editorial,JSON_THROW_ON_ERROR)),
    'supportMode'=>Settings::get('tcl_support_mail_mode'),'supportQualified'=>Settings::get('tcl_support_transport_qualified'),
    'defaultMail'=>Drupal::config('system.mail')->get('interface.default'),
    'supportMail'=>Drupal::config('system.mail')->get('interface.tcl_support_report'),
    'contactEnabled'=>Settings::get('tcl_contact_enabled',FALSE),'messagesSent'=>0,'productionWritten'=>FALSE];
  if ($result['supportMode'] !== 'transport' || $result['supportQualified'] !== TRUE
    || $result['defaultMail'] !== 'tcl_null_mail' || $result['supportMail'] !== 'php_mail') { throw new RuntimeException('Existing support transport'); }
  if ($operation === 'rebuild') { drupal_flush_all_caches(); }
  if (in_array($operation,['verify','rehearse'],TRUE)) {
    foreach ($manifest['files'] as $name=>$entry) {
      if (!hash_equals($entry['sha256'],hash_file('sha256',$root.'/'.$name))) { throw new RuntimeException('Installed source'); }
    }
    $account = Drupal\user\Entity\User::load(1); $switch = Drupal::service('account_switcher');
    $switch->switchTo($account);
    try {
      foreach (PublicPageEditor::PAGES as $page) {
        $body = (new ClubPageController())->page($page)->getContent();
        if (!str_contains($body,'>Modifier cette page</a>') || !str_contains($body,'Pages du club</a>')) { throw new RuntimeException('Native admin controls'); }
      }
    } finally { $switch->switchBack(); }
    $switch->switchTo(new Drupal\Core\Session\AnonymousUserSession());
    try {
      foreach (PublicPageEditor::PAGES as $page) {
        $body = (new ClubPageController())->page($page)->getContent();
        if (str_contains($body,'tcl-edit-bar')) { throw new RuntimeException('Anonymous editing'); }
        if ($page === 'calendrier' && !str_contains($body,'mode=MONTH')) { throw new RuntimeException('Monthly view'); }
        if ($page === 'index' && !str_contains($body,'data:image/png;base64,')) { throw new RuntimeException('Provided image'); }
        if ($page === 'contact' && (!str_contains($body,'tcl-club-contact') || str_contains($body,'id="contact-prepare"'))) { throw new RuntimeException('Native contact form'); }
      }
    } finally { $switch->switchBack(); }
    $request->attributes->set('_route','tcl_site.calendrier');
    $response = (new ClubPageController())->page('calendrier');
    $event = new ResponseEvent(Drupal::service('http_kernel'),$request,HttpKernelInterface::MAIN_REQUEST,$response);
    (new Drupal\tcl_support\EventSubscriber\SupportButtonSubscriber())->onResponse($event);
    if (!str_contains($response->getContent(),'>Signaler un problème sur le site</a>')) { throw new RuntimeException('Support button'); }
    $plugin = Drupal::service('plugin.manager.mail')->getInstance(['module'=>'tcl_site','key'=>'contact']);
    $mode = Settings::get('tcl_contact_mail_mode');
    if (!$result['contactEnabled'] || ClubContactForm::RECIPIENT !== 'tclongages@gmail.com'
      || ($operation === 'rehearse' && ($mode !== 'capture' || !$plugin instanceof Drupal\Core\Mail\Plugin\Mail\TestMailCollector))
      || ($operation === 'verify' && ($mode !== 'transport' || !Settings::get('tcl_contact_transport_qualified')
        || !$plugin instanceof Drupal\Core\Mail\Plugin\Mail\PhpMail || $plugin instanceof Drupal\Core\Mail\Plugin\Mail\TestMailCollector))) { throw new RuntimeException('Contact transport'); }
    $result += ['adminEditingControls'=>7,'anonymousEditingControls'=>0,'contactMode'=>$mode,
      'contactPlugin'=>get_class($plugin),'contactRecipient'=>ClubContactForm::RECIPIENT,
      'supportButtonPreserved'=>TRUE,'monthAndPhotoPreserved'=>TRUE,'browserAccountVerified'=>FALSE,'gmailInboxReceptionVerified'=>FALSE];
    if ($operation === 'rehearse') {
      $formObject = new ClubContactForm(); $state = new FormState();
      $values = ['name'=>'Essai fictif de restauration','reply'=>'testeur@example.invalid','message'=>'Essai fictif du contact sur la copie privée uniquement.',
        'website'=>'','information'=>'1','contact_token'=>Drupal::csrfToken()->get('tcl_club_contact'),'submission_nonce'=>bin2hex(random_bytes(16))];
      $state->setValues($values)->setUserInput($values); $form = [];
      $formObject->validateForm($form,$state);
      if ($state->hasAnyErrors()) { throw new RuntimeException('Native captured contact validation'); }
      $count = count(Drupal::state()->get('system.test_mail_collector',[]));
      $formObject->submitForm($form,$state); $formObject->submitForm($form,$state);
      $messages = Drupal::state()->get('system.test_mail_collector',[]);
      if (count($messages) !== $count+1 || end($messages)['to'] !== ClubContactForm::RECIPIENT || $state->isRebuilding()) { throw new RuntimeException('Captured duplicate control'); }
      $result += ['contactCapturedOnRestoration'=>TRUE,'doubleSubmissionSuppressed'=>TRUE];
    }
  }
  echo json_encode($result,JSON_THROW_ON_ERROR),"\n";
} catch (Throwable $error) {
  echo json_encode(['success'=>FALSE,'phase'=>$phase,'errorType'=>get_class($error),
    'errorFile'=>basename($error->getFile()),'errorLine'=>$error->getLine()]),"\n"; exit(1);
}
