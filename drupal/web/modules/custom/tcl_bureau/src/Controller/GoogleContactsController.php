<?php
declare(strict_types=1);
namespace Drupal\tcl_bureau\Controller;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\tcl_bureau\GoogleContactsConnection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
final class GoogleContactsController extends ControllerBase {
  public function access(AccountInterface $account) { return AccessResult::allowedIf(GoogleContactsConnection::allowed($account))->setCacheMaxAge(0); }
  public function callback(Request $request): RedirectResponse {
    try { $count = GoogleContactsConnection::finish($this->currentUser(), (string) $request->query->get('state', ''), (string) $request->query->get('code', '')); $this->messenger()->addStatus('Google Contacts connecté : ' . $count . ' contacts dans le libellé. Aucun courriel envoyé.'); }
    catch (\RuntimeException $e) { $this->messenger()->addError($e->getMessage()); }
    $response = new RedirectResponse(Url::fromRoute('tcl_bureau.google_contacts')->toString());
    $response->headers->set('Cache-Control', 'no-store'); $response->headers->set('Referrer-Policy', 'no-referrer'); return $response;
  }
}
