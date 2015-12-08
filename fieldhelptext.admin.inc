<?php
/**
 * @file
 * Admin edit form for field help text.
 *
 * @copyright (c) Copyright 2015 Palantir.net
 */

/**
 * Build a render array of links to edit field help text by bundle or by field.
 */
function fieldhelptext_admin() {
  $render = array('#theme' => 'fieldhelptext_admin');

  // List of links to administer by bundle.
  $render['bundles_list'] = array(
    '#theme' => 'item_list',
    '#items' => array(),
    '#title' => t('Administer by bundle'),
  );

  $entity_bundles = field_info_bundles();
  foreach ($entity_bundles as $entity_type => $bundles) {
    $render['bundles_list']['#items'][$entity_type] = array('data' => $entity_type);
    foreach ($bundles as $bundle_name => $bundle) {
      $label = t('@label (@machine_name)', array('@label' => $bundle['label'], '@machine_name' => $bundle_name));
      $path = "admin/structure/fieldhelptext/by-bundle/{$entity_type}/{$bundle_name}";
      $render['bundles_list']['#items'][$entity_type]['children'][] = l($label, $path);
    }
  }

  // List of links to administer by field.
  $render['fields_list'] = array(
    '#theme' => 'item_list',
    '#items' => array(),
    '#title' => t('Administer by field'),
  );

  $fields = field_info_fields();
  foreach ($fields as $field_name => $field) {
    $uses = 0;
    foreach ($field['bundles'] as $bundles) {
      $uses += count($bundles);
    }

    $path = "admin/structure/fieldhelptext/by-field/{$field_name}";
    $render['fields_list']['#items'][$field['type']]['data'] = $field['type'];
    $render['fields_list']['#items'][$field['type']]['children'][] = l($field_name, $path) . " ($uses)";
  }

  return $render;
}

/**
 * Form callback to edit help text for all fields on a bundle.
 */
function fieldhelptext_admin_bundle($form, &$form_state, $entity_type, $bundle_name) {
  $form = array();

  $form['title'] = array(
    '#type' => 'html_tag',
    '#tag' => 'h3',
    '#value' => t('Edit help text for %bundle_name @entity_type fields', array(
      '%bundle_name' => $bundle_name,
      '@entity_type' => $entity_type,
    )),
  );

  $form['entity_type'] = array(
    '#type' => 'value',
    '#value' => $entity_type,
  );
  $form['bundle_name'] = array(
    '#type' => 'value',
    '#value' => $bundle_name,
  );

  $instances = field_info_instances($entity_type, $bundle_name);
  foreach ($instances as $field_name => $instance) {
    $form[$field_name] = array(
      '#tree' => TRUE,
    );
    $form[$field_name]['field_name'] = array(
      '#type' => 'value',
      '#value' => $field_name,
    );
    $form[$field_name]['description'] = array(
      '#type' => 'textfield',
      '#title' => $instance['label'],
      '#description' => fieldhelptext_instance_description($instance),
      '#default_value' => $instance['description'],
      '#size' => 160,
      '#maxlength' => 1024,
    );
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Submit function for fieldhelptext_admin_bundle().
 */
function fieldhelptext_admin_bundle_submit($form, &$form_state) {
  $entity_type = $form_state['values']['entity_type'];
  $bundle_name = $form_state['values']['bundle_name'];

  foreach ($form_state['values'] as $value) {
    if (isset($value['field_name'])) {
      // Update edited description text.
      $instance = field_read_instance($entity_type, $value['field_name'], $bundle_name);
      if ($instance && ($instance['description'] != $value['description'])) {
        $instance['description'] = $value['description'];
        field_update_instance($instance);
        drupal_set_message(t('Updated field %entity-%bundle-%field_name', array(
          '%entity' => $entity_type,
          '%bundle' => $bundle_name,
          '%field_name' => $value['field_name'],
        )));
        $updated = TRUE;
      }
    }
  }
}

/**
 * Form callback to edit field label and help text for all instances of a field.
 */
function fieldhelptext_admin_field($form, &$form_state, $field_name) {
  $form = array();
  $field_info = field_info_field($field_name);

  $form['field_name'] = array(
    '#type' => 'value',
    '#value' => $field_name,
  );

  $form['info'] = array(
    '#type' => 'item',
    '#title' => t('Edit field %field_name across instances', array(
      '%field_name' => $field_name,
    )),
    '#markup' => fieldhelptext_field_description($field_info),
  );

  $form['label'] = array(
    '#type' => 'textfield',
    '#title' => t('Label'),
  );
  $form['description'] = array(
    '#type' => 'textfield',
    '#title' => t('Description'),
    '#size' => 160,
    '#maxlength' => 1024,
  );

  $form['apply_to'] = array(
    '#type' => 'fieldset',
    '#tree' => TRUE,
    '#title' => t('Update field instances'),
  );

  foreach ($field_info['bundles'] as $entity_type => $bundles) {
    $entity_info = entity_get_info($entity_type);
    $form['apply_to'][$entity_type] = array(
      '#type' => 'checkboxes',
      '#title' => $entity_info['label'],
      '#options' => array(),
      '#entity_type' => $entity_type,
    );

    $bundle_info = field_info_bundles($entity_type);
    foreach ($bundles as $bundle_name) {
      $instance = field_info_instance($entity_type, $field_name, $bundle_name);
      $form['apply_to'][$entity_type]['#options'][$bundle_name] = $bundle_name;
      $form['apply_to'][$entity_type][$bundle_name] = array(
        '#type' => 'checkbox',
        '#title' => t('@label (%field_name on %bundle_name)', array(
          '@label' => $bundle_info[$bundle_name]['label'],
          '%field_name' => $instance['field_name'],
          '%bundle_name' => $bundle_name,
        )),
        '#description' => empty($instance['description']) ? t('(Empty description)') : $instance['description'],
        '#default_value' => $bundle_name,
        '#return_value' => $bundle_name,
      );

      if (empty($default_label) && !empty($instance['label'])) {
        $default_label = $instance['label'];
        $form['label']['#default_value'] = $default_label;
      }
      if (empty($default_description) && !empty($instance['description'])) {
        $default_description = $instance['description'];
        $form['description']['#default_value'] = $default_description;
      }
    }

  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Submit function for fieldhelptext_admin_field().
 */
function fieldhelptext_admin_field_submit($form, &$form_state) {
  $field_name = $form_state['values']['field_name'];
  $label = $form_state['values']['label'];
  $description = $form_state['values']['description'];

  foreach ($form_state['values']['apply_to'] as $entity_type => $bundles) {
    foreach (array_filter($bundles) as $bundle_name) {
      // Update edited description text.
      $instance = field_read_instance($entity_type, $field_name, $bundle_name);
      if ($instance) {
        if ($instance['label'] != $label || $instance['description'] != $description) {
          $instance['label'] = $label;
          $instance['description'] = $description;
          field_update_instance($instance);
          drupal_set_message(t('Updated field %entity-%bundle-%field_name', array(
            '%entity' => $entity_type,
            '%bundle' => $bundle_name,
            '%field_name' => $field_name,
          )));
        }
      }
    }
  }
}

/**
 * Generate a brief description of the field configuration.
 */
function fieldhelptext_instance_description($instance) {
  $description = array();

  if ($instance['required']) {
    $description[] = 'required';
  }

  $field_info = field_info_field($instance['field_name']);
  $description[] = fieldhelptext_field_description($field_info);

  return implode('; ', $description);
}

/**
 * Generate a brief description of the field configuration.
 */
function fieldhelptext_field_description($field_info) {
  $description = array();

  $field_type_info = field_info_field_types($field_info['type']);
  $description[] = t('@field_type field', array('@field_type' => $field_type_info['label']));

  switch ($field_info['cardinality']) {
    case 1:
      $description[] = 'single value';
      break;

    case -1:
      $description[] = 'unlimited number of values';
      break;

    default:
      $description[] = $field_info['cardinality'] . ' values';
  }

  return implode('; ', $description);
}
