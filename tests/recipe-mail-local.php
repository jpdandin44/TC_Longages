<?php
declare(strict_types=1);
require dirname(__DIR__) . '/drupal/bin/local-bootstrap.php';
use Drupal\Core\Site\Settings;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\tcl_bureau\RecipeMail;
use Drupal\tcl_bureau\AccountInvitation;
use Drupal\tcl_bureau_oauth\OAuthSettings;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

$checks = [];
function mailCheck(string $label, bool $ok): void { global $checks; if (!$ok) { throw new RuntimeException($label); } $checks[]=$label; }
function mailDenied(callable $fn): bool { try { $fn(); return FALSE; } catch (Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException | Symfony\Component\HttpKernel\Exception\ConflictHttpException | InvalidArgumentException $e) { return TRUE; } }
$settings = Settings::getAll();
$originalMail = Drupal::config('system.mail')->getRawData();
$oldState = Drupal::state()->get('system.test_mail_collector');
$settings['tcl_bureau_recipe_mail']=['enabled'=>TRUE,'allowed_recipients'=>['invite@example.invalid','club@example.invalid'],'registration_to'=>'club@example.invalid'];
$oauth = OAuthSettings::load();
// CLI-only self-provider fixture, never active hosted configuration.
$settings['tcl_identity']=['enabled'=>TRUE,'local_recipe'=>TRUE,'issuer'=>$oauth['issuer'],'client_id'=>$oauth['client_id'],'redirect_uri'=>$oauth['redirect_uri']];
new Settings($settings);
$stack=Drupal::requestStack();$stack->push(Request::create('http://127.0.0.1:4186/'));
$target=User::create(['name'=>'mail-fixture-'.bin2hex(random_bytes(4)),'mail'=>'invite@example.invalid','status'=>1,'roles'=>['tcl_capitaine']]);$target->setPassword(bin2hex(random_bytes(24)))->save();
$admin=User::create(['name'=>'mail-manager-'.bin2hex(random_bytes(4)),'mail'=>'manager@example.invalid','status'=>1,'roles'=>['tcl_admin']]);$admin->save();
$oauthService=Drupal::service('tcl_bureau_oauth.connection');
try {
  mailCheck('Unlisted recipient rejected',!RecipeMail::allowed('someone@example.org'));
  mailCheck('Fictional and explicit recipe input accepted',RecipeMail::inputAllowed('fixture@example.invalid')&&RecipeMail::inputAllowed('invite@example.invalid'));
  $stack->push(Request::create('https://tclongages.fr/'));
  mailCheck('Production forbidden even with enabled setting',RecipeMail::settings()===[]&&!RecipeMail::allowed('invite@example.invalid'));$stack->pop();
  mailCheck('Anonymous invitation denied',mailDenied(fn()=>AccountInvitation::send(new AnonymousUserSession(),(int)$target->id(),AccountInvitation::version($target))));
  mailCheck('Native admin without OAuth denied',mailDenied(fn()=>AccountInvitation::send($admin,(int)$target->id(),AccountInvitation::version($target))));
  Drupal::getContainer()->set('tcl_bureau_oauth.connection',new class {public function valid():bool{return TRUE;}});
  mailCheck('Stale target denied',mailDenied(fn()=>AccountInvitation::send($admin,(int)$target->id(),'stale')));
  Drupal::configFactory()->getEditable('system.mail')->set('interface.tcl_bureau_account_invitation','tcl_null_mail')->save();
  mailCheck('Null transport never reports delivery',!RecipeMail::send('account_invitation','invite@example.invalid','Test','Body'));
  Drupal::configFactory()->getEditable('system.mail')->set('interface.tcl_bureau_account_invitation','test_mail_collector')->set('interface.tcl_bureau_registration','test_mail_collector')->save();
  Drupal::state()->set('system.test_mail_collector',[]);
  mailCheck('Explicit invitation accepted by collector',AccountInvitation::send($admin,(int)$target->id(),AccountInvitation::version($target)));
  $messages=Drupal::state()->get('system.test_mail_collector');
  mailCheck('Exactly one invitation to selected address',count($messages)===1&&$messages[0]['to']==='invite@example.invalid');
  mailCheck('One-time reset link included privately',str_contains($messages[0]['body'],'/user/reset/')&&!str_contains($messages[0]['body'],'destination='));
  mailCheck('Dynamic binding resolves exact UID',OAuthSettings::identityUid($oauth,(string)$target->id())===(int)$target->id());
  mailCheck('Repeat within five minutes denied',mailDenied(fn()=>AccountInvitation::send($admin,(int)$target->id(),AccountInvitation::version($target))));
  $target->block()->save();
  mailCheck('Blocked identity fails resolution',OAuthSettings::identityUid($oauth,(string)$target->id())===NULL);
  mailCheck('Blocked invitation denied',mailDenied(fn()=>AccountInvitation::send($admin,(int)$target->id(),AccountInvitation::version($target))));
  $target->activate()->save();
  $entry=Drupal::keyValue('tcl_bureau.invited_identities')->get((string)$target->id());$entry['uuid']='different';Drupal::keyValue('tcl_bureau.invited_identities')->set((string)$target->id(),$entry);
  mailCheck('Reused ID or UUID mismatch denied',OAuthSettings::identityUid($oauth,(string)$target->id())===NULL);
  mailCheck('No mail for nonexistent registration',RecipeMail::registration(2147483647)==='failed');
  $values=['recipe'=>TRUE,'origin'=>'web_recipe','first_name'=>'Fictif','last_name'=>'Recette courrier','season'=>'2026-2027','email'=>'invite@example.invalid'];
  $id=Drupal::database()->insert('tcl_bureau_registration')->fields(['identity_key'=>hash('sha256',random_bytes(20)),'submission_key'=>bin2hex(random_bytes(16)),'first_name'=>'Fictif','last_name'=>'Recette courrier','season'=>'2026-2027','status'=>'received','details'=>json_encode($values),'revision'=>1,'created'=>time(),'changed'=>time(),'created_by'=>0,'changed_by'=>0])->execute();
  mailCheck('Registration notification accepted',RecipeMail::registration((int)$id)==='accepted');
  mailCheck('Repeated submission returns same mail status',RecipeMail::registration((int)$id)==='accepted');
  $messages=array_values(array_filter(Drupal::state()->get('system.test_mail_collector'), static fn($m)=>str_starts_with($m['id'], 'tcl_bureau_')));
  mailCheck('Single club notification and no applicant acknowledgement',count($messages)===2&&$messages[1]['to']==='club@example.invalid');
  mailCheck('Recipe message contains dossier details',str_contains($messages[1]['body'],'Recette courrier')&&str_contains($messages[1]['subject'],'[RECETTE PRÉPRODUCTION]')&&str_contains($messages[1]['body'],'adhésion fictive'));
  file_put_contents(dirname(__DIR__).'/.local/recipe-mail-tests.json',json_encode(['checks'=>$checks,'passed'=>TRUE,'realMailSent'=>FALSE],JSON_PRETTY_PRINT));
  echo count($checks)." recipe mail checks passed; no real mail sent.\n";
}
finally {
  if(isset($id)){Drupal::database()->delete('tcl_bureau_registration')->condition('id',$id)->execute();Drupal::keyValue('tcl_bureau.recipe_mail')->delete('registration.'.$id);}
  Drupal::keyValue('tcl_bureau.invited_identities')->delete((string)$target->id());Drupal::keyValue('tcl_bureau.recipe_mail')->delete('invitation.'.$target->id());Drupal::service('flood')->clear('tcl.account.invitation',(string)$target->id());
  $target->delete();$admin->delete();
  Drupal::configFactory()->getEditable('system.mail')->setData($originalMail)->save();Drupal::state()->set('system.test_mail_collector',$oldState);
  Drupal::getContainer()->set('tcl_bureau_oauth.connection',$oauthService);$stack->pop();
}
