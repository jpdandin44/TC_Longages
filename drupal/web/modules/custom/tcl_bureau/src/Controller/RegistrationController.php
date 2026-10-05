<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\tcl_bureau\RegistrationRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class RegistrationController extends ControllerBase {
  public function __construct(private RegistrationRepository $repository) {}

  public static function create(ContainerInterface $container) {
    return new static($container->get('tcl_bureau.registrations'));
  }

  public function access(AccountInterface $account) {
    return AccessResult::allowedIf($this->repository->allowed($account))->setCacheMaxAge(0);
  }

  public function overview(): array {
    $search = (string) \Drupal::request()->query->get('recherche', '');
    $rows = [];
    foreach ($this->repository->list($this->currentUser(), $search) as $record) {
      $rows[] = [
        ['data' => ['#plain_text' => $record['first_name'] . ' ' . $record['last_name']]],
        $record['season'],
        RegistrationRepository::STATUSES[$record['status']],
        Link::fromTextAndUrl('Ouvrir le dossier', Url::fromRoute('tcl_bureau.registration_edit', ['registration' => $record['id']])),
      ];
    }
    return [
      '#type' => 'container', '#attributes' => ['class' => ['tcl-bureau']], '#cache' => ['max-age' => 0],
      '#attached' => ['library' => ['tcl_bureau/bureau']],
      'navigation' => ['#type' => 'html_tag', '#tag' => 'nav', '#attributes' => ['aria-label' => 'Espace Bureau'],
        'site' => Link::fromTextAndUrl('Retour au site', Url::fromRoute('tcl_site.front'))->toRenderable(),
        'accounts' => Link::fromTextAndUrl('Gestion des comptes', Url::fromRoute('tcl_bureau.accounts'))->toRenderable(),
        'logout' => Link::fromTextAndUrl('Se déconnecter', Url::fromRoute('user.logout'))->toRenderable(),
      ],
      'title' => ['#type' => 'html_tag', '#tag' => 'h2', '#value' => 'Nouveaux adhérents'],
      'intro' => ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'Enregistrez les fiches reçues, vérifiez les coordonnées et suivez les dossiers à compléter.'],
      'add' => Link::fromTextAndUrl('Enregistrer un nouvel adhérent', Url::fromRoute('tcl_bureau.registration_add'))->toRenderable(),
      'search' => ['#type' => 'html_tag', '#tag' => 'form', '#attributes' => ['method' => 'get', 'action' => Url::fromRoute('tcl_bureau.registrations')->toString()],
        'label' => ['#type' => 'html_tag', '#tag' => 'label', '#attributes' => ['for' => 'tcl-member-search'], '#value' => 'Rechercher par nom ou prénom'],
        'recherche' => ['#type' => 'html_tag', '#tag' => 'input', '#attributes' => ['type' => 'text', 'name' => 'recherche', 'id' => 'tcl-member-search', 'value' => $search, 'maxlength' => 120]],
        'submit' => ['#type' => 'html_tag', '#tag' => 'button', '#attributes' => ['type' => 'submit'], '#value' => 'Rechercher'],
      ],
      'records' => ['#type' => 'container', '#attributes' => ['class' => ['tcl-table']], 'table' => [
        '#type' => 'table', '#header' => ['Adhérent', 'Saison', 'Dossier', 'Action'], '#rows' => $rows, '#empty' => 'Aucun dossier enregistré pour cette recherche.',
      ]],
      'limit' => ['#type' => 'html_tag', '#tag' => 'p', '#attributes' => ['class' => ['tcl-note']], '#value' => 'Enregistrer un dossier ne crée pas de licence FFT et ne déclenche aucun paiement ni courriel. Les 100 dossiers les plus récents correspondant à la recherche sont affichés.'],
    ];
  }
}
