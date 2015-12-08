<?php
/**
 * @file
 * Theme functions for field help text administration.
 *
 * @copyright (c) Copyright 2015 Palantir.net
 */

/**
 * Display the admin listing in two columns.
 */
function theme_fieldhelptext_admin($variables) {
  $element = $variables['element'];
  $message = '<div>' . t('Bulk edit field description text. From this page you may either edit help text for all fields on a bundle, or edit labels and descriptions for all instances of a field.') . '</div>';
  return $message . '<div style="width: 49%; float: left;>' . drupal_render($element['bundles_list']) . '</div><div style="width: 49%; float: left;>' . drupal_render($element['fields_list']) . '</div>';
}
