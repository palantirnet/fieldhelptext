<?php

/**
 * @copyright Copyright 2020 Palantir.net, Inc.
 */

namespace Drupal\fieldhelptext\ParamConverter;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Route parameter converter for field names to field configs.
 */
class FieldNameConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new EntityConverter.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = array_reduce($defaults, function ($carry, $item) {
      return is_a($item, '\Drupal\Core\Entity\EntityTypeInterface') ? $item : $carry;
    });

    $fields = $this->entityFieldManager->getFieldStorageDefinitions($entity_type->id());

    return isset($fields[$value]) ? $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] === 'field_name');
  }

}
