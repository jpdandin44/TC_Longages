<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

/** Optional plain-text overrides, stored within the reviewed post revision. */
final class ChannelContent {
  public static function decode(?string $json): array {
    try { $data = json_decode($json ?: '{}', TRUE, 8, JSON_THROW_ON_ERROR); }
    catch (\JsonException) { throw new \InvalidArgumentException('Invalid channel text.'); }
    if (!is_array($data) || array_diff(array_keys($data), array_keys(CommunicationRepository::CHANNELS))) { throw new \InvalidArgumentException('Invalid channels.'); }
    foreach ($data as $values) {
      if (!is_array($values) || array_diff(array_keys($values), ['title', 'body'])) { throw new \InvalidArgumentException('Invalid channel fields.'); }
      foreach (['title' => 160, 'body' => 5000] as $key => $limit) {
        if (isset($values[$key]) && (!is_string($values[$key]) || mb_strlen($values[$key]) > $limit || ($key === 'title' && preg_match('/[\r\n]/', $values[$key])))) { throw new \InvalidArgumentException('Invalid channel value.'); }
      }
    }
    return $data;
  }
  public static function forChannel(array $row, string $channel): array {
    $values = self::decode($row['channel_texts'] ?? NULL)[$channel] ?? [];
    foreach (['title', 'body'] as $key) {
      if (trim($values[$key] ?? '') !== '') { $row[$key] = trim($values[$key]); }
    }
    return $row;
  }
}
