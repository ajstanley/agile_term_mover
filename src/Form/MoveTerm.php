<?php

namespace Drupal\agile_term_mover\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class MoveTerm.
 */
class MoveTerm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'move_term';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $vocabularies = Vocabulary::loadMultiple();
    $vocab_options = [];
    foreach ($vocabularies as $vocabulary) {
      $vocab_options[$vocabulary->get('vid')] = $vocabulary->get('name');
    }
    $form['current_vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Current Vocabulary'),
      '#description' => $this->t('Current vocabulary'),
      '#options' => $vocab_options,
      '#weight' => '0',
    ];
    $form['destination_vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination Vocabulary'),
      '#description' => $this->t('Vocabulary to which term is being moved '),
      '#options' => $vocab_options,
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($values['current_vocabulary']);
    $count = count($terms);
    foreach ($terms as $candidate) {
      $term = Term::load($candidate->tid);
      $term->vid = $values['destination_vocabulary'];
      $term->save();
    }
    $old = Vocabulary::load($values['current_vocabulary']);
    $new =  Vocabulary::load($values['destination_vocabulary']);
    \Drupal::messenger()
      ->addMessage("Moved $count terms from  {$old->get('name')} to {$new->get('name')}");
  }
}
