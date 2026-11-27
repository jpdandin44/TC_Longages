<?php

declare(strict_types=1);
namespace Drupal\tcl_bureau\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tcl_bureau\LessonRepository;
use Drupal\tcl_bureau\LessonSchedule;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class LessonActionForm extends FormBase {

  public function __construct(protected LessonRepository $lessons) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('tcl_bureau.lessons'));
  }

  public function getFormId(): string {
    return 'tcl_lesson_action';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $group = '', string $date = '', string $action = ''): array {
    $row = $this->lessons->get($this->currentUser(), $group);
    $session = $row['sessions'][$date] ?? throw new NotFoundHttpException();
    $form['#cache']['max-age'] = 0;
    $form['#attributes']['class'] = ['tcl-bureau'];
    $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['back'] = ['#type' => 'link', '#title' => 'Retour à la séance', '#url' => Url::fromRoute('tcl_bureau.lesson_session', ['group' => $group, 'date' => $date])];
    $heading = LessonSchedule::ACTIONS[$action] . ' · ' . $row['name'] . ' · ' . (new \DateTimeImmutable($date))->format('d/m/Y');
    $form['heading'] = ['#type' => 'html_tag', '#tag' => 'h2', 'text' => ['#plain_text' => $heading]];
    if (!$this->getRequest()->isMethod('POST') && !in_array($action, LessonSchedule::available($session, $this->lessons->now()), TRUE)) {
      $form['notice'] = ['#plain_text' => 'Cette action n’est plus disponible pour la séance.'];
      return $form;
    }
    $form_state->set('lesson_action', [$group, $date, $action]);
    $form['revision'] = ['#type' => 'hidden', '#default_value' => $row['revision']];
    if ($action === 'reschedule') {
      $form['new_date'] = ['#type' => 'date', '#title' => 'Date de rattrapage', '#required' => TRUE, '#attributes' => ['max' => $row['season_end']]];
      $previous = array_values(array_filter($session['attempts'], static fn(array $attempt): bool => $attempt['start'] !== NULL));
      $slot = $previous ? end($previous) : NULL;
      foreach (['start' => 'Heure de début', 'end' => 'Heure de fin'] as $field => $label) {
        $form[$field] = ['#type' => 'textfield', '#title' => $label . ' (HH:MM)', '#default_value' => $slot ? (new \DateTimeImmutable($slot[$field]))->format('H:i') : '', '#maxlength' => 5, '#size' => 8, '#required' => TRUE, '#attributes' => ['pattern' => '[0-9]{2}:[0-9]{2}']];
      }
      $form['court'] = ['#type' => 'textfield', '#title' => 'Court', '#default_value' => $slot['court'] ?? $row['court'], '#maxlength' => 100, '#required' => TRUE];
    }
    if (in_array($action, ['cancel', 'close', 'reopen'], TRUE)) {
      $form['reason'] = ['#type' => 'textarea', '#title' => 'Motif interne', '#maxlength' => 300, '#rows' => 3, '#required' => TRUE];
    }
    $form['actions'] = ['#type' => 'actions',
      'submit' => ['#type' => 'submit', '#value' => LessonSchedule::ACTIONS[$action]],
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    [$group, $date, $action] = $form_state->get('lesson_action');
    $input = ['date' => trim((string) $form_state->getValue('new_date', ''))];
    foreach (['start', 'end', 'court', 'reason'] as $field) {
      $input[$field] = trim((string) $form_state->getValue($field, ''));
    }
    try {
      $this->lessons->change($this->currentUser(), $group, $date, (int) $form_state->getValue('revision'), $action, $input);
    }
    catch (ConflictHttpException | \InvalidArgumentException $error) {
      $this->messenger()->addError($error->getMessage());
      $form_state->setRebuild();
      return;
    }
    $this->messenger()->addStatus('Le suivi de la séance a été mis à jour.');
    $form_state->setRedirect('tcl_bureau.lesson_session', ['group' => $group, 'date' => $date]);
  }

}
