<?php
/**
 * @file
 * Contains \Drupal\file_entity\FileEntityInterface.
 */

namespace Drupal\file_entity;

/**
 * File entity interface.
 */
interface FileEntityInterface {
  /**
   * Get the metadata property value for the given property.
   *
   * @param string $property
   *   A metadata property for the file.
   *
   * @return int|null
   *   A metadata property value.
   */
  public function getMetadata($property);

  /**
   * Set the metadata property for this file.
   *
   * @param string $property
   *   A metadata property for the file.
   *
   * @param int|null $value
   *   A metadata property value for the given property.
   *
   */
  public function setMetadata($property, $value);

  /**
   * Get all metadata properties for the file.
   *
   * @return array
   *   An array of metadata properties.
   */
  public function getAllMetadata();
}
