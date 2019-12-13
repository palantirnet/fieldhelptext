<?php

/**
 * @copyright Copyright 2019 Palantir.net, Inc.
 */

namespace Drupal\fieldhelptext\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for editing help text for all fields on a bundle.
 */
class Bundle extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fieldhelptext_bundle';
  }

  /**
   * {@inheritdoc}
   *
   * Provide a form with a text area for updating the description associated
   * with each non-base field.
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityTypeInterface $entity_type = null, $bundle = '') {
    /** @var EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::getContainer()->get('entity_field.manager');

    $all_fields = $entity_field_manager->getFieldDefinitions($entity_type->id(), $bundle);
    $base_fields = $entity_field_manager->getBaseFieldDefinitions($entity_type->id());

    /** @var FieldDefinitionInterface[] $fields */
    $fields = array_diff_key($all_fields, $base_fields);

    $form['fieldhelptext'] = [
      '#type' => 'value',
      '#value' => [
        'entity_type' => $entity_type->id(),
        'bundle' => $bundle,
        'field_names' => array_keys($fields),
      ],
    ];

    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Edit help text for %bundle_name @entity_type fields', [
        '%bundle_name' => $bundle,
        '@entity_type' => $entity_type->getLabel(),
      ]),
    ];

    $form['intro'] = [
      '#markup' => '<p>Allowed HTML tags: &lt;a&gt; &lt;b&gt; &lt;big&gt; &lt;code&gt; &lt;del&gt; &lt;em&gt; &lt;i&gt; &lt;ins&gt; &lt;pre&gt; &lt;q&gt; &lt;small&gt; &lt;span&gt; &lt;strong&gt; &lt;sub&gt; &lt;sup&gt; &lt;tt&gt; &lt;ol&gt; &lt;ul&gt; &lt;li&gt; &lt;p&gt; &lt;br&gt; &lt;img&gt;</p><p>These fields support tokens.</p>'
    ];

    foreach ($fields as $field_name => $field) {
      $form[$field_name] = [
        '#type' => 'textarea',
        '#title' => $field->getLabel(),
        '#default_value' => $field->getDescription(),
        '#description' => $this->t('Field type: %type', ['%type' => $field->getType()]),
        '#rows' => 2,
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update help text'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Save each changed field.
   */
  public function submitForm(array &$form, FormStateInterface $form_state, $foo = '') {
    $params = $form_state->getValue('fieldhelptext');

    /** @var EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::getContainer()->get('entity_field.manager');
    /** @var FieldDefinitionInterface[] $field_definitions */
    $field_definitions = $entity_field_manager->getFieldDefinitions($params['entity_type'], $params['bundle']);

    foreach ($params['field_names'] as $field_name) {
      $field_config = $field_definitions[$field_name]->getConfig($params['bundle']);

      // Only update fields that have been changed.
      if ($field_config->get('description') != $form_state->getValue($field_name)) {
        $field_config->set('description', $form_state->getValue($field_name));
        $field_config->save();
      }
    }
  }

}
