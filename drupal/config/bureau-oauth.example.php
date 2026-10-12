<?php

declare(strict_types=1);

// Copy outside web/, fill in the dedicated TC client through a private channel,
// and include from settings.php. This inert example grants nobody access.
$settings['tcl_bureau_oauth'] = [
  'enabled' => FALSE,
  'issuer' => '',
  'authorize_url' => '',
  'token_url' => '',
  'userinfo_url' => '',
  'redirect_uri' => 'https://preprod.tclongages.fr/bureau/oauth/retour',
  'client_id' => '',
  'client_secret' => '',
  'public_key_path' => '',
  'allow_missing_nonce' => FALSE,
  'identities' => [
    // ['issuer' => '<exact issuer>', 'subject' => '<stable sub>', 'uid' => <local Drupal uid>],
  ],
];
