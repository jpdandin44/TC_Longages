<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

use Drupal\Core\Site\Settings;

/** Read only one explicitly configured Google contact group; no directory import. */
final class MemberContacts {
  public static function configured(): bool {
    $s = Settings::get('tcl_member_contacts', []);
    return SeasonCatalogue::recipeEnabled() && ($s['enabled'] ?? FALSE) === TRUE
      && !empty($s['client_id']) && !empty($s['client_secret']) && !empty($s['refresh_token'])
      && preg_match('/^contactGroups\/[a-zA-Z0-9_-]+$/D', $s['group'] ?? '') === 1;
  }
  public static function recipients(): array {
    if (!self::configured()) { throw new \RuntimeException('La connexion Google Contacts et le libellé adhérents restent à configurer.'); }
    $s = Settings::get('tcl_member_contacts'); $client = \Drupal::httpClient();
    try {
      $token = json_decode((string) $client->post('https://oauth2.googleapis.com/token', ['timeout' => 15, 'allow_redirects' => FALSE, 'form_params' => ['grant_type' => 'refresh_token', 'client_id' => $s['client_id'], 'client_secret' => $s['client_secret'], 'refresh_token' => $s['refresh_token']]])->getBody(), TRUE, 32, JSON_THROW_ON_ERROR);
      if (empty($token['access_token'])) { throw new \RuntimeException(); }
      $options = ['timeout' => 15, 'allow_redirects' => FALSE, 'headers' => ['Authorization' => 'Bearer ' . $token['access_token']]];
      $group = json_decode((string) $client->get('https://people.googleapis.com/v1/' . $s['group'] . '?maxMembers=501&groupFields=name,memberCount', $options)->getBody(), TRUE, 32, JSON_THROW_ON_ERROR);
      $resources = $group['memberResourceNames'] ?? [];
      if (($group['memberCount'] ?? 0) > 500 || count($resources) > 500 || count($resources) !== (int) ($group['memberCount'] ?? 0)) { throw new \RuntimeException(); }
      $people = [];
      foreach (array_chunk($resources, 200) as $chunk) {
        foreach ($chunk as $resource) { if (!preg_match('/^people\/[a-zA-Z0-9_-]+$/D', $resource)) { throw new \RuntimeException(); } }
        $query = 'personFields=names,emailAddresses&' . implode('&', array_map(static fn($v) => 'resourceNames=' . rawurlencode($v), $chunk));
        $batch = json_decode((string) $client->get('https://people.googleapis.com/v1/people:batchGet?' . $query, $options)->getBody(), TRUE, 64, JSON_THROW_ON_ERROR);
        if (count($batch['responses'] ?? []) !== count($chunk)) { throw new \RuntimeException(); }
        foreach ($batch['responses'] as $response) {
          if (!empty($response['status']['code']) || !isset($response['person'])) { throw new \RuntimeException(); }
          $people[] = $response['person'];
        }
      }
      return self::normalize($people) + ['group' => $group['name'] ?? 'Adhérents'];
    } catch (\Throwable) {
      // Do not surface tokens, provider responses or contact payloads in errors/logs.
      throw new \RuntimeException('Lecture Google Contacts impossible ou groupe incomplet. Aucun envoi préparé.');
    }
  }
  public static function normalize(array $people): array {
    $recipients = []; $excluded = 0;
    foreach ($people as $person) {
      $emails = $person['emailAddresses'] ?? [];
      // One address per contact: prefer the primary email, otherwise the first.
      usort($emails, static fn($a, $b) => (int) ($b['metadata']['primary'] ?? FALSE) <=> (int) ($a['metadata']['primary'] ?? FALSE));
      $email = strtolower(trim((string) ($emails[0]['value'] ?? '')));
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $excluded++; continue; }
      $recipients[$email] = ['email' => $email, 'name' => mb_substr((string) ($person['names'][0]['displayName'] ?? ''), 0, 100)];
    }
    ksort($recipients);
    return ['recipients' => array_values($recipients), 'excluded' => $excluded];
  }
}
