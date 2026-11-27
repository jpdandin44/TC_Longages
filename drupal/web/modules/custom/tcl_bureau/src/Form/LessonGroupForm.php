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

final class LessonGroupForm extends FormBase {

  public function __construct(protected LessonRepository $lessons) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('tcl_bureau.lessons'));
  }

  public function getFormId(): string {
    return 'tcl_lesson_group';
  }

  public function buildForm(array $form, FormStateInterface $state): array {
    $now = (new \DateTimeImmutable('@' . $this->lessons->now()))->setTimezone(new \DateTimeZone('Europe/Paris'));
    $form['#cache']['max-age'] = 0;
    $form['#attributes']['class'] = ['tcl-bureau'];
    $form['#attached']['library'][] = 'tcl_bureau/bureau';
    $form['back'] = ['#type' => 'link', '#title' => 'Retour aux cours de tennis', '#url' => Url::fromRoute('tcl_bureau.lessons')];
    $form['submission_id'] = ['#type' => 'hidden', '#default_value' => bin2hex(random_bytes(16))];
    foreach (['name' => 'Nom du groupe', 'coach' => 'Enseignant', 'court' => 'Court habituel'] as $field => $label) {
      $form[$field] = ['#type' => 'textfield', '#title' => $label, '#maxlength' => 100, '#required' => TRUE];
    }
    $form['season'] = [
      '#type' => 'number', '#title' => 'Année de début de saison', '#required' => TRUE,
      '#default_value' => (int) $now->format('Y') - ((int) $now->format('n') < 9 ? 1 : 0),
      '#min' => 2000, '#max' => 2098, '#description' => 'Du 1er septembre au 31 août de l’année suivante.',
    ];
    $form['goal'] = ['#type' => 'number', '#title' => 'Objectif de cours sur la saison', '#required' => TRUE, '#min' => 1, '#max' => 999];
    $form['date'] = ['#type' => 'date', '#title' => 'Première séance suivie', '#required' => TRUE];
    $form['until'] = ['#type' => 'date', '#title' => 'Dernière date de la répétition hebdomadaire', '#required' => TRUE];
    foreach (['start' => 'Heure de début', 'end' => 'Heure de fin'] as $field => $label) {
      $form[$field] = ['#type' => 'textfield', '#title' => $label . ' (HH:MM)', '#maxlength' => 5, '#size' => 8, '#required' => TRUE, '#attributes' => ['placeholder' => '18:30', 'pattern' => '[0-9]{2}:[0-9]{2}']];
    }
    $form['excluded'] = ['#type' => 'textarea', '#title' => 'Dates exclues dès la planification', '#rows' => 3, '#maxlength' => 5000, '#description' => 'Une date par ligne (AAAA-MM-JJ), par exemple les vacances.'];
    $form['baseline'] = ['#type' => 'number', '#title' => 'Cours assurés avant la première séance suivie', '#min' => 0, '#max' => 999, '#default_value' => 0, '#description' => 'Reportez le bilan validé avec l’enseignant en cas de reprise en cours de saison.'];
    $form['prior_cancelled'] = ['#type' => 'textarea', '#title' => 'Annulations antérieures à reprendre', '#rows' => 3, '#maxlength' => 4000, '#description' => 'Une date passée de la saison par ligne (AAAA-MM-JJ). Elles rejoignent les rattrapages à programmer.'];
    $form['actions'] = ['#type' => 'actions',
      'submit' => ['#type' => 'submit', '#value' => 'Créer le groupe'],
    ];
    return $form;
  }

  private function input(FormStateInterface $state): array {
    $values = [];
    foreach (['name', 'coach', 'court', 'season', 'goal', 'date', 'until', 'start', 'end', 'excluded', 'baseline', 'prior_cancelled'] as $field) {
      $values[$field] = trim((string) $state->getValue($field, ''));
    }
    $values['baseline'] = $values['baseline'] === '' ? '0' : $values['baseline'];
    return $values;
  }

  public function validateForm(array &$form, FormStateInterface $state): void {
    $prepared = LessonSchedule::creation($this->input($state), $this->lessons->now(), (int) $this->currentUser()->id());
    foreach ($prepared['errors'] as $field => $error) {
      $state->setErrorByName($field, $error);
    }
  }

  public function submitForm(array &$form, FormStateInterface $state): void {
    $id = (string) $state->getValue('submission_id');
    try {
      $this->lessons->create($this->currentUser(), $id, $this->input($state));
    }
    catch (ConflictHttpException | \InvalidArgumentException $error) {
      $this->messenger()->addError($error->getMessage());
      $state->setRebuild();
      return;
    }
    $this->messenger()->addStatus('Le groupe et ses séances ont été enregistrés.');
    $state->setRedirect('tcl_bureau.lesson_group', ['group' => $id]);
  }

}
