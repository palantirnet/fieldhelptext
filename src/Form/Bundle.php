<?php

namespace Drupal\fieldhelptext\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class Bundle extends FormBase {

  public function getFormId() {
    return 'fieldhelptext_bundle';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = '', $bundle = '') {
    /** @var EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::getContainer()->get('entity_type.manager');
    /** @var EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::getContainer()->get('entity_field.manager');

    $entity_field_manager->getFieldDefinitions($entity_type, $bundle);




    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update help text'),
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

}
