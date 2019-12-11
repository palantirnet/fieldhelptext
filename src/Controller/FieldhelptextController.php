<?php

namespace Drupal\fieldhelptext\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Routing\RequestContext;

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
    $entity_field_manager = \Drupal::getContainer()->get('entity_field.manager');

    $all_entity_types = $entity_type_manager->getDefinitions();
    /** @var FieldableEntityInterface[] $fieldable_entity_types */
    $fieldable_entity_types = [];
    foreach ($all_entity_types as $entity_type) {
      if ($entity_type instanceof FieldableEntityInterface) {
        $fieldable_entity_types[] = $entity_type;
      }
    }

    // List of links to administer by bundle
    foreach ($fieldable_entity_types as $entity_type) {
      $output['bundle'][] = ['#type' => 'html_tag', '#tag' => 'h3', '#value' => $entity_type->label()];
    }

    // List of links to administer by field
    // Count number of times field is used

    return $output;
  }

  public function bundle($entity_type, $bundle) {
    return ['#markup' => 'Placeholder for bundle edit page.'];
  }

  public function field() {
    return ['#markup' => 'Placeholder for field edit page.'];
  }

}
