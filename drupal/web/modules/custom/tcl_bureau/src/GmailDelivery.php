<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau;

use GuzzleHttp\ClientInterface;

/** One Gmail request per recipient; uncertain outcomes are never retried. */
final class GmailDelivery {
  public static function raw(string $to, string $subject, string $text, string $html): string {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $to . $subject) || strlen($subject) > 998) { throw new \InvalidArgumentException('Adresse ou objet invalide.'); }
    $mime = MemberMail::multipart(['headers' => []], $text, $html);
    $headers = ['From' => 'Tennis Club de Longages <' . GmailConnection::ACCOUNT . '>', 'Reply-To' => GmailConnection::ACCOUNT, 'To' => $to, 'Subject' => mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n", 9), 'Date' => gmdate('D, d M Y H:i:s') . ' +0000', 'Message-ID' => '<tcl-' . bin2hex(random_bytes(16)) . '@tclongages.fr>', 'MIME-Version' => '1.0'] + $mime['headers'];
    $lines = []; foreach ($headers as $name => $value) { $lines[] = $name . ': ' . $value; }
    return rtrim(strtr(base64_encode(implode("\r\n", $lines) . "\r\n\r\n" . $mime['body']), '+/', '-_'), '=');
  }
  public static function deliver(ClientInterface $client, string $token, string $raw): array {
    try {
      $response = $client->request('POST', 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send', ['timeout' => 25, 'allow_redirects' => FALSE, 'http_errors' => FALSE, 'headers' => ['Authorization' => 'Bearer ' . $token], 'json' => ['raw' => $raw]]);
      $code = $response->getStatusCode();
      if ($code >= 400 && $code < 500) { return ['status' => 'failed', 'transport' => 'gmail', 'http_status' => $code]; }
      $result = json_decode((string) $response->getBody(), TRUE, 32, JSON_THROW_ON_ERROR);
      if ($code >= 200 && $code < 300 && preg_match('/^[a-zA-Z0-9_-]{1,128}$/D', $result['id'] ?? '')) { return ['status' => 'accepted', 'transport' => 'gmail', 'message_id' => $result['id']]; }
    } catch (\Throwable) { /* Do not expose tokens or personal response bodies. */ }
    return ['status' => 'pending', 'transport' => 'gmail'];
  }
  public static function send(string $to, string $subject, string $text, string $html, string $token): array {
    if (!GmailConnection::enabled() || (!MemberContacts::production() && !RecipeMail::allowed($to))) { throw new \RuntimeException('Destinataire Gmail non autorisé dans cet environnement.'); }
    $subject = (MemberContacts::production() ? '' : '[RECETTE PRÉPRODUCTION] ') . $subject;
    return self::deliver(\Drupal::httpClient(), $token, self::raw($to, $subject, $text, $html));
  }
}
