<?php
declare(strict_types=1);
require dirname(__DIR__).'/drupal/bin/local-bootstrap.php';
use Drupal\user\Entity\User;
$p=dirname(__DIR__).'/.local/invitation-http.json';$config=dirname(__DIR__).'/.local/bureau-oauth-settings.php';
if(($argv[1]??'')==='prepare'){
 if(is_file($p)){throw new RuntimeException('Existing fixture');}
 $password=bin2hex(random_bytes(24));$u=User::create(['name'=>'invitation-http-'.bin2hex(random_bytes(4)),'mail'=>'invitation-http@example.invalid','status'=>1,'roles'=>['tcl_capitaine']]);$u->setPassword($password)->save();
 file_put_contents($p,json_encode(['uid'=>(int)$u->id(),'username'=>$u->getAccountName(),'password'=>$password,'settings'=>file_get_contents($config),'mail'=>Drupal::config('system.mail')->getRawData(),'messages'=>Drupal::state()->get('system.test_mail_collector')]));
 file_put_contents($config,"\n\$settings['tcl_bureau_recipe_mail'] = ['enabled'=>TRUE,'allowed_recipients'=>['invitation-http@example.invalid','club-http@example.invalid'],'registration_to'=>'club-http@example.invalid'];\n",FILE_APPEND);
 Drupal::configFactory()->getEditable('system.mail')->set('interface.tcl_bureau_account_invitation','test_mail_collector')->set('interface.tcl_bureau_registration','test_mail_collector')->save();Drupal::state()->set('system.test_mail_collector',[]);drupal_flush_all_caches();
}
elseif(($argv[1]??'')==='verify'){
 $f=json_decode(file_get_contents($p),TRUE);$m=Drupal::state()->get('system.test_mail_collector');if(count($m)!==1||$m[0]['to']!=='invitation-http@example.invalid'||!str_contains($m[0]['body'],'/user/reset/')){throw new RuntimeException('Expected one captured invitation');}
}
elseif(($argv[1]??'')==='allow-reset'){
 $f=json_decode(file_get_contents($p),TRUE);Drupal::service('flood')->clear('tcl.account.invitation',(string)$f['uid']);
}
elseif(($argv[1]??'')==='verify-reset'){
 $f=json_decode(file_get_contents($p),TRUE);$m=Drupal::state()->get('system.test_mail_collector');$last=end($m);if(count($m)!==2||$last['to']!=='invitation-http@example.invalid'||!str_contains($last['subject'],'Réinitialiser votre mot de passe')||!str_contains($last['body'],'/user/reset/')){throw new RuntimeException('Expected explicit reset');}
}
elseif(($argv[1]??'')==='cleanup'){
 $f=json_decode(file_get_contents($p),TRUE);$u=User::load($f['uid']);if(!$u||$u->getAccountName()!==$f['username']){throw new RuntimeException('Identity mismatch');}
 Drupal::keyValue('tcl_bureau.invited_identities')->delete((string)$u->id());Drupal::keyValue('tcl_bureau.recipe_mail')->delete('password_reset.'.$u->id());Drupal::keyValue('tcl_bureau.recipe_mail')->delete('invitation.'.$u->id());Drupal::service('flood')->clear('tcl.account.invitation',(string)$u->id());$u->delete();file_put_contents($config,$f['settings']);Drupal::configFactory()->getEditable('system.mail')->setData($f['mail'])->save();Drupal::state()->set('system.test_mail_collector',$f['messages']);unlink($p);drupal_flush_all_caches();
}
else{throw new InvalidArgumentException('operation');}
echo "Local invitation fixture operation complete.\n";
