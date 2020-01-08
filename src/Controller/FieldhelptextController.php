<?php

namespace Drupal\fieldhelptext\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class to provide a list of links to field help text edit forms.
 *
 * @copyright Copyright 2019, 2020 Palantir.net, Inc.
 */
class FieldhelptextController extends ControllerBase {

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity_field.manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity_type.bundle.info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfoManager;

  /**
   * FieldhelptextController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity_field.manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfoManager
   *   The entity_type.bundle.info service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, EntityTypeBundleInfoInterface $bundleInfoManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->bundleInfoManager = $bundleInfoManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'), $container->get('entity_field.manager'), $container->get('entity_type.bundle.info'));
  }

  /**
   * Build the lists of links to help text edit forms by bundle and by field.
   *
   * @return array
   *   A Drupal render array.
   */
  public function main() {
    $output = [
      'bundle' => [
        ['#type' => 'html_tag', '#tag' => 'h2', '#value' => 'Edit by Bundle'],
      ],
      'field' => [
        ['#type' => 'html_tag', '#tag' => 'h2', '#value' => 'Edit by Field'],
      ],
    ];

    $all_entity_types = $this->entityTypeManager->getDefinitions();
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface[] $fieldable_entity_types */
    $fieldable_entity_types = [];
    foreach ($all_entity_types as $entity_type_name => $entity_type) {
      if (is_a($entity_type->getClass(), '\Drupal\Core\Entity\FieldableEntityInterface', TRUE)) {
        $bundles = $this->bundleInfoManager->getBundleInfo($entity_type_name);
        $base_fields = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_name);

        foreach (array_keys($bundles) as $bundle) {
          $all_bundle_fields = $this->entityFieldManager->getFieldDefinitions($entity_type_name, $bundle);
          /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $fields */
          $bundle_fields = array_diff_key($all_bundle_fields, $base_fields);

          if (!empty($bundle_fields)) {
            $fieldable_entity_types[$entity_type_name][] = $bundle;
          }
        }

      }
    }

    $map = $this->entityFieldManager->getFieldMap();

    // List of links to administer by bundle.
    foreach ($fieldable_entity_types as $entity_type_name => $bundles) {
      $output['bundle']["{$entity_type_name}__title"] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $entity_type_name,
      ];

      $output['bundle']["{$entity_type_name}"] = [
        '#type' => 'html_tag',
        '#tag' => 'ul',
      ];

      foreach ($bundles as $bundle) {
        $all_bundle_fields = $this->entityFieldManager->getFieldDefinitions($entity_type_name, $bundle);
        /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $fields */
        $bundle_fields = array_diff_key($all_bundle_fields, $base_fields);

        if (empty($bundle_fields)) {
          continue;
        }

        $output['bundle']["{$entity_type_name}"][] = [
          '#type' => 'html_tag',
          '#tag' => 'li',
          '#value' => Link::createFromRoute($bundle, 'fieldhelptext.bundle', ['entity_type' => $entity_type_name, 'bundle' => $bundle])->toString(),
        ];
      }
    }

    // List of links to administer by field.
    foreach ($map as $entity_type => $fields) {
      $base_fields = $this->entityFieldManager->getBaseFieldDefinitions($entity_type);
      $configurable_fields = array_diff_key($fields, $base_fields);

      if (empty($configurable_fields)) {
        continue;
      }

      $output['field']["{$entity_type}__title"] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $entity_type,
      ];
      $output['field']["{$entity_type}"] = [
        '#type' => 'html_tag',
        '#tag' => 'ul',
      ];

      foreach ($configurable_fields as $field => $info) {
        $output['field']["{$entity_type}"][] = [
          '#type' => 'html_tag',
          '#tag' => 'li',
          '#value' => Link::createFromRoute($this->t('@field_name (%field_type)', ['@field_name' => $field, '%field_type' => $info['type']]), 'fieldhelptext.field', ['entity_type' => $entity_type, 'field_name' => $field])->toString(),
        ];

      }
    }

    return $output;
  }

}
