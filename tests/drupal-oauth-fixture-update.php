<?php
declare(strict_types=1);
require dirname(__DIR__) . '/drupal/bin/local-bootstrap.php';
$fixtures = json_decode(file_get_contents(dirname(__DIR__) . '/.local/bureau-oauth-fixtures.json'), TRUE, 32, JSON_THROW_ON_ERROR);
$user = \Drupal\user\Entity\User::load($fixtures['bureau']['uid']);
if ($user->getAccountName() !== 'oauth-local-bureau') { throw new RuntimeException('Fixture identity mismatch.'); }
$settingsFile = dirname(__DIR__) . '/.local/bureau-oauth-settings.php';
$configuration = (static function ($path): array { $settings = []; require $path; return $settings['tcl_bureau_oauth']; })($settingsFile);
$rewrite = static function (array $configuration) use ($settingsFile): void {
  file_put_contents($settingsFile, '<?php $settings[\'tcl_bureau_oauth\'] = ' . var_export($configuration, TRUE) . ';' . "\n");
};
switch ($argv[1] ?? '') {
  case 'block': $user->block()->save(); break;
  case 'unblock': $user->activate()->save(); break;
  case 'remove-role': $user->removeRole('tcl_bureau')->save(); break;
  case 'restore-role': $user->addRole('tcl_bureau')->save(); break;
  case 'disable': $configuration['enabled'] = FALSE; $rewrite($configuration); break;
  case 'revoke-identity': $configuration['identities'] = array_values(array_filter($configuration['identities'], static fn($identity) => $identity['uid'] !== (int) $user->id())); $rewrite($configuration); break;
  case 'restore-settings': $rewrite(json_decode(file_get_contents(dirname(__DIR__) . '/.local/oauth-fixture-provider.json'), TRUE, 32, JSON_THROW_ON_ERROR)); break;
  case 'expire-idle':
  case 'expire-absolute':
    $field = $argv[1] === 'expire-idle' ? 'last' : 'until';
    foreach (\Drupal::database()->select('sessions', 's')->fields('s', ['sid', 'session'])->condition('uid', $user->id())->execute()->fetchAll() as $row) {
      if (str_contains($row->session, 'tcl_bureau_oauth_proof')) {
        $session = preg_replace('/s:' . strlen($field) . ':"' . $field . '";i:[0-9]+;/', 's:' . strlen($field) . ':"' . $field . '";i:' . (time() - 3600) . ';', $row->session, 1, $count);
        if ($count !== 1) { throw new RuntimeException('Fixture session format changed.'); }
        \Drupal::database()->update('sessions')->fields(['session' => $session])->condition('sid', $row->sid)->execute();
      }
    }
    break;
  default: throw new RuntimeException('Unknown fixture action.');
}
drupal_flush_all_caches();
echo "Local fictitious account updated.\n";
