<?php
declare(strict_types=1);
require dirname(__DIR__) . '/drupal/bin/local-bootstrap.php';
// Drupal's exception renderer must not turn a failed CLI assertion into exit 0.
set_exception_handler(static function (\Throwable $error): void { fwrite(STDERR, 'Workspace test failed: ' . $error->getMessage() . PHP_EOL); exit(1); });
use Drupal\Core\Site\Settings;
use Drupal\Core\Form\FormState;
use Drupal\tcl_bureau\WorkspaceNavigation;
use Drupal\tcl_bureau\Controller\WorkspaceReportsController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
$checks=[];
$routes=Symfony\Component\Yaml\Yaml::parseFile(dirname(__DIR__).'/drupal/web/modules/custom/tcl_bureau/tcl_bureau.routing.yml');

function uxcheck($label,$ok){global $checks;$checks[]=['label'=>$label,'passed'=>(bool)$ok];if(!$ok)throw new RuntimeException($label);}
foreach (['responses'=>'/bureau/reponses','delivery_history'=>'/bureau/envois','responses_recipe'=>'/bureau/suivi-reponses','delivery_history_recipe'=>'/bureau/historique-envois'] as $name=>$path) {
 uxcheck('Distinct route preserved: '.$name, $routes['tcl_bureau.'.$name]['path']===$path);
}
uxcheck('Synthetic routes retain preproduction guard',str_contains($routes['tcl_bureau.responses_recipe']['requirements']['_custom_access'],'ClubFollowupController::access') && str_contains($routes['tcl_bureau.delivery_history_recipe']['requirements']['_custom_access'],'ClubFollowupController::access'));
$settings=Settings::getAll();$s=$settings;$s['tcl_registration_recipe_enabled']=TRUE;new Settings($s);
$connection=Drupal::service('tcl_bureau_oauth.connection');$actorBefore=Drupal::currentUser()->getAccount();
$tx=Drupal::database()->startTransaction();
$request=Request::create('http://127.0.0.1:4196/bureau/reponses');$request->setSession(new Session(new MockArraySessionStorage()));$request->attributes->set('_route','tcl_bureau.responses');Drupal::requestStack()->push($request);
try{
 Drupal::getContainer()->set('tcl_bureau_oauth.connection',new class{public function valid():bool{return TRUE;}});
 $actor=Drupal\user\Entity\User::create(['name'=>'Bureau demonstration','status'=>1,'roles'=>['tcl_bureau']]);$actor->save();Drupal::currentUser()->setAccount($actor);
 $repo=Drupal::service('tcl_bureau.communication');
 $post=$repo->save($actor,NULL,['title'=>'Instant Beaujolais · démonstration','category'=>'event','body'=>'Retrouvons-nous au club pour un moment convivial.','link'=>'','tenup_visible'=>'no','image_alt'=>'','channels'=>'site,email'],bin2hex(random_bytes(16)),0);
 $id=bin2hex(random_bytes(16));$date='2026-11-20';
 Drupal::service('tcl_bureau.events')->save($actor,$id,0,['title'=>'Instant Beaujolais · démonstration','location'=>'Club house','description'=>'Données fictives uniquement','start_date'=>$date,'end_date'=>$date,'start_time'=>'18:30','end_time'=>'23:30','frequency'=>'once','until'=>'','excluded'=>'','category'=>'meal','poster_post'=>'0','status'=>'scheduled']);
 $people=[];foreach(['yes','no','maybe','unknown','yes'] as $i=>$answer){$people[]=['name'=>'Essai '.$i,'email'=>'essai'.$i.'@example.invalid','answer'=>$answer,'count'=>$answer==='yes'?4:0,'event_revision'=>$i===4?0:1];}
 Drupal::keyValue('tcl_bureau.event_participation')->set($id.'.'.$date,['revision'=>1,'people'=>$people,'registration_post'=>$post]);
 $request->query->set('event',$id);$request->query->set('date',$date);
 $controller=WorkspaceReportsController::create(Drupal::getContainer());$build=$controller->responses();
 uxcheck('Confirmed people exclude stale answers',$build['stats']['people']['value']['#value']==='4');
 uxcheck('Stale answers separately identified',$build['stats']['reconfirm']['value']['#value']==='1');
 uxcheck('Preproduction addresses masked',$build['table']['data']['#rows'][0][1]['data']['#plain_text']==='participant1@example.invalid');
 $request->query->set('answer','no');$filtered=$controller->responses();uxcheck('Answer filter selects absences',count($filtered['table']['data']['#rows'])===1);$request->query->remove('answer');
 Drupal::keyValue('tcl_bureau.member_mail')->set($post.'.1.'.hash('sha256','essai@example.invalid'),['status'=>'accepted','at'=>time()]);
 $request->query->set('post',$post);$history=$controller->history();uxcheck('History reads actual trace',count($history['table']['data']['#rows'])===1);uxcheck('Acceptance is not delivery',$history['table']['data']['#rows'][0][5]==='Accepté par le serveur');
 $form=Drupal::formBuilder()->getForm(Drupal\tcl_bureau\Form\CommunicationForm::class);
 uxcheck('Nested variants retain submitted value paths',$form['channel_copy']['copy_email']['title']['#parents']===['copy_email','title']);
 uxcheck('Channel adaptation optional',$form['channel_copy']['#open']===FALSE);
 $render=static function(array $build):string{return (string)Drupal::service('renderer')->renderRoot($build);};
 $nav=WorkspaceNavigation::build();$navHtml=$render($nav);uxcheck('Common navigation contains responses',str_contains($navHtml,'Suivi des réponses'));
 $css=file_get_contents($tclRoot.'/web/modules/custom/tcl_bureau/css/bureau.css');
 $folder=dirname($tclRoot).'/.local/ux-preview';if(!is_dir($folder))mkdir($folder);
 foreach(['reponses'=>['Suivi des réponses',$build],'envois'=>['Historique des envois',$history],'communication'=>['Nouvelle communication',$form]] as $page=>[$title,$content]){
 file_put_contents($folder.'/'.$page.'.html','<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$title.'</title><style>'.$css.'</style><body class="tcl-workspace">'.$navHtml.'<main><h1>'.$title.'</h1>'.$render($content).'</main></body></html>');
 }
 $captain=Drupal\user\Entity\User::create(['name'=>'Capitaine demonstration','status'=>1,'roles'=>['tcl_capitaine']]);$captain->save();Drupal::currentUser()->setAccount($captain);
 $nav=$render(WorkspaceNavigation::build());uxcheck('Captain has no communication or report menu',!str_contains($nav,'Historique des envois')&&!str_contains($nav,'Suivi des réponses'));
 foreach(['responses','history'] as $method){$denied=FALSE;try{$controller=WorkspaceReportsController::create(Drupal::getContainer());$controller->$method();}catch(Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException){$denied=TRUE;}uxcheck('Captain denied '.$method,$denied);}
}finally{
 $tx->rollBack();Drupal::currentUser()->setAccount($actorBefore);Drupal::requestStack()->pop();new Settings($settings);Drupal::getContainer()->set('tcl_bureau_oauth.connection',$connection);
 file_put_contents(dirname($tclRoot).'/.local/workspace-ux-tests.json',json_encode(['scope'=>'local SQLite, mocked OAuth, no hosted change or send','checks'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
}
echo count($checks)." workspace checks passed\n";
