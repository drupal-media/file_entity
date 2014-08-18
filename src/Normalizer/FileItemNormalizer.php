<?php
/**
 * @file
 * Contains \Drupal\file_entity\Normalizer\FileItemNormalizer.
 */

namespace Drupal\file_entity\Normalizer;

use Drupal\hal\Normalizer\EntityReferenceItemNormalizer;

/**
 * Converts File items, including display and description values.
 */
class FileItemNormalizer extends EntityReferenceItemNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\file\Plugin\Field\FieldType\FileItem';

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    $value = parent::constructValue($data, $context);
    $value['display'] = $data['display'];
    $value['description'] = $data['description'];
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = array()) {
    /** @var $field_item \Drupal\file\Plugin\Field\FieldType\FileItem */

    $data = parent::normalize($field_item, $format, $context);

    // Copied from parent implementation.
    $field_name = $field_item->getParent()->getName();
    $entity = $field_item->getEntity();
    $field_uri = $this->linkManager->getRelationUri($entity->getEntityTypeId(), $entity->bundle(), $field_name);

    // Set file field-specific values.
    $data['_embedded'][$field_uri][0]['display'] = $field_item->get('display')->getValue();
    $data['_embedded'][$field_uri][0]['description'] = $field_item->get('description')->getValue();
    return $data;
  }
}
