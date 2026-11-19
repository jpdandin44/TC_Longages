<?php
namespace Drupal\Core\Form {
 interface FormStateInterface { public function getTriggeringElement(); public function setRebuild(); }
 class FormBase { public static bool $allowed=true; public function currentUser(){return self::$allowed;} public function messenger(){return \Drupal::$messages;} }
}
namespace Drupal\Core\Site { class Settings {} }
namespace Drupal\Core { class Url { public static function fromRoute($route) { return $route; } } }
namespace Symfony\Component\HttpKernel\Exception { class AccessDeniedHttpException extends \RuntimeException {} }
namespace Drupal\tcl_bureau {
 class GmailConnection { public static function enabled(){return false;} }
 class GoogleContactsConnection { public static bool $enabled=true; public static function allowed($u){return $u;} public static function settings(){return ['enabled'=>self::$enabled,'client_id'=>'fixture','client_secret'=>'fixture','refresh_token'=>'fixture','group'=>'contactGroups/fixture'];} }
 class SeasonCatalogue {public static function recipeEnabled(){return false;}}
 class RecipeMail {public static function communicationEnabled(){return false;}}
}
namespace {
 class Bag {public array $data=[];public function get($k){return $this->data[$k]??null;}public function set($k,$v){$this->data[$k]=$v;}}
 class Messages {public array $errors=[];public array $success=[];public function addStatus($s){$this->success[]=$s;}public function addError($s){$this->errors[]=$s;}}
 class Request {public Bag $attributes;public function __construct(){$this->attributes=new Bag;}public function getSchemeAndHttpHost(){return 'https://tclongages.fr';}}
 class Http {public array $queue=[];public array $calls=[];public function post($u,$o){if($u!=='https://oauth2.googleapis.com/token')throw new \RuntimeException('Unexpected write');return $this->next($u);}public function get($u,$o){return $this->next($u);}private function next($u){$this->calls[]=$u;$d=array_shift($this->queue);if($d===null)throw new \RuntimeException('fixture unavailable');return new class($d){public function __construct(private $d){} public function getBody(){return json_encode($this->d);}};}}
 class Drupal {public static Bag $store;public static Request $request;public static Messages $messages;public static Http $http;public static function keyValue($s){return self::$store;}public static function request(){return self::$request;}public static function httpClient(){return self::$http;}}
 class State implements \Drupal\Core\Form\FormStateInterface {public bool $rebuilt=false;public function getTriggeringElement(){return ['#name'=>'refresh'];}public function setRebuild(){$this->rebuilt=true;}}
 Drupal::$store=new Bag;Drupal::$request=new Request;Drupal::$messages=new Messages;Drupal::$http=new Http;
 $src=dirname(__DIR__).'/drupal/web/modules/custom/tcl_bureau/src/';
 require $src.'MemberContacts.php'; require $src.'Form/GoogleContactsForm.php';
 $checks=[];function check($name,$ok){global $checks;if(!$ok)throw new \RuntimeException($name);$checks[]=$name;}
 $f=new \Drupal\tcl_bureau\Form\GoogleContactsForm;$state=new State;$form=[];
 function fixture($label='Adhérents_2026_2027') { Drupal::$http->queue=[['access_token'=>'fixture'],['name'=>$label,'memberCount'=>3,'memberResourceNames'=>['people/a','people/b','people/c']],['responses'=>[['person'=>['emailAddresses'=>[['value'=>' Test@example.invalid ']]]],['person'=>['emailAddresses'=>[['value'=>'test@example.invalid']]]],['person'=>['emailAddresses'=>[['value'=>'invalid']]]]]]]; }
 fixture();$f->submitForm($form,$state);$saved=Drupal::$store->get('last_refresh');
 check('Production refresh deduplicates addresses',$saved['count']===1);check('Invalid contact excluded',$saved['excluded']===1);check('Refresh rebuilds dated summary',$state->rebuilt&&isset($f->buildForm([],$state)['last_refresh']));
 $f->submitForm($form,new State);check('Provider failure preserves last success',Drupal::$store->get('last_refresh')===$saved&&count(Drupal::$messages->errors)===1);
 fixture('Wrong label');$f->submitForm($form,new State);check('Wrong group refused without replacing summary',Drupal::$store->get('last_refresh')===$saved&&count(Drupal::$messages->errors)===2);
 \Drupal\tcl_bureau\GoogleContactsConnection::$enabled=false;check('Disconnected button disabled',$f->buildForm([],$state)['actions']['refresh']['#disabled']===true);
 \Drupal\Core\Form\FormBase::$allowed=false;$denied=0;try{$f->buildForm([],$state);}catch(\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException){$denied++;}try{$f->submitForm($form,$state);}catch(\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException){$denied++;}check('Access denied on display and submission',$denied===2);
 check('No recipient addresses persisted',array_keys(Drupal::$store->data)===['last_refresh']&&!str_contains(json_encode(Drupal::$store->data),'@'));
 echo json_encode(['status'=>'passed','scope'=>'isolated PHP harness with real form and directory code, fake Google and Drupal services','checks'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
}
