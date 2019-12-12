<?php

/**
 * @copyright Copyright 2019 Palantir.net, Inc.
 */

namespace Drupal\fieldhelptext\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for editing help text everywhere a field appears.
 */
class Field extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fieldhelptext_field';
  }

  /**
   * {@inheritdoc}
   *
   * Provide a form with a text area for updating the description associated
   * with each non-base field.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = '', $field_name = '') {
    /** @var EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::getContainer()->get('entity_field.manager');

    $map = $entity_field_manager->getFieldMap()[$entity_type];

    /** @var \Drupal\Core\Field\FieldConfigInterface[] $configs */
    $configs = [];
    foreach ($map[$field_name]['bundles'] as $bundle) {
      $configs[$bundle] = $entity_field_manager->getFieldDefinitions($entity_type, $bundle)[$field_name]->getConfig($bundle);
    }

    $form['fieldhelptext'] = [
      '#type' => 'value',
      '#value' => [
        'entity_type' => $entity_type,
        'field_name' => $field_name,
        'bundles' => $map[$field_name]['bundles'],
      ],
    ];

    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Edit help text for %field_name across all @entity_type bundles', [
        '%field_name' => $field_name,
        '@entity_type' => $entity_type,
      ]),
    ];

    // Default values for label and description are set later.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => '<p>Allowed HTML tags: &lt;a&gt; &lt;b&gt; &lt;big&gt; &lt;code&gt; &lt;del&gt; &lt;em&gt; &lt;i&gt; &lt;ins&gt; &lt;pre&gt; &lt;q&gt; &lt;small&gt; &lt;span&gt; &lt;strong&gt; &lt;sub&gt; &lt;sup&gt; &lt;tt&gt; &lt;ol&gt; &lt;ul&gt; &lt;li&gt; &lt;p&gt; &lt;br&gt; &lt;img&gt;</p><p>These fields support tokens.</p>',
      '#rows' => 2,
    ];

    $form['apply_to'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Update %entity_type field instances', ['%entity_type' => $entity_type]),
      '#options' => [],
    ];

    foreach ($configs as $bundle => $field_config) {
      $form['apply_to']['#options'][$bundle] = $bundle;

      $form['apply_to'][$bundle] = [
        '#type' => 'checkbox',
        '#title' => $this->t('@label (%field_name on %bundle_name)', [
          '@label' => $field_config->label(),
          '%field_name' => $field_name,
          '%bundle_name' => $bundle,
        ]),
        '#description' => empty($field_config->getDescription()) ? $this->t('(Empty description)') : $field_config->getDescription(),
        '#default_value' => $bundle,
        '#return_value' => $bundle,
      ];
      if (empty($default_label) && !empty($field_config->label())) {
        $default_label = $field_config->label();
        $form['label']['#default_value'] = $default_label;
      }
      if (empty($default_description) && !empty($field_config->getDescription())) {
        $default_description = $field_config->getDescription();
        $form['description']['#default_value'] = $default_description;
      }

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

    /** @var \Drupal\Core\Field\FieldConfigInterface[] $configs */
    $configs = [];
    foreach ($params['bundles'] as $bundle) {
      $configs[$bundle] = $entity_field_manager->getFieldDefinitions($params['entity_type'], $bundle)[$params['field_name']]->getConfig($bundle);
    }

    $apply_to = array_filter($form_state->getValue('apply_to'));

    $label = $form_state->getValue('label');
    $description = $form_state->getValue('description');

    foreach ($apply_to as $bundle) {
      if ($configs[$bundle]->label() != $label || $configs[$bundle]->getDescription() != $description) {
        $configs[$bundle]->setLabel($label);
        $configs[$bundle]->set('description', $description);
        $configs[$bundle]->save();
      }
    }

    return;

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