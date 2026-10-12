<?php

declare(strict_types=1);

// Inert example. Include a completed private copy from settings.php, outside web/.
// Generate TC keys and client secret on the TC host. Never copy AVEREO secrets.
$settings['tcl_identity'] = [
  'enabled' => FALSE,
  'issuer' => 'https://preprod.tclongages.fr/',
  'client_id' => 'tc-bureau-preproduction',
  'redirect_uri' => 'https://preprod.tclongages.fr/bureau/oauth/retour',
];
$settings['tcl_bureau_oauth'] = [
  'enabled' => FALSE,
  'issuer' => 'https://preprod.tclongages.fr/',
  'authorize_url' => 'https://preprod.tclongages.fr/oauth/authorize',
  'token_url' => 'https://preprod.tclongages.fr/oauth/token',
  'userinfo_url' => 'https://preprod.tclongages.fr/oauth/userinfo',
  'redirect_uri' => 'https://preprod.tclongages.fr/bureau/oauth/retour',
  'client_id' => 'tc-bureau-preproduction',
  'client_secret' => '',
  'public_key_path' => '',
  'allow_missing_nonce' => FALSE,
  'identities' => [
    // Explicit association of the local TC Admin: issuer + string uid => uid.
  ],
];
// $config['simple_oauth.settings']['private_key'] = '<private path outside web>';
// $config['simple_oauth.settings']['public_key'] = '<public key path outside web>';
