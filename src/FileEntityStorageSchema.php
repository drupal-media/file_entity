<?php

/**
 * @file
 * Contains \Drupal\file_entity\FileEntityStorageSchema.
 */

namespace Drupal\file_entity;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\file\FileStorageSchema;

/**
 * Extends the file storage schema handler.
 */
class FileEntityStorageSchema extends FileStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Set an initial for the type, which is needed when the column is added
    // and there is already data stored.
    $schema['file_managed']['type']['initial'] = FILE_TYPE_NONE;
    debug($schema['file_managed']['type']);

    return $schema;
  }

}
