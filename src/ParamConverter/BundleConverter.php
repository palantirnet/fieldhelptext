<?php

namespace Drupal\fieldhelptext\ParamConverter;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Route parameter converter for bundle ids.
 *
 * @copyright Copyright 2019 Palantir.net, Inc.
 */
class BundleConverter implements ParamConverterInterface {

  /**
   * The entity_type.bundle.info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Constructs a new EntityConverter.
   */
  public function __construct(EntityTypeBundleInfoInterface $bundle_info) {
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = array_reduce($defaults, function ($carry, $item) {
      return is_a($item, '\Drupal\Core\Entity\EntityTypeInterface') ? $item : $carry;
    });

    $bundles = $this->bundleInfo->getBundleInfo($entity_type->id());

    return isset($bundles[$value]) ? $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] === 'bundle');
  }

}
