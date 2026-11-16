<?php
declare(strict_types=1);
require dirname(__DIR__) . '/drupal/bin/local-bootstrap.php';
use Drupal\Core\Site\Settings;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\tcl_bureau\GoogleCalendarConnection as G;
use Drupal\tcl_bureau\GoogleCalendarEvents as E;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
$checks = [];
function calCheck(string $label, bool $ok): void { global $checks; if (!$ok) { throw new RuntimeException($label); } $checks[] = $label; }
function calDenied(callable $fn): bool { try { $fn(); return FALSE; } catch (Throwable) { return TRUE; } }
$original = Settings::getAll(); $s = $original; $s['tcl_registration_recipe_enabled'] = TRUE;
$s['tcl_member_contacts'] = ['enabled' => TRUE, 'client_id' => 'fixture-client', 'client_secret' => 'fixture-secret', 'account' => 'tclongages@gmail.com']; new Settings($s);
$http = Drupal::httpClient(); $connection = Drupal::service('tcl_bureau_oauth.connection'); $store = Drupal::keyValue(G::STORE); $saved = $store->getAll(); $store->deleteAll();
$actor = Drupal\user\Entity\User::create(['name' => 'calendar-fixture-' . bin2hex(random_bytes(4)), 'status' => 1, 'roles' => ['tcl_admin']]); $actor->save();
$request = Request::create('https://preprod.tclongages.fr/bureau/agenda-google'); $request->setSession(new Session(new MockArraySessionStorage())); $stack = Drupal::requestStack(); $stack->push($request);
$history = []; $mock = new GuzzleHttp\Handler\MockHandler(); $handler = GuzzleHttp\HandlerStack::create($mock); $handler->push(GuzzleHttp\Middleware::history($history)); Drupal::getContainer()->set('http_client', new GuzzleHttp\Client(['handler' => $handler]));
$response = static fn(array $a, int $code = 200) => new GuzzleHttp\Psr7\Response($code, [], json_encode($a));
try {
  calCheck('Anonymous and native admin denied', !G::allowed(new AnonymousUserSession()) && !G::allowed($actor));
  Drupal::getContainer()->set('tcl_bureau_oauth.connection', new class { public function valid(): bool { return TRUE; } });
  parse_str(parse_url(G::begin($actor), PHP_URL_QUERY), $query);
  calCheck('Only app-created scope and PKCE', $query['scope'] === 'openid email ' . G::SCOPE && $query['code_challenge_method'] === 'S256' && $query['include_granted_scopes'] === 'false');
  calCheck('State mismatch fails', calDenied(fn() => G::finish($actor, 'forged', 'fake')));
  calCheck('State cannot replay', calDenied(fn() => G::finish($actor, $query['state'], 'fake')));
  $token = ['refresh_token' => 'fixture-refresh', 'access_token' => 'fixture-access', 'scope' => 'openid https://www.googleapis.com/auth/userinfo.email ' . G::SCOPE];
  parse_str(parse_url(G::begin($actor), PHP_URL_QUERY), $query);
  $mock->append($response($token), $response(['email' => 'wrong@example.invalid', 'email_verified' => TRUE]));
  calCheck('Wrong account refused', calDenied(fn() => G::finish($actor, $query['state'], 'fake')) && !G::connected());
  parse_str(parse_url(G::begin($actor), PHP_URL_QUERY), $query);
  $broad = $token; $broad['scope'] .= ' https://www.googleapis.com/auth/calendar'; $mock->append($response($broad));
  calCheck('Broad calendar grant refused', calDenied(fn() => G::finish($actor, $query['state'], 'fake')));
  parse_str(parse_url(G::begin($actor), PHP_URL_QUERY), $query);
  calCheck('Calendar callback recognized', G::pending($query['state']) && !G::pending('fake'));
  $mock->append($response($token), $response(['email' => 'tclongages@gmail.com', 'email_verified' => TRUE])); G::finish($actor, $query['state'], 'fake');
  calCheck('Grant sealed separately', G::connected() && !str_contains(json_encode($store->get('grant')), 'fixture-refresh'));
  $mock->append($response(['access_token' => 'access']), $response(['id' => 'fixture@group.calendar.google.com', 'summary' => G::NAME])); G::createCalendar($actor);
  calCheck('Only named secondary calendar created', G::target()['name'] === G::NAME && end($history)['request']->getUri()->getPath() === '/calendar/v3/calendars');
  $n = count($history); G::createCalendar($actor); calCheck('Repeated create does not duplicate', count($history) === $n);
  $target = G::target();
  $metadata = ['id' => $target['id'], 'summary' => G::NAME, 'description' => $target['marker'], 'timeZone' => 'Europe/Paris'];
  $row = ['id' => str_repeat('a', 32), 'title' => 'Test calendrier', 'description' => 'Fictif', 'location' => 'Club fictif', 'start_date' => '2027-03-21', 'start_time' => '10:00', 'end_date' => '2027-03-21', 'end_time' => '11:00', 'frequency' => 'weekly', 'until' => '2027-04-04', 'excluded' => '2027-03-28', 'status' => 'scheduled'];
  $payload = E::payload($row); $remote = ['id' => 'tcl' . $row['id'], 'etag' => 'version-1'] + $payload;
  calCheck('Recurrence and Paris exclusion retained', $payload['recurrence'] === ['RRULE:FREQ=WEEKLY;UNTIL=20270404T215959Z', 'EXDATE;TZID=Europe/Paris:20270328T100000']);
  $mock->append($response(['access_token' => 'access']), $response($metadata), $response(['error' => 'not found'], 404), $response($remote));
  $sync = E::save($row, NULL);
  $last = end($history)['request'];
  calCheck('Creation only on app calendar without attendees', str_contains((string) $last->getUri(), 'fixture%40group.calendar.google.com/events?sendUpdates=none') && !array_key_exists('attendees', json_decode((string) $last->getBody(), TRUE)) && $sync['id'] === $remote['id']);
  $mock->append($response(['access_token' => 'access']), $response($metadata), $response($remote)); $n = count($history); E::save($row, NULL);
  calCheck('Retry after uncertain response adopts without duplicate', count($history) === $n + 3 && end($history)['request']->getMethod() === 'GET');
  $old = $row + ['google' => $sync]; $row['title'] = 'Test modifié'; $newRemote = ['id' => $remote['id'], 'etag' => 'version-2'] + E::payload($row);
  $mock->append($response(['access_token' => 'access']), $response($metadata), $response($remote), $response($newRemote)); $sync2 = E::save($row, $old);
  calCheck('Edit uses ETag on same event', end($history)['request']->getMethod() === 'PATCH' && end($history)['request']->getHeaderLine('If-Match') === 'version-1' && $sync2['id'] === $sync['id']);
  $changedOutside = $remote; $changedOutside['summary'] = 'Changed in Google';
  $mock->append($response(['access_token' => 'access']), $response($metadata), $response($changedOutside));
  calCheck('External edit not overwritten', calDenied(fn() => E::save($row, $old)) && end($history)['request']->getMethod() === 'GET');
  $wrong = $metadata; $wrong['summary'] = 'Official';
  $mock->append($response(['access_token' => 'access']), $response($wrong));
  calCheck('Renamed or wrong calendar fails before event access', calDenied(fn() => E::save($row, $old)) && end($history)['request']->getUri()->getPath() === '/calendar/v3/calendars/fixture%40group.calendar.google.com');
  $row['status'] = 'cancelled'; $cancelled = E::payload($row);
  calCheck('Cancellation reversible without deletion', $cancelled['summary'] === '[ANNULÉ] Test modifié' && $cancelled['transparency'] === 'transparent' && $cancelled['status'] === 'confirmed');
  $row['frequency'] = 'once'; $row['until'] = ''; $row['excluded'] = ''; calCheck('Single event clears recurrence', E::payload($row)['recurrence'] === []);
  $repo = Drupal::service('tcl_bureau.events'); $fixtureId = bin2hex(random_bytes(16));
  $input = $row; $input['id'] = $fixtureId; $input['category'] = 'meal'; $input['poster_post'] = '0';
  $remoteFixture = ['id' => 'tcl' . $fixtureId, 'etag' => 'fixture-version'] + E::payload($input);
  $mock->append($response(['access_token' => 'access']), $response($metadata), $response(['error' => 'not found'], 404), $response($remoteFixture));
  try {
    $repo->save($actor, $fixtureId, 0, $input); $persisted = $repo->get($actor, $fixtureId);
    calCheck('Repository persists only after Google confirmation', $persisted['google']['id'] === 'tcl' . $fixtureId);
    $input['title'] = 'Unsaved conflicting title';
    $mock->append($response(['access_token' => 'access']), $response($metadata), $response($remoteFixture), $response(['error' => 'precondition'], 412));
    calCheck('Google conflict leaves local record unchanged', calDenied(fn() => $repo->save($actor, $fixtureId, 1, $input)) && $repo->get($actor, $fixtureId) === $persisted);
  } finally { Drupal::keyValue('tcl_bureau.recipe_events')->delete($fixtureId); }
  $store->delete('calendar');
  $mock->append($response(['access_token' => 'access']), $response(['error' => 'temporary'], 503));
  calCheck('Uncertain calendar creation not recorded', calDenied(fn() => G::createCalendar($actor)) && G::target() === NULL);
  $n = count($history); calCheck('Uncertain create blocks automatic duplicate', calDenied(fn() => G::createCalendar($actor)) && count($history) === $n);
  $store->delete('creation_pending'); $store->set('calendar', $target);
  $stack->push(Request::create('https://tclongages.fr/')); $n = count($history);
  calCheck('Production cannot request a token or target', G::target() === NULL && calDenied(fn() => G::token()) && count($history) === $n); $stack->pop();
  $s['tcl_member_contacts']['client_secret'] = 'rotated'; new Settings($s); calCheck('Credential change invalidates token', calDenied(fn() => G::token()));
  new Settings($original);
  file_put_contents(dirname(__DIR__) . '/.local/google-calendar-tests.json', json_encode(['passed' => TRUE, 'checks' => $checks, 'liveGoogleAccess' => FALSE], JSON_PRETTY_PRINT));
  echo count($checks) . " Google Calendar checks passed; no external requests.\n";
} finally {
  $store->deleteAll(); foreach ($saved as $key => $value) { $store->set($key, $value); } $actor->delete(); $stack->pop(); new Settings($original); Drupal::getContainer()->set('http_client', $http); Drupal::getContainer()->set('tcl_bureau_oauth.connection', $connection);
}
