<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\tcl_bureau\TeamRepository;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class BureauController extends ControllerBase {

  public function __construct(private TeamRepository $repository) {}

  public static function create(ContainerInterface $container) {
    return new static($container->get('tcl_bureau.repository'));
  }

  public function access(AccountInterface $account): AccessResultInterface {
    return AccessResult::allowedIf($this->repository->allowed($account, 'access tcl bureau'))->setCacheMaxAge(0);
  }

  public function accountsAccess(AccountInterface $account): AccessResultInterface {
    return AccessResult::allowedIf($this->repository->canManageAccount($account))->setCacheMaxAge(0);
  }

  public function accountAccess(AccountInterface $account, UserInterface $user): AccessResultInterface {
    return AccessResult::allowedIf($this->repository->canManageAccount($account, $user))->setCacheMaxAge(0);
  }

  public function teamCreateAccess(AccountInterface $account): AccessResultInterface {
    return AccessResult::allowedIf($this->config('tcl_bureau.settings')->get('teams_enabled') && $this->repository->allowed($account, 'manage tcl teams'))->setCacheMaxAge(0);
  }

  public function teamAccess(AccountInterface $account, string $team): AccessResultInterface {
    return AccessResult::allowedIf($this->config('tcl_bureau.settings')->get('teams_enabled') && $this->repository->canEdit($account, (int) $team))->setCacheMaxAge(0);
  }

  public function entry(): RedirectResponse {
    $destination = $this->currentUser()->isAuthenticated()
      ? Url::fromRoute('tcl_bureau.home')
      : Url::fromRoute('user.login', [], ['query' => ['destination' => '/bureau']]);
    return new RedirectResponse($destination->toString());
  }

  public function home(): array {
    if (!$this->config('tcl_bureau.settings')->get('teams_enabled')) {
      if ($this->repository->allowed($this->currentUser(), 'manage tcl registrations')) {
        return \Drupal::service('class_resolver')->getInstanceFromDefinition(RegistrationController::class)->overview();
      }
      $build = $this->shell();
      $build['waiting'] = ['#plain_text' => 'La gestion des équipes sera proposée dans un prochain lot.'];
      return $build;
    }
    $actor = $this->currentUser();
    $manager = $this->repository->allowed($actor, 'manage tcl teams');
    $build = $this->shell();
    $build['welcome'] = ['#plain_text' => 'Connecté : ' . $actor->getAccountName() . '.'];
    $build['intro'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => $manager ? 'Gérez les équipes et les comptes Capitaine du club.' : 'Vous pouvez consulter et mettre à jour les notes de vos équipes.'];
    $build['heading'] = ['#type' => 'html_tag', '#tag' => 'h2', '#value' => $manager ? 'Équipes du club' : 'Mes équipes'];
    $rows = [];
    foreach ($this->repository->visible($actor) as $team) {
      $rows[] = [
        ['data' => ['#plain_text' => $team['name']]],
        ['data' => ['#plain_text' => $team['category']]],
        ['data' => ['#plain_text' => $team['notes']]],
        Link::fromTextAndUrl('Modifier', Url::fromRoute('tcl_bureau.team_edit', ['team' => $team['id']])),
      ];
    }
    $build['teams'] = ['#type' => 'container', '#attributes' => ['class' => ['tcl-table']], 'table' => [
      '#type' => 'table', '#header' => ['Équipe', 'Catégorie', 'Notes', 'Action'], '#rows' => $rows,
      '#empty' => $manager ? 'Aucune équipe créée.' : 'Aucune équipe ne vous est attribuée. Contactez le Bureau.',
    ]];
    return $build;
  }

  public function accounts(): array {
    $actor = $this->currentUser();
    $storage = $this->entityTypeManager()->getStorage('user');
    $ids = $storage->getQuery()->accessCheck(FALSE)->condition('roles', ['tcl_bureau', 'tcl_capitaine'], 'IN')->sort('name')->execute();
    $rows = [];
    foreach ($storage->loadMultiple($ids) as $user) {
      if (!$this->repository->canManageAccount($actor, $user)) {
        continue;
      }
      $rows[] = [
        ['data' => ['#plain_text' => $user->getAccountName()]],
        $user->hasRole('tcl_bureau') ? 'Bureau' : 'Capitaine',
        $user->isActive() ? 'Actif' : 'Bloqué',
        Link::fromTextAndUrl('Modifier', Url::fromRoute('tcl_bureau.account_edit', ['user' => $user->id()])),
      ];
    }
    $build = $this->shell();
    $build['explanation'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => 'Le Bureau gère les comptes Capitaine. La création d’un compte Bureau est réservée à l’administrateur. Bloquer un compte retire son accès.'];
    $build['add'] = Link::fromTextAndUrl('Créer un compte', Url::fromRoute('tcl_bureau.account_add'))->toRenderable();
    $build['accounts'] = ['#type' => 'container', '#attributes' => ['class' => ['tcl-table']], 'table' => ['#type' => 'table', '#header' => ['Identifiant', 'Rôle', 'État', 'Action'], '#rows' => $rows, '#empty' => 'Aucun compte à gérer.']];
    return $build;
  }

  private function shell(): array {
    $links = [
      'home' => Link::fromTextAndUrl('Espace du club', Url::fromRoute('tcl_bureau.home'))->toRenderable(),
      'site' => Link::fromTextAndUrl('Retour au site', Url::fromRoute('tcl_site.front'))->toRenderable(),
      'logout' => Link::fromTextAndUrl('Se déconnecter', Url::fromRoute('user.logout'))->toRenderable(),
    ];
    if ($this->repository->canManageAccount($this->currentUser())) {
      $links['accounts'] = Link::fromTextAndUrl('Gestion des comptes', Url::fromRoute('tcl_bureau.accounts'))->toRenderable();
    }
    if ($this->config('tcl_bureau.settings')->get('teams_enabled') && $this->repository->allowed($this->currentUser(), 'manage tcl teams')) {
      $links['team_add'] = Link::fromTextAndUrl('Créer une équipe', Url::fromRoute('tcl_bureau.team_add'))->toRenderable();
    }
    return [
      '#type' => 'container', '#attributes' => ['class' => ['tcl-bureau']], '#cache' => ['max-age' => 0],
      '#attached' => ['library' => ['tcl_bureau/bureau']],
      'navigation' => ['#type' => 'html_tag', '#tag' => 'nav', '#attributes' => ['aria-label' => 'Espace du club']] + $links,
    ];
  }

}
