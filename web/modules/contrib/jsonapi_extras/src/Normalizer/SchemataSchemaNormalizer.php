<?php

namespace Drupal\jsonapi_extras\Normalizer;

use Drupal\schemata_json_schema\Normalizer\jsonapi\SchemataSchemaNormalizer as SchemataJsonSchemaSchemataSchemaNormalizer;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;

/**
 * Applies JSONAPI Extras attribute overrides to entity schemas.
 */
class SchemataSchemaNormalizer extends SchemataJsonSchemaSchemataSchemaNormalizer {

  /**
   * The JSON API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepository
   */
  protected $resourceTypeRepository;

  /**
   * @param ResourceTypeRepository $resource_type_repository
   *   A resource repository.
   */
  public function __construct(ResourceTypeRepository $resource_type_repository) {
    $this->resourceTypeRepository = $resource_type_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $normalized = parent::normalize($entity, $format, $context);

    // Load the resource type for this entity type and bundle.
    $resource_type = $this->resourceTypeRepository->get($entity->getEntityTypeId(), $entity->getBundleId());

    if (!$resource_type) {
      return $normalized;
    }

    // Alter the attributes according to the resource config.
    foreach (['attributes', 'relationships'] as $property_type) {
      foreach ($normalized['properties'][$property_type]['properties'] as $fieldname => $schema) {
        $properties = &$normalized['properties'][$property_type]['properties'];
        unset($properties[$fieldname]);

        if (!$resource_type->isFieldEnabled($fieldname)) {
          // If the field is disabled, do nothing after removal.
          continue;
        }
        else {
          // Otherwise, substitute the public name.
          $public_name = $resource_type->getPublicName($fieldname);
          $properties[$public_name] = $schema;
        }
      }
    }

    return $normalized;
  }

}
