<?php
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Session\AnonymousUserSession;
$root='/home2/daje5127/tcl-preproduction/private/restorations/restore-_vypl584/drupal';
if(PHP_SAPI!=='cli'||realpath($root)!==$root){throw new RuntimeException('Scope');}
$loader=require $root.'/web/autoload.php';chdir($root.'/web');$request=Request::create('https://preprod.tclongages.fr/');$kernel=DrupalKernel::createFromRequest($request,$loader,'prod');$kernel->boot();$kernel->preHandle($request);
if(Drupal::database()->getConnectionOptions()['prefix']!=='sr_f11fe659616a_'||Drupal::VERSION!=='11.4.8'){throw new RuntimeException('Isolated database required');}
$checks=[];$check=function($name,$ok)use(&$checks){if(!$ok)throw new RuntimeException($name);$checks[]=$name;};
$catalogue=Drupal::service('tcl_bureau.seasons');$repo=Drupal::service('tcl_bureau.registrations');$anon=new AnonymousUserSession();
$check('recipe enabled on private copy',Drupal\tcl_bureau\SeasonCatalogue::recipeEnabled());
$check('anonymous cannot manage catalogue',!$catalogue->allowed($anon));
$admin=user_load_by_name('Admin_TC_Longages');$check('existing admin preserved',(bool)$admin);
$check('native identity cannot bypass OAuth',!$catalogue->allowed($admin));
// Confined to this CLI process and this private prefixed restoration only.
Drupal::getContainer()->set('tcl_bureau_oauth.connection',new class{public function valid():bool{return TRUE;}});
$check('Bureau permission with fixture OAuth',$catalogue->allowed($admin));
$state=$catalogue->read($admin);$seed=json_decode(file_get_contents($root.'/web/modules/custom/tcl_bureau/resources/season-seed.json'),TRUE,32,JSON_THROW_ON_ERROR);$seed['notice']='Recette fictive, textes définitifs à compléter.';
$revision=$catalogue->save($admin,$seed,$state['revision'],TRUE);$check('ten formulas published',count($catalogue->published()['formulas'])===10);
$formula=current(array_filter($seed['formulas'],fn($f)=>$f['profile']==='adult'));$key=bin2hex(random_bytes(16));
$v=['profile'=>'adult','first_name'=>'Exemple','last_name'=>'RecetteServeur','birth_date'=>'1990-01-01','email'=>'serveur@example.invalid','formula'=>$formula['id'],'training'=>'unknown','previous_licence'=>'unknown','fictional_confirmation'=>TRUE];
$id=$repo->submitRecipe($anon,$v,$key,$revision);$check('fictitious submission persisted',$id>0);$check('same submission idempotent',$repo->submitRecipe($anon,$v,$key,$revision)===$id);
$before=$repo->get($admin,$id);$seed['formulas'][0]['amountCents']+=100;$catalogue->save($admin,$seed,$revision,TRUE);$after=$repo->get($admin,$id);$check('previous dossier snapshot immutable',$before===$after);
try{$repo->get($anon,$id);$denied=FALSE;}catch(Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e){$denied=TRUE;}$check('anonymous dossier read denied',$denied);
$check('public route installed',Drupal::service('router.route_provider')->getRouteByName('tcl_bureau.registration_recipe')->getPath()==='/adherer');
$check('Bureau route installed',Drupal::service('router.route_provider')->getRouteByName('tcl_bureau.seasons')->getPath()==='/bureau/saison');
$receipt=['checkedAt'=>gmdate('c'),'coreVersion'=>Drupal::VERSION,'checks'=>$checks,'passed'=>TRUE,'scope'=>'private CLI restoration, OAuth fixture only; not real hosted login','productionWritten'=>FALSE];file_put_contents(dirname($root).'/v2-rehearsal.json',json_encode($receipt,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));echo json_encode($receipt,JSON_UNESCAPED_UNICODE).PHP_EOL;
