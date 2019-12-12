<?php

namespace Drupal\fieldhelptext\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;

class FieldhelptextController extends ControllerBase {

  public function main() {
    $output = [
      'bundle' => [
        ['#type' => 'html_tag', '#tag' => 'h2', '#value' => 'Edit by Bundle'],
      ],
      'field' => [
        ['#type' => 'html_tag', '#tag' => 'h2', '#value' => 'Edit by Field'],
      ],
    ];

    /** @var EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::getContainer()->get('entity_type.manager');
    /** @var EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::getContainer()->get('entity_field.manager');
    /** @var EntityTypeBundleInfoInterface $bundle_info_manager */
    $bundle_info_manager = \Drupal::getContainer()->get('entity_type.bundle.info');

    $all_entity_types = $entity_type_manager->getDefinitions();
    /** @var ContentEntityTypeInterface[] $fieldable_entity_types */
    $fieldable_entity_types = [];
    foreach ($all_entity_types as $entity_type_name => $entity_type) {
      if (is_a($entity_type->getClass(), '\Drupal\Core\Entity\FieldableEntityInterface', TRUE)) {
        $fieldable_entity_types[$entity_type_name] = $entity_type;
      }
    }

    // List of links to administer by bundle
    foreach ($fieldable_entity_types as $entity_type_name => $entity_type) {
      $output['bundle']["{$entity_type_name}__title"] = ['#type' => 'html_tag', '#tag' => 'h3', '#value' => $entity_type_name];

      $output['bundle']["{$entity_type_name}"] = [
        '#type' => 'html_tag',
        '#tag' => 'ul',
      ];

      $bundles = $bundle_info_manager->getBundleInfo($entity_type_name);
      foreach ($bundles as $bundle => $info) {
        $output['bundle']["{$entity_type_name}"][] = [
          '#type' => 'html_tag',
          '#tag' => 'li',
          '#value' => Link::createFromRoute($info['label'], 'fieldhelptext.bundle', ['entity_type' => $entity_type_name, 'bundle' => $bundle])->toString(),
        ];
      }
    }

    // List of links to administer by field
    // Count number of times field is used

    return $output;
  }

}