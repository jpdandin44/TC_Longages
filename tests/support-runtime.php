<?php

declare(strict_types=1);

use Drupal\Core\DrupalKernel;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Site\Settings;
use Drupal\tcl_support\SupportNotifier;
use Drupal\tcl_support\SupportRepository;
use Symfony\Component\HttpFoundation\Request;

if (PHP_SAPI !== 'cli') { exit(1); }
$project = dirname(__DIR__);
$autoloader = require $project . '/drupal/web/autoload.php';
chdir($project . '/drupal/web');
$kernel = DrupalKernel::createFromRequest(Request::create('http://127.0.0.1:4183/'), $autoloader, 'prod');
$kernel->boot();
$kernel->preHandle(Request::create('http://127.0.0.1:4183/'));
$db = \Drupal::database();
$options = $db->getConnectionOptions();
if ($options['driver'] !== 'sqlite' || realpath($options['database']) !== realpath($project . '/.local/drupal-runtime/site.sqlite')
  || Settings::get('tcl_support_mail_mode') !== 'capture') {
  throw new RuntimeException('Test réservé à la base locale et aux courriels capturés.');
}
$checks = [];
$check = static function (bool $condition, string $label) use (&$checks): void {
  if (!$condition) { throw new RuntimeException($label); }
  $checks[] = ['label' => $label, 'passed' => TRUE];
};
$transaction = $db->startTransaction();
$savedSettings = Settings::getAll();
$collectorBefore = \Drupal::state()->get('system.test_mail_collector', []);
try {
  $repo = \Drupal::service('tcl_support.repository');
  $result = $repo->create([
    'submission_id' => hash('sha256', random_bytes(16)), 'category' => 'problem',
    'subject' => "Essai fictif\nSans nouvel en-tête", 'description' => '<script>Texte fictif à afficher sans exécution</script>',
    'email' => 'testeur@example.invalid', 'page' => '/calendrier.html',
  ]);
  $id = $result['id'];
  $ticket = $repo->load($id);
  $check($result['created'] && $ticket->status === 'new', 'Demande durable créée avec état Nouvelle');
  $duplicate = $repo->create([
    'submission_id' => $ticket->submission_id, 'category' => 'need', 'subject' => 'Autre texte',
    'description' => 'Ne doit pas remplacer la demande', 'email' => '', 'page' => '/',
  ]);
  $check(!$duplicate['created'] && $duplicate['id'] === $id, 'Même soumission sans doublon ni écrasement');
  $check($repo->load($id)->subject === $ticket->subject, 'Contenu initial conservé après doublon');
  $check($repo->update($id, 1, 'in_progress', 'Responsable fictif', 'Analyse locale fictive.', 1), 'État, responsable et note enregistrés');
  $check(!$repo->update($id, 1, 'closed', 'Autre personne', 'Révision périmée.', 1), 'Révision périmée refusée');
  $check($repo->load($id)->status === 'in_progress' && count($repo->history($id)) === 2, 'Historique préservé après conflit');
  try {
    $repo->update($id, 2, 'invented', '', '', 1);
    $check(FALSE, 'État inventé refusé');
  }
  catch (InvalidArgumentException) { $check(TRUE, 'État inventé refusé'); }
  $check(\Drupal::service('tcl_support.notifier')->send($id) === 'captured_local', 'Courriel capturé sans envoi réseau');
  $messages = \Drupal::state()->get('system.test_mail_collector', []);
  $message = end($messages);
  $check($message['to'] === 'support@tclongages.fr' && $message['from'] === 'support@tclongages.fr', 'Destinataire et expéditeur fixes');
  $check(!str_contains($message['subject'], "\n") && !str_contains($message['subject'], "\r"), 'Objet du courriel sans injection d’en-tête');
  $check(str_contains(implode("\n", (array) $message['body']), '/calendrier.html'), 'Courriel contient la page concernée');
  new Settings($savedSettings + []);
  $disabled = $savedSettings;
  $disabled['tcl_support_mail_mode'] = 'disabled';
  new Settings($disabled);
  $check(\Drupal::service('tcl_support.notifier')->send($id) === 'disabled', 'Envoi fermé sans faux succès de livraison');
  $check($repo->load($id)->description === $ticket->description, 'Demande conservée lorsque le transport est fermé');
  new Settings($savedSettings);
  $realManager = \Drupal::service('plugin.manager.mail');
  $failingManager = new class($realManager) extends MailManager {
    public function __construct(private readonly MailManager $inner) {}
    public function getInstance(array $options) { return $this->inner->getInstance($options); }
    public function mail($module, $key, $to, $langcode, $params = [], $reply = NULL, $send = TRUE) { return ['result' => FALSE]; }
  };
  $check((new SupportNotifier($repo, $failingManager))->send($id) === 'failed', 'Échec du transport enregistré');
  $check($repo->load($id)->notification === 'failed' && $repo->load($id)->subject === $ticket->subject, 'Ticket intact après échec de courriel');
  $check(count($repo->history($id)) === 5, 'Notifications et traitement présents dans le journal privé');
  $flood = \Drupal::service('flood');
  $testIdentifier = 'support-local-test-' . bin2hex(random_bytes(8));
  for ($i = 0; $i < 5; $i++) { $flood->register('tcl_support.submit', 3600, $testIdentifier); }
  $check(!$flood->isAllowed('tcl_support.submit', 5, 3600, $testIdentifier), 'Limitation des soumissions après cinq demandes par heure');
}
finally {
  new Settings($savedSettings);
  $transaction->rollBack();
  \Drupal::state()->set('system.test_mail_collector', $collectorBefore);
}
$report = ['observedAt' => gmdate('c'), 'scope' => 'local-sqlite-only', 'outgoingMail' => FALSE, 'checks' => $checks];
file_put_contents($project . '/.local/support-runtime-verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
echo count($checks) . " contrôles métier du support réussis ; aucun courriel envoyé.\n";
