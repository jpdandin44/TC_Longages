<?php
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\tcl_bureau\CommunicationImage;
$root='/home2/daje5127/tcl-preproduction/private/restorations/restore-b4_west1/drupal';
if(PHP_SAPI!=='cli'||realpath($root)!==$root){throw new RuntimeException('Scope');}
$loader=require $root.'/web/autoload.php';chdir($root.'/web');$request=Request::create('https://preprod.tclongages.fr/');$kernel=DrupalKernel::createFromRequest($request,$loader,'prod');$kernel->boot();$kernel->preHandle($request);
if(Drupal::database()->getConnectionOptions()['prefix']!=='sr_210f6bcda7d5_'||Drupal::VERSION!=='11.4.8'){throw new RuntimeException('Isolated database required');}
$checks=[];$check=function($name,$ok)use(&$checks){if(!$ok)throw new RuntimeException($name);$checks[]=$name;};
Drupal::moduleHandler()->loadInclude('tcl_bureau','install');
$before=Drupal::database()->select('tcl_bureau_post','p')->fields('p')->orderBy('id')->execute()->fetchAll(PDO::FETCH_ASSOC);
tcl_bureau_update_11003();tcl_bureau_update_11003();Drupal::service('update.update_hook_registry')->setInstalledVersion('tcl_bureau',11003);
$after=Drupal::database()->select('tcl_bureau_post','p')->fields('p')->orderBy('id')->execute()->fetchAll(PDO::FETCH_ASSOC);
foreach($after as &$row){$check('existing channels default', $row['channels']==='site,facebook,whatsapp');unset($row['channels']);}unset($row);
$check('existing posts preserved by idempotent update',$before===$after);
$repo=Drupal::service('tcl_bureau.communication');$anon=new AnonymousUserSession();$admin=user_load_by_name('Admin_TC_Longages');
$check('anonymous denied',!$repo->allowed($anon));$check('native login cannot bypass OAuth',!$repo->allowed($admin));
Drupal::getContainer()->set('tcl_bureau_oauth.connection',new class{public function valid():bool{return TRUE;}});
$v=['title'=>'Recette PDF fictive','category'=>'club','body'=>'Essai fictif privé','link'=>'','tenup_visible'=>'no','image_alt'=>'Affiche fictive','channels'=>'whatsapp'];
$pdf="%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n";
$tmp=tempnam(dirname($root),'pdf-');file_put_contents($tmp,$pdf);$upload=new Symfony\Component\HttpFoundation\File\UploadedFile($tmp,'fixture.pdf','application/pdf',NULL,TRUE);
$prepared=CommunicationImage::prepare($upload);unlink($tmp);$check('PDF MIME processing available',$prepared===$pdf);
$id=$repo->save($admin,NULL,$v,bin2hex(random_bytes(16)),0,$prepared);$check('PDF binary retained',$repo->get($admin,$id)['image_data']===$pdf);
$repo->transition($admin,$id,1,'validate');try{$repo->transition($admin,$id,2,'publish');$denied=FALSE;}catch(Symfony\Component\HttpKernel\Exception\ConflictHttpException $e){$denied=TRUE;}$check('site action rejected when unselected',$denied);
$check('private PDF not public',$repo->published($id)===[]);
$v['channels']='site,facebook';$repo->save($admin,$id,$v,bin2hex(random_bytes(16)),2);$check('channel edit resets validation',$repo->get($admin,$id)['approved_revision']==0);
$repo->transition($admin,$id,3,'validate');$repo->transition($admin,$id,4,'publish');$check('selected site publishes',$repo->published($id)['image_data']===$pdf);
$response=(new Drupal\tcl_bureau\Controller\CommunicationController($repo))->publicImage((string)$id);$check('PDF served only as attachment',$response->headers->get('Content-Type')==='application/pdf'&&str_starts_with($response->headers->get('Content-Disposition'),'attachment;')&&$response->getContent()===$pdf);
$repo->transition($admin,$id,5,'withdraw');$check('withdrawal hides PDF',$repo->published($id)===[]);
$receipt=['checkedAt'=>gmdate('c'),'coreVersion'=>Drupal::VERSION,'checks'=>$checks,'passed'=>TRUE,'scope'=>'private CLI restoration, OAuth fixture only; not real hosted login','productionWritten'=>FALSE];file_put_contents(dirname($root).'/v2-rehearsal.json',json_encode($receipt,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));echo json_encode($receipt,JSON_UNESCAPED_UNICODE).PHP_EOL;
