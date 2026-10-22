<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau\Controller;
use Drupal\tcl_bureau\EventParticipation as Replies;
use Drupal\tcl_bureau\SeasonCatalogue;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** Same private fragment/POST pattern as the local team response module. */
final class EventResponseController {
  private static function headers(): array { return ['Cache-Control' => 'private, no-store', 'Referrer-Policy' => 'no-referrer', 'X-Robots-Tag' => 'noindex, nofollow', 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'self'; script-src 'self'; style-src 'self'; connect-src 'self'; img-src 'self'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'"]; }
  public function page(): Response {
    if (!SeasonCatalogue::recipeEnabled()) { throw new AccessDeniedHttpException(); }
    $assets = dirname(__DIR__, 2);
    $scriptVersion = substr(hash_file('sha256', $assets . '/js/event-response.js'), 0, 16);
    $styleVersion = substr(hash_file('sha256', $assets . '/css/event-response.css'), 0, 16);
    $options = '';
    for ($i = 1; $i <= 99; $i++) { $options .= '<option value="' . $i . '">' . $i . '</option>'; }
    return new Response('<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ma participation — TC Longages</title><link rel="stylesheet" href="/modules/custom/tcl_bureau/css/event-response.css?v=' . $styleVersion . '"></head><body><main><img src="/modules/custom/tcl_bureau/images/logo.jpeg" width="60" height="60" alt="TC Longages"><p>RECETTE — participation fictive</p><h1>Ma participation</h1><p id="reply-status" role="status">Ouverture du lien…</p><section id="reply-context" hidden><h2 id="reply-title"></h2><p id="reply-details"></p><p id="reply-name"></p><form id="reply-form"><fieldset><legend>Votre réponse</legend><label><input type="radio" name="answer" value="yes" required> Je participe</label><label><input type="radio" name="answer" value="no"> Je ne participe pas</label><label><input type="radio" name="answer" value="maybe"> Je ne sais pas encore</label></fieldset><div id="reply-count"><label for="count">Nombre total de personnes, vous compris</label><select id="count" name="count">' . $options . '</select></div><button>Confirmer ma réponse</button></form><button id="refresh" type="button">Actualiser la réponse</button></section></main><script src="/modules/custom/tcl_bureau/js/event-response.js?v=' . $scriptVersion . '" defer></script></body></html>', 200, self::headers() + ['Content-Type' => 'text/html; charset=UTF-8']);
  }
  public function api(string $action): JsonResponse {
    $r = \Drupal::request(); $headers = self::headers();
    if (!SeasonCatalogue::recipeEnabled() || $r->headers->get('Origin') !== $r->getSchemeAndHttpHost() || !str_starts_with(strtolower($r->headers->get('Content-Type', '')), 'application/json') || strlen($r->getContent()) > 2048) { return new JsonResponse(['error' => 'Requête refusée.'], 403, $headers); }
    $flood = \Drupal::service('flood'); $ip = hash('sha256', (string) $r->getClientIp());
    if (!$flood->isAllowed('tcl.event.reply', 150, 3600, $ip)) { return new JsonResponse(['error' => 'Trop de tentatives. Réessayez plus tard.'], 429, $headers); }
    $flood->register('tcl.event.reply', 3600, $ip);
    try {
      $in = json_decode($r->getContent(), TRUE, 8, JSON_THROW_ON_ERROR);
      if (!is_array($in) || !is_string($in['token'] ?? NULL)) { throw new \InvalidArgumentException(); }
      $r->getSession()->start();
      if ($action === 'context') { Replies::context($in['token']); $r->getSession()->set('tcl_event_reply', TRUE); }
      if ($action === 'save') {
        if (!is_string($in['csrf'] ?? NULL) || !\Drupal::csrfToken()->validate($in['csrf'], 'tcl.event.reply')) { throw new AccessDeniedHttpException(); }
        foreach (['count', 'revision', 'eventRevision'] as $key) { if (!is_int($in[$key] ?? NULL)) { throw new \InvalidArgumentException(); } }
        if (!is_string($in['answer'] ?? NULL)) { throw new \InvalidArgumentException(); }
        Replies::respond($in['token'], $in['answer'], $in['count'], $in['revision'], $in['eventRevision']);
      }
      return new JsonResponse(['context' => Replies::context($in['token']), 'csrf' => \Drupal::csrfToken()->get('tcl.event.reply')], 200, $headers);
    } catch (ConflictHttpException $e) { return new JsonResponse(['error' => $e->getMessage()], 409, $headers); }
    catch (AccessDeniedHttpException) { return new JsonResponse(['error' => 'Lien expiré ou remplacé, réponses closes ou événement indisponible. Contactez le Bureau.'], 403, $headers); }
    catch (\InvalidArgumentException | \JsonException | \TypeError) { return new JsonResponse(['error' => 'Réponse invalide. Vérifiez le nombre de personnes.'], 400, $headers); }
  }
}
