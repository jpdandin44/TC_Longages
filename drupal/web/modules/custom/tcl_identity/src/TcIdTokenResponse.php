<?php

declare(strict_types=1);

namespace Drupal\tcl_identity;

use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\simple_oauth\OpenIdConnect\OpenIdConnectIdTokenResponse;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use OpenIDConnectServer\ClaimExtractor;
use OpenIDConnectServer\Repositories\IdentityProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/** Signed TC issuer and nonce, added after Simple OAuth validates the code/PKCE. */
final class TcIdTokenResponse extends OpenIdConnectIdTokenResponse {
  public function __construct(IdentityProviderInterface $identities, ClaimExtractor $claims, LanguageManagerInterface $languages, private RequestStack $requests, private KeyValueExpirableFactoryInterface $stores) {
    parent::__construct($identities, $claims, $languages);
  }

  protected function getBuilder(AccessTokenEntityInterface $accessToken, UserEntityInterface $userEntity) {
    $config = IdentitySettings::load();
    if ($accessToken->getClient()->getIdentifier() !== $config['client_id']) {
      throw new \RuntimeException('identity_client_invalid');
    }
    $code = $this->requests->getCurrentRequest()->request->get('code');
    if (!is_string($code) || $code === '' || strlen($code) > 4096) {
      throw new \RuntimeException('identity_code_invalid');
    }
    $store = $this->stores->get('tcl_identity_nonce');
    $key = hash('sha256', $code);
    $nonce = $store->get($key);
    $store->delete($key);
    if (!is_string($nonce) || !preg_match('/\A[a-f0-9]{64}\z/D', $nonce)) {
      throw new \RuntimeException('identity_nonce_invalid');
    }
    return parent::getBuilder($accessToken, $userEntity)->issuedBy($config['issuer'])->withClaim('nonce', $nonce);
  }
}
