<?php
declare(strict_types=1);
// Standalone read-only renderer tests: no database, HTTP or mail transport.
require $argv[1] ?? dirname(__DIR__) . '/drupal/vendor/autoload.php';
require dirname(__DIR__) . '/drupal/web/modules/custom/tcl_bureau/src/PublicAgenda.php';
require dirname(__DIR__) . '/drupal/web/modules/custom/tcl_bureau/src/EventSchedule.php';
require dirname(__DIR__) . '/drupal/web/modules/custom/tcl_bureau/src/EventRepository.php';
use Drupal\tcl_bureau\PublicAgenda;
function base_path() { return '/'; }
function ok(string $name, bool $value): void { if (!$value) { throw new RuntimeException($name); } echo "PASS $name\n"; }
$event = ['title'=>'Private internal title','location'=>'private location','description'=>'secret@example.invalid','start_date'=>'2026-11-20','end_date'=>'2026-11-20','start_time'=>'20:30','end_time'=>'22:00','frequency'=>'once','until'=>'','excluded'=>'','status'=>'scheduled','category'=>'festival','poster_post'=>'1'];
$public = [1=>['title'=>'Instant <Beaujolais>']];
$html = PublicAgenda::month([$event],$public,'2026-11');
ok('published announcement and exact times', str_contains($html,'Instant &lt;Beaujolais&gt;') && str_contains($html,'20:30 – 22:00'));
ok('date and public destination', str_contains($html,'2026-11-20') && str_contains($html,'href="/actualites/1"'));
ok('no private fields, identity or administration', !str_contains($html,'private location') && !str_contains($html,'secret@') && !str_contains($html,'Private internal') && !str_contains($html,'/bureau'));
ok('unpublished or withdrawn announcement hides event', !str_contains(PublicAgenda::month([$event],[],'2026-11'),'20:30'));
ok('unlinked event stays private', !str_contains(PublicAgenda::month([array_replace($event,['poster_post'=>'0'])],$public,'2026-11'),'20:30'));
ok('other month stays empty', str_contains(PublicAgenda::month([$event],$public,'2026-12'),'Aucun rendez-vous'));
ok('cancelled event explicit', str_contains(PublicAgenda::month([array_replace($event,['status'=>'cancelled'])],$public,'2026-11'),'Annulé'));
$weekly=array_replace($event,['frequency'=>'weekly','until'=>'2026-12-04','excluded'=>'2026-11-27']);
ok('recurrence excluded date absent', substr_count(PublicAgenda::month([$weekly],$public,'2026-11'),'href="/actualites/1"')===1);
ok('next month recurrence', substr_count(PublicAgenda::month([$weekly],$public,'2026-12'),'href="/actualites/1"')===1);
$overnight=array_replace($event,['end_date'=>'2026-11-21','end_time'=>'00:00']);
ok('exclusive midnight does not duplicate',substr_count(PublicAgenda::month([$overnight],$public,'2026-11'),'href="/actualites/1"')===1);
ok('invalid schedule omitted',!str_contains(PublicAgenda::month([array_replace($event,['start_date'=>'invalid'])],$public,'2026-11'),'20:30'));
ok('invalid month input escaped by fallback',!str_contains(PublicAgenda::month([$event],$public,'<script>'),' <script>'));
