<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tcl_bureau\CommunicationRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CommunicationActionForm extends ConfirmFormBase {
  private int $postId;
  private string $action;
  private const LABELS = ['validate' => 'Valider cette actualité', 'publish' => 'Publier sur le site', 'withdraw' => 'Retirer du site', 'archive' => 'Archiver cette actualité', 'restore' => 'Restaurer en brouillon'];

  public function __construct(private CommunicationRepository $repository) {}
  public static function create(ContainerInterface $container) { return new static($container->get('tcl_bureau.communication')); }
  public function getFormId(): string { return 'tcl_bureau_communication_action'; }
  public function getQuestion() { return self::LABELS[$this->action]; }
  public function getConfirmText() { return self::LABELS[$this->action]; }
  public function getCancelUrl(): Url { return $this->action === 'restore' ? Url::fromRoute('tcl_bureau.communication_archives') : Url::fromRoute('tcl_bureau.communication_review', ['post' => $this->postId]); }

  public function buildForm(array $form, FormStateInterface $form_state, ?string $post = NULL, ?string $action = NULL): array {
    $this->postId = (int) $post;
    $this->action = (string) $action;
    $row = $this->repository->get($this->currentUser(), $this->postId);
    if (!$row || !isset(self::LABELS[$this->action]) || ($row['status'] === 'archived' && $action !== 'restore')) { throw new NotFoundHttpException(); }
    $form = parent::buildForm($form, $form_state);
    $form['#attributes']['class'][] = 'tcl-bureau';
    $form['#attached']['library'][] = 'tcl_bureau/communication';
    $form['#cache']['max-age'] = 0;
    $form['description'] = ['#plain_text' => $row['title'] . ' — Révision ' . $row['revision'] . '. ' . ($action === 'publish' ? 'Cette actualité deviendra visible par les visiteurs de ce site.' : ($action === 'archive' ? 'L’actualité sera retirée du site et conservée en archive.' : 'Aucun envoi automatique vers un service externe.'))];
    $form['revision'] = ['#type' => 'hidden', '#default_value' => $row['revision']];
    $form['content'] = ['#type' => 'fieldset', '#title' => 'Contenu de cette révision', 'body' => ['#type' => 'container', '#attributes' => ['class' => ['tcl-post-body']], 'text' => ['#plain_text' => $row['body']]], 'link' => ['#plain_text' => $row['link']], 'tenup' => ['#plain_text' => 'Visible sur Ten’Up : ' . ($row['tenup_visible'] === 'yes' ? 'Oui' : 'Non')]];
    if ($row['image_data'] !== '') {
      $form['content']['image'] = ['#theme' => 'image', '#uri' => Url::fromRoute('tcl_bureau.communication_image', ['post' => $this->postId], ['query' => ['v' => $row['revision']]])->toString(), '#alt' => $row['image_alt'], '#attributes' => ['class' => ['tcl-post-image']]];
    }
    $form['personal_confirmation'] = ['#type' => 'checkbox', '#title' => 'Je confirme personnellement cette action sur cette révision.', '#required' => TRUE, '#required_error' => 'Votre confirmation personnelle est obligatoire.'];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try { $this->repository->transition($this->currentUser(), $this->postId, (int) $form_state->getValue('revision'), $this->action); }
    catch (ConflictHttpException $error) { $this->messenger()->addError($error->getMessage()); $form_state->setRedirect('tcl_bureau.communication_review', ['post' => $this->postId]); return; }
    $this->messenger()->addStatus('L’action est enregistrée.');
    $form_state->setRedirect($this->action === 'archive' ? 'tcl_bureau.communication' : 'tcl_bureau.communication_review', $this->action === 'archive' ? [] : ['post' => $this->postId]);
  }
}
