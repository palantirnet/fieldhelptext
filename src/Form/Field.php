<?php

/**
 * @copyright Copyright 2019, 2020 Palantir.net, Inc.
 */

namespace Drupal\fieldhelptext\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for editing help text everywhere a field appears.
 */
class Field extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new Field form object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager, Messenger $messenger) {
    $this->entityFieldManager = $entityFieldManager;
    $this->setMessenger($messenger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_field.manager'), $container->get('messenger'));
  }

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
  public function buildForm(array $form, FormStateInterface $form_state, EntityTypeInterface $entity_type = NULL, $field_name = '') {
    $map = $this->entityFieldManager->getFieldMap()[$entity_type->id()];

    /** @var \Drupal\Core\Field\FieldConfigInterface[] $configs */
    $configs = [];
    foreach ($map[$field_name]['bundles'] as $bundle) {
      $configs[$bundle] = $this->entityFieldManager->getFieldDefinitions($entity_type->id(), $bundle)[$field_name]->getConfig($bundle);
    }

    $form['fieldhelptext'] = [
      '#type' => 'value',
      '#value' => [
        'entity_type' => $entity_type->id(),
        'field_name' => $field_name,
        'bundles' => $map[$field_name]['bundles'],
      ],
    ];

    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Edit label and help text for %field_name across all @entity_type bundles', [
        '%field_name' => $field_name,
        '@entity_type' => $entity_type->getLabel(),
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
      '#description' => '<p>Allowed HTML tags: &lt;a&gt; &lt;b&gt; &lt;big&gt; &lt;code&gt; &lt;del&gt; &lt;em&gt; &lt;i&gt; &lt;ins&gt; &lt;pre&gt; &lt;q&gt; &lt;small&gt; &lt;span&gt; &lt;strong&gt; &lt;sub&gt; &lt;sup&gt; &lt;tt&gt; &lt;ol&gt; &lt;ul&gt; &lt;li&gt; &lt;p&gt; &lt;br&gt; &lt;img&gt;</p><p>This text area supports tokens.</p>',
      '#rows' => 2,
    ];

    $form['apply_to'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select field instances to update', ['%entity_type' => $entity_type->getLabel()]),
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

    /** @var \Drupal\Core\Field\FieldConfigInterface[] $configs */
    $configs = [];
    foreach ($params['bundles'] as $bundle) {
      $configs[$bundle] = $this->entityFieldManager->getFieldDefinitions($params['entity_type'], $bundle)[$params['field_name']]->getConfig($bundle);
    }

    $apply_to = array_filter($form_state->getValue('apply_to'));

    $label = $form_state->getValue('label');
    $description = $form_state->getValue('description');

    foreach ($apply_to as $bundle) {
      if ($configs[$bundle]->label() != $label || $configs[$bundle]->getDescription() != $description) {
        $configs[$bundle]->setLabel($label);
        $configs[$bundle]->set('description', $description);
        $configs[$bundle]->save();
        $this->messenger->addStatus(new TranslatableMarkup('Updated text for @bundle @entity field %field_name', [
          '@bundle' => $bundle,
          '@entity' => $params['entity_type'],
          '%field_name' => $params['field_name'],
        ]));
      }
    }
  }

}
