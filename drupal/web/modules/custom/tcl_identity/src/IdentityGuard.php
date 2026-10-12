<?php

declare(strict_types=1);

namespace Drupal\tcl_identity;

use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Restrict this instance to its TC client and preserve the authorization nonce. */
final class IdentityGuard implements EventSubscriberInterface {
  public function __construct(private KeyValueExpirableFactoryInterface $stores) {}

  public static function getSubscribedEvents(): array {
    return [KernelEvents::REQUEST => ['request', 20], KernelEvents::RESPONSE => ['response', 0]];
  }

  public function request(RequestEvent $event): void {
    if (!$event->isMainRequest()) { return; }
    $request = $event->getRequest();
    $route = $request->attributes->get('_route');
    if (!in_array($route, ['oauth2_token.authorize', 'oauth2_token.token', 'simple_oauth.userinfo', 'simple_oauth.jwks'], TRUE)) { return; }
    try {
      $config = IdentitySettings::load();
      $origin = parse_url($config['issuer']);
      $authority = $origin['scheme'] . '://' . $origin['host'] . (isset($origin['port']) ? ':' . $origin['port'] : '');
      if ($request->getSchemeAndHttpHost() !== $authority) {
        throw new \RuntimeException('identity_origin_invalid');
      }
      if (($config['local_recipe'] ?? FALSE) === TRUE && $request->getHost() !== '127.0.0.1') {
        throw new \RuntimeException('identity_local_only');
      }
      if ($route === 'oauth2_token.authorize') {
        if ($request->query->get('client_id') !== $config['client_id'] || $request->query->get('redirect_uri') !== $config['redirect_uri'] || $request->query->get('response_type') !== 'code' || $request->query->get('code_challenge_method') !== 'S256' || !is_string($challenge = $request->query->get('code_challenge')) || !preg_match('/\A[a-zA-Z0-9_-]{43}\z/D', $challenge) || !is_string($nonce = $request->query->get('nonce')) || !preg_match('/\A[a-f0-9]{64}\z/D', $nonce) || $request->query->get('scope') !== 'openid profile email') {
          throw new \RuntimeException('identity_authorize_invalid');
        }
      }
      if ($route === 'oauth2_token.token' && ($request->request->get('client_id') !== $config['client_id'] || $request->request->get('grant_type') !== 'authorization_code')) {
        throw new \RuntimeException('identity_token_invalid');
      }
    }
    catch (\Throwable) {
      $event->setResponse(new JsonResponse(['error' => 'invalid_request'], 400, ['Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex', 'Referrer-Policy' => 'no-referrer']));
    }
  }

  public function response(ResponseEvent $event): void {
    if (!$event->isMainRequest()) { return; }
    $request = $event->getRequest();
    $route = $request->attributes->get('_route');
    if (!in_array($route, ['oauth2_token.authorize', 'oauth2_token.token', 'simple_oauth.userinfo', 'simple_oauth.jwks'], TRUE)) { return; }
    $response = $event->getResponse();
    $response->headers->set('Cache-Control', 'no-store, private');
    $response->headers->set('X-Robots-Tag', 'noindex');
    $response->headers->set('Referrer-Policy', 'no-referrer');
    if ($route !== 'oauth2_token.authorize' || $response->getStatusCode() !== 302) { return; }
    try {
      $config = IdentitySettings::load();
      $location = $response->headers->get('Location', '');
      $parts = explode('?', $location, 2);
      if ($parts[0] !== $config['redirect_uri']) { return; }
      parse_str($parts[1] ?? '', $parameters);
      $code = $parameters['code'] ?? NULL;
      $nonce = $request->query->get('nonce');
      if (is_string($code) && $code !== '' && strlen($code) <= 4096 && is_string($nonce) && preg_match('/\A[a-f0-9]{64}\z/D', $nonce)) {
        $this->stores->get('tcl_identity_nonce')->setWithExpire(hash('sha256', $code), $nonce, 300);
      }
    }
    catch (\Throwable) {
      $event->setResponse(new JsonResponse(['error' => 'server_error'], 503, ['Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex', 'Referrer-Policy' => 'no-referrer']));
    }
  }
}
