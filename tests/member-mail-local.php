<?php
declare(strict_types=1);
require dirname(__DIR__) . '/drupal/bin/local-bootstrap.php';
use Drupal\Core\Site\Settings;
use Drupal\tcl_bureau\MemberContacts;
use Drupal\tcl_bureau\MemberMail;
use Symfony\Component\HttpFoundation\Request;
$checks=[];
function mc(string $label,bool $ok):void{global $checks;$checks[]=['label'=>$label,'passed'=>$ok];if(!$ok)throw new RuntimeException($label);}
function rejected(callable $fn):bool{try{$fn();return FALSE;}catch(Throwable){return TRUE;}}
$originalSettings=Settings::getAll();$s=$originalSettings;$s['tcl_bureau_recipe_mail']=['enabled'=>TRUE,'allowed_recipients'=>['one@example.invalid','two@example.invalid']];
$s['tcl_member_contacts']=['enabled'=>TRUE,'client_id'=>'fictitious','client_secret'=>'fictitious','refresh_token'=>'fictitious','group'=>'contactGroups/fictitious'];new Settings($s);
$originalMail=Drupal::config('system.mail')->getRawData();$collector=Drupal::state()->get('system.test_mail_collector');
$connection=Drupal::service('tcl_bureau_oauth.connection');$http=Drupal::httpClient();
$actor=Drupal\user\Entity\User::create(['name'=>'member-mail-'.bin2hex(random_bytes(4)),'status'=>1,'roles'=>['tcl_bureau']]);$actor->save();
$stack=Drupal::requestStack();$stack->push(Request::create('http://127.0.0.1:4186/'));
$repo=Drupal::service('tcl_bureau.communication');
try{
 mc('Native role cannot bypass OAuth',rejected(fn()=>MemberMail::post($actor,1)));
 Drupal::getContainer()->set('tcl_bureau_oauth.connection',new class{public function valid():bool{return TRUE;}});
 $input=['title'=>'Essai fictif courriel','category'=>'event','body'=>'Message test','link'=>'','tenup_visible'=>'no','image_alt'=>'','channels'=>'email'];
 $id=$repo->save($actor,NULL,$input,bin2hex(random_bytes(16)),0);
 mc('Draft cannot send',rejected(fn()=>MemberMail::snapshot($actor,$id,[])));$repo->transition($actor,$id,1,'validate');
 $p=MemberMail::snapshot($actor,$id,[['email'=>'one@example.invalid'],['email'=>'two@example.invalid'],['email'=>'one@example.invalid'],['email'=>'outside@example.org']]);
 mc('Recipe filters and deduplicates recipients',count($p['recipients'])===2&&$p['excluded']===1);
 Drupal::configFactory()->getEditable('system.mail')->set('interface.tcl_bureau_member_communication','test_mail_collector')->save();Drupal::state()->set('system.test_mail_collector',[]);
 $r=MemberMail::send($actor,$p);$messages=Drupal::state()->get('system.test_mail_collector');
 mc('Two individual sends accepted by collector',$r['accepted']===2&&count($messages)===2);
 mc('Addresses not exposed to other recipients',$messages[0]['to']==='one@example.invalid'&&!str_contains($messages[0]['body'],'two@example.invalid')&&!isset($messages[0]['headers']['Cc']));
 mc('Recipe prefix preserved',str_starts_with($messages[0]['subject'],'[RECETTE PRÉPRODUCTION]'));
 mc('Repeat does not send twice',MemberMail::send($actor,$p)['skipped']===2&&count(Drupal::state()->get('system.test_mail_collector'))===2);
 $old=$p;$old['prepared']=time()-901;mc('Expired preview blocked',rejected(fn()=>MemberMail::send($actor,$old)));
 $repo->save($actor,$id,$input,bin2hex(random_bytes(16)),2);mc('Edit invalidates preview',rejected(fn()=>MemberMail::send($actor,$p)));
 $stack->push(Request::create('https://tclongages.fr/'));mc('Production refuses mail and Contacts',!MemberContacts::configured()&&rejected(fn()=>MemberMail::send($actor,$p)));$stack->pop();
 $people=[['names'=>[['displayName'=>'Fictif']], 'emailAddresses'=>[['value'=>'wrong@example.invalid'],['value'=>'ONE@example.invalid','metadata'=>['primary'=>TRUE]]]],['emailAddresses'=>[['value'=>'one@example.invalid']]],['emailAddresses'=>[['value'=>'not-email']]]];
 $n=MemberContacts::normalize($people);mc('Primary email and deduplication',count($n['recipients'])===1&&$n['recipients'][0]['email']==='one@example.invalid'&&$n['excluded']===1);
 $history=[];$mock=new GuzzleHttp\Handler\MockHandler([new GuzzleHttp\Psr7\Response(200,[],json_encode(['access_token'=>'fictitious'])),new GuzzleHttp\Psr7\Response(200,[],json_encode(['name'=>'Fictitious group','memberCount'=>1,'memberResourceNames'=>['people/fictitious']])),new GuzzleHttp\Psr7\Response(200,[],json_encode(['responses'=>[['person'=>$people[0]]]]))]);
 $handler=GuzzleHttp\HandlerStack::create($mock);$handler->push(GuzzleHttp\Middleware::history($history));Drupal::getContainer()->set('http_client',new GuzzleHttp\Client(['handler'=>$handler]));
 $n=MemberContacts::recipients();mc('Google group read from mocked provider only',$n['group']==='Fictitious group'&&count($n['recipients'])===1);
 mc('Only read requests to People API',count($history)===3&&$history[1]['request']->getMethod()==='GET'&&$history[2]['request']->getMethod()==='GET');
 $mock->append(new GuzzleHttp\Psr7\Response(400,[], 'provider-secret'));try{MemberContacts::recipients();$safe=FALSE;}catch(RuntimeException $e){$safe=!str_contains($e->getMessage(),'provider-secret');}mc('Provider errors sanitized',$safe);
 file_put_contents(dirname(__DIR__).'/.local/member-mail-tests.json',json_encode(['passed'=>TRUE,'checks'=>$checks,'realMailSent'=>FALSE,'googleLiveAccess'=>FALSE],JSON_PRETTY_PRINT));echo count($checks)." member mail checks passed; mock provider and collector only.\n";
}finally{
 if(isset($id)){Drupal::database()->delete('tcl_bureau_post')->condition('id',$id)->execute();foreach(Drupal::keyValue('tcl_bureau.member_mail')->getAll() as $key=>$row){if(str_starts_with($key,$id.'.'))Drupal::keyValue('tcl_bureau.member_mail')->delete($key);}}
 $actor->delete();Drupal::configFactory()->getEditable('system.mail')->setData($originalMail)->save();Drupal::state()->set('system.test_mail_collector',$collector);Drupal::getContainer()->set('http_client',$http);Drupal::getContainer()->set('tcl_bureau_oauth.connection',$connection);$stack->pop();new Settings($originalSettings);
}
