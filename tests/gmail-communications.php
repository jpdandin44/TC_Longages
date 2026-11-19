<?php
declare(strict_types=1);
// Standalone: in-memory stores, fake Google HTTP. No database, mail or network.
$loader = require ($argv[1] ?? dirname(__DIR__) . '/drupal/vendor/autoload.php');
$loader->addPsr4('Drupal\\tcl_bureau\\', dirname(__DIR__) . '/drupal/web/modules/custom/tcl_bureau/src/', TRUE);
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Site\Settings;
use Drupal\tcl_bureau\GmailConnection as G;
use Drupal\tcl_bureau\GmailDelivery as D;
use Drupal\tcl_bureau\MemberMail;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
$checks = 0;
function check(string $label, bool $pass): void { global $checks; if (!$pass) { throw new RuntimeException($label); } $checks++; echo 'PASS ' . $label . PHP_EOL; }
function rejects(callable $fn): bool { try { $fn(); return FALSE; } catch (Throwable) { return TRUE; } }
$container = new ContainerBuilder(); Drupal::setContainer($container);
$stack = new RequestStack(); $request = Request::create('https://tclongages.fr/bureau/gmail'); $request->setSession(new Session(new MockArraySessionStorage())); $stack->push($request);
$container->set('request_stack', $stack); $container->set('keyvalue', new KeyValueMemoryFactory());
$container->set('tcl_bureau.repository', new class { public function allowed($actor, $permission): bool { return $actor->id() === 42 && $permission === 'administer tcl bureau accounts'; } });
$settings = ['hash_salt' => 'fictitious-only', 'tcl_gmail' => ['enabled' => TRUE], 'tcl_member_contacts' => ['enabled' => TRUE, 'client_id' => 'test-client', 'client_secret' => 'test-secret', 'account' => G::ACCOUNT, 'label' => 'Adhérents_2026_2027']]; new Settings($settings);
$actor = new UserSession(['uid' => 42]); $other = new UserSession(['uid' => 43]);
check('Non-admin cannot connect', rejects(fn() => G::begin($other)));
parse_str(parse_url(G::begin($actor), PHP_URL_QUERY), $query);
check('Only send scope and identity requested', $query['scope'] === 'openid email ' . G::SCOPE && $query['include_granted_scopes'] === 'false');
check('Production callback and PKCE', $query['redirect_uri'] === 'https://tclongages.fr/bureau/contacts-google/retour' && $query['code_challenge_method'] === 'S256');
check('Incorrect state rejected', rejects(fn() => G::finish($actor, 'bad', 'code')));
function google(array $responses): array { $history = []; $handler = HandlerStack::create(new MockHandler($responses)); $handler->push(Middleware::history($history)); return [new Client(['handler' => $handler]), &$history]; }
$token = ['access_token' => 'access-test', 'refresh_token' => 'refresh-test', 'scope' => 'openid email ' . G::SCOPE];
$identity = ['email_verified' => TRUE, 'email' => G::ACCOUNT];
foreach (['wrong-account', 'broad-scope', 'unverified'] as $case) {
  parse_str(parse_url(G::begin($actor), PHP_URL_QUERY), $q); $t=$token; $i=$identity;
  if ($case === 'wrong-account') { $i['email']='wrong@example.invalid'; }
  if ($case === 'broad-scope') { $t['scope'].=' https://mail.google.com/'; }
  if ($case === 'unverified') { $i['email_verified']=FALSE; }
  [$client] = google([new Response(200, [], json_encode($t)), new Response(200, [], json_encode($i))]); $container->set('http_client', $client);
  check('Reject ' . $case, rejects(fn()=>G::finish($actor,$q['state'],'fake-code')) && !G::connected());
}
parse_str(parse_url(G::begin($actor), PHP_URL_QUERY), $q);
[$client] = google([new Response(200, [], json_encode($token)), new Response(200, [], json_encode($identity))]); $container->set('http_client', $client); G::finish($actor,$q['state'],'fake-code');
check('Grant sealed and connected', G::connected() && !str_contains(json_encode(Drupal::keyValue(G::STORE)->getAll()),'refresh-test'));
check('Callback replay rejected', rejects(fn()=>G::finish($actor,$q['state'],'fake-code')));
$preprod = Request::create('https://preprod.tclongages.fr/bureau/gmail'); $preprod->setSession(new Session(new MockArraySessionStorage())); $stack->push($preprod);
check('Production grant cannot cross environments', !G::connected()); $stack->pop();
[$client] = google([new Response(400, [], '{"error":"invalid_grant"}')]); $container->set('http_client',$client);
check('Revoked grant blocks sending', rejects(fn()=>G::token()));
[$client] = google([new Response(200, [], '{"access_token":"refreshed","scope":"https://www.googleapis.com/auth/gmail.send"}')]); $container->set('http_client',$client);
check('Refresh succeeds', G::token()==='refreshed');
$row=['id'=>1,'title'=>'Fête du club','body'=>'Bonjour','link'=>'','status'=>'validated','channels'=>'email','channel_texts'=>''];
$links=['yes'=>'https://tclongages.fr/participation?test=yes','no'=>'https://tclongages.fr/participation?test=no','maybe'=>'https://tclongages.fr/participation?test=maybe'];
$html=MemberMail::html($row,$links); $raw=D::raw('one@example.invalid','Fête du club','Version texte',$html); $mime=base64_decode(strtr($raw,'-_','+/'));
check('Fixed club Gmail sender and no other recipients', str_contains($mime,'From: Tennis Club de Longages <tclongages@gmail.com>') && str_contains($mime,'To: one@example.invalid') && !str_contains($mime,'Bcc:') && !str_contains($mime,'Cc:'));
check('Multipart text and HTML', str_contains($mime,'multipart/alternative') && str_contains($mime,'text/plain') && str_contains($mime,'text/html'));
check('Three response buttons survive MIME encoding', str_contains($mime,chunk_split(base64_encode('<!doctype html><html lang="fr"><body>'.$html.'</body></html>'),76,"\r\n")) && substr_count($html,'https://tclongages.fr/participation?test=')===3);
check('Subject header injection rejected', rejects(fn()=>D::raw('one@example.invalid',"test\r\nBcc: bad@example.invalid",'', '')));
check('Recipient header injection rejected', rejects(fn()=>D::raw("one@example.invalid\r\nBcc: bad@example.invalid",'test','', '')));
foreach ([200=>'accepted',400=>'failed',401=>'failed',403=>'failed',429=>'failed',500=>'pending'] as $code=>$expected) {
  [$client] = google([new Response($code,[], $code===200 ? '{"id":"abc123"}' : '{}')]);
  $result=D::deliver($client,'fake-token',$raw); check('HTTP '.$code.' => '.$expected,$result['status']===$expected);
}
[$client] = google([new Response(200,[], '{}')]); check('Missing message ID is uncertain',D::deliver($client,'fake-token',$raw)['status']==='pending');
[$client] = google([new GuzzleHttp\Exception\ConnectException('timeout',new GuzzleHttp\Psr7\Request('POST','https://gmail.googleapis.com'))]); check('Network timeout is uncertain',D::deliver($client,'fake-token',$raw)['status']==='pending');
$stack->push($preprod); check('Recipe refuses unapproved recipient before network',rejects(fn()=>D::send('outside@example.invalid','test','','','fake-token'))); $stack->pop();
$settings['tcl_gmail']['enabled']=FALSE; new Settings($settings); check('Disabled Gmail rejects sends',rejects(fn()=>D::send('one@example.invalid','test','','','fake-token')));
$settings['tcl_gmail']['enabled']=TRUE; new Settings($settings);
$row += ['revision'=>6, 'approved_revision'=>6];
$container->set('tcl_bureau.communication', new class($row) {
  public function __construct(public array $row) {}
  public function get($actor, $id): array { return $this->row; }
});
$container->set('lock', new class {
  public function acquire($name, $timeout): bool { return TRUE; }
  public function release($name): void {}
});
Drupal::keyValue('tcl_bureau.member_directory')->set('adherents_2026_2027', ['label'=>'Adhérents_2026_2027','contacts_by_email'=>['one@example.invalid'=>[],'two@example.invalid'=>[]]]);
$snapshot=MemberMail::snapshot($actor,1,[]); $snapshot['recipients']=['one@example.invalid'];
[$client]=google([new Response(200,[],json_encode($token)),new Response(200,[],'{"id":"first"}'),new Response(200,[],json_encode($token))]); $container->set('http_client',$client);
$r=MemberMail::send($actor,$snapshot);
$key='1.6.'.hash('sha256','one@example.invalid'); $store=Drupal::keyValue('tcl_bureau.member_mail');
check('Send pipeline records Gmail receipt for selected recipient only', $r['accepted']===1 && count($store->getAll())===1 && $store->get($key)['message_id']==='first');
$r=MemberMail::send($actor,$snapshot,TRUE);
check('Accepted receipt prevents duplicate even with retry requested',$r['skipped']===1 && $r['accepted']===0);
$old=$store->get($key); $old['status']='bounced'; $store->set($key,$old);
[$client]=google([new Response(200,[],json_encode($token)),new Response(200,[],'{"id":"retry"}')]); $container->set('http_client',$client);
$r=MemberMail::send($actor,$snapshot,TRUE);
check('Explicit bounced retry preserves previous receipt',$r['accepted']===1 && $store->get($key)['previous']['message_id']==='first');
$store->set($key,['status'=>'pending']);
[$client]=google([new Response(200,[],json_encode($token))]); $container->set('http_client',$client);
check('Uncertain send cannot be retried',MemberMail::send($actor,$snapshot,TRUE)['skipped']===1);
[$client]=google([new Response(400,[],'{"error":"invalid_grant"}')]); $container->set('http_client',$client);
check('Pipeline blocks expired Gmail without legacy transport',rejects(fn()=>MemberMail::send($actor,$snapshot)));
echo json_encode(['passed'=>$checks,'network'=>'mocked','mail_sent'=>FALSE]).PHP_EOL;
