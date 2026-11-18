<?php
namespace Drupal\tcl_post_fixture;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;

/** Transport fixture, installed only in a disposable SQLite checkout. */
final class LocalClient {
  public static function create(): Client {
    $db = \Drupal\Core\Database\Database::getConnection()->getConnectionOptions();
    if ($db['driver'] !== 'sqlite' || !str_contains(str_replace('\\', '/', $db['database']), '/.local/drupal-runtime/')) {
      throw new \RuntimeException('Test fixture refuses hosted databases.');
    }
    $root = dirname($db['database'], 2);
    $handler = static function ($request, $options) use ($root) {
      $uri = $request->getUri();
      if ($uri->getHost() === '127.0.0.1' && $uri->getPort() === 4190) {
        return (new CurlHandler())($request, $options);
      }
      $key = $request->getMethod() . ' ' . $uri->getHost() . $uri->getPath();
      file_put_contents($root . '/post-fixture-calls.jsonl', json_encode(['request' => $key]) . "\n", FILE_APPEND);
      $mode = trim(@file_get_contents($root . '/post-fixture-mode.txt') ?: 'success');
      $data = match ($key) {
        'POST oauth2.googleapis.com/token' => ['access_token' => 'fixture-access'],
        'GET people.googleapis.com/v1/contactGroups/fixture' => ['name' => $mode === 'wrong-group' ? 'Other' : 'Adhérents_2026_2027', 'memberCount' => 3, 'memberResourceNames' => ['people/a','people/b','people/c']],
        'GET people.googleapis.com/v1/people:batchGet' => ['responses' => [
          ['person' => ['emailAddresses' => [['value' => ' Person@example.invalid ']]]],
          ['person' => ['emailAddresses' => [['value' => 'person@example.invalid']]]],
          ['person' => ['emailAddresses' => [['value' => 'invalid']]]],
        ]],
        default => throw new \RuntimeException('Outbound request blocked by local fixture.'),
      };
      return Create::promiseFor(new Response($mode === 'unavailable' ? 503 : 200, ['Content-Type'=>'application/json'], json_encode($data)));
    };
    return new Client(['handler' => HandlerStack::create($handler)]);
  }
}
