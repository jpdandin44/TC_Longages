<?php
declare(strict_types=1);
require dirname(__DIR__).'/drupal/bin/local-bootstrap.php';
use Drupal\Core\Site\Settings;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\tcl_bureau\GoogleContactsConnection as G;
use Drupal\tcl_bureau\MemberContacts;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
$checks=[];
function gc(string $label,bool $ok):void{global $checks;if(!$ok)throw new RuntimeException($label);$checks[]=$label;}
function denied(callable $fn):bool{try{$fn();return FALSE;}catch(Throwable){return TRUE;}}
$original=Settings::getAll();$s=$original;$s['tcl_registration_recipe_enabled']=TRUE;$s['tcl_member_contacts']=['enabled'=>TRUE,'client_id'=>'fixture-client','client_secret'=>'fixture-secret','account'=>'tclongages@gmail.com','label'=>'Adhérents_2026_2027'];new Settings($s);
$connection=Drupal::service('tcl_bureau_oauth.connection');$http=Drupal::httpClient();$store=Drupal::keyValue('tcl_bureau.google_contacts');$saved=$store->get('grant');$store->delete('grant');
$actor=Drupal\user\Entity\User::create(['name'=>'google-fixture-'.bin2hex(random_bytes(4)),'status'=>1,'roles'=>['tcl_admin']]);$actor->save();
$request=Request::create('https://preprod.tclongages.fr/bureau/contacts-google');$request->setSession(new Session(new MockArraySessionStorage()));$stack=Drupal::requestStack();$stack->push($request);
try{
 gc('Anonymous denied',!G::allowed(new AnonymousUserSession()));gc('Native admin denied',!G::allowed($actor));
 Drupal::getContainer()->set('tcl_bureau_oauth.connection',new class{public function valid():bool{return TRUE;}});
 gc('Unconnected state',!MemberContacts::configured());
 parse_str(parse_url(G::begin($actor),PHP_URL_QUERY),$query);
 gc('Fixed callback and readonly scope',$query['redirect_uri']===G::CALLBACK&&str_contains($query['scope'],'contacts.readonly')&&!str_contains($query['scope'],'gmail')&&$query['code_challenge_method']==='S256');
 gc('State mismatch refused',denied(fn()=>G::finish($actor,'forged','fake')));
 gc('State consumed after refusal',denied(fn()=>G::finish($actor,$query['state'],'fake')));
 $history=[];
 $mock=new GuzzleHttp\Handler\MockHandler();$handler=GuzzleHttp\HandlerStack::create($mock);$handler->push(GuzzleHttp\Middleware::history($history));Drupal::getContainer()->set('http_client',new GuzzleHttp\Client(['handler'=>$handler]));
 $token=['refresh_token'=>'fixture-refresh','access_token'=>'fixture-access','scope'=>'openid https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/contacts.readonly'];
 $response=static fn($a)=>new GuzzleHttp\Psr7\Response(200,[],json_encode($a));
 parse_str(parse_url(G::begin($actor),PHP_URL_QUERY),$query);$mock->append($response($token),$response(['email'=>'wrong@example.invalid','email_verified'=>TRUE]));
 gc('Wrong Google account refused',denied(fn()=>G::finish($actor,$query['state'],'fake')));gc('Failure does not connect',!MemberContacts::configured());
 parse_str(parse_url(G::begin($actor),PHP_URL_QUERY),$query);$mock->append($response($token),$response(['email'=>'tclongages@gmail.com','email_verified'=>TRUE]),$response(['contactGroups'=>[['name'=>'Adhérents_2026_2027','resourceName'=>'contactGroups/fixture','memberCount'=>21]]]));
 gc('Exact group qualified',G::finish($actor,$query['state'],'fake')===21);
 gc('Encrypted refresh token not plaintext',!str_contains(json_encode($store->get('grant')),'fixture-refresh'));
 gc('Configured reader recovers private grant',MemberContacts::configured()&&G::settings()['refresh_token']==='fixture-refresh'&&G::settings()['group']==='contactGroups/fixture');
 gc('No replay',denied(fn()=>G::finish($actor,$query['state'],'fake')));
 $s['tcl_member_contacts']['client_secret']='different';new Settings($s);gc('Credential change invalidates grant',!MemberContacts::configured());new Settings($original);
 $stack->push(Request::create('https://tclongages.fr/'));gc('Production denied',!G::allowed($actor)&&G::settings()===[]);$stack->pop();
 gc('Only expected remote methods',count($history)===5&&$history[0]['request']->getMethod()==='POST'&&$history[4]['request']->getMethod()==='GET');
 file_put_contents(dirname(__DIR__).'/.local/google-contacts-tests.json',json_encode(['passed'=>TRUE,'checks'=>$checks,'liveGoogleAccess'=>FALSE]));echo count($checks)." Google connection checks passed; no external requests.\n";
}finally{
 if($saved===NULL)$store->delete('grant');else $store->set('grant',$saved);$actor->delete();$stack->pop();new Settings($original);Drupal::getContainer()->set('http_client',$http);Drupal::getContainer()->set('tcl_bureau_oauth.connection',$connection);
}
