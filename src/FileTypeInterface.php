<?php
/**
 * @file
 * Contains \Drupal\file_entity\FileTypeInterface.
 */

namespace Drupal\file_entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * File type entity interface.
 */
interface FileTypeInterface extends ConfigEntityInterface {
  /**
   * Loads and returns all enabled file types.
   *
   * @param bool $status
   *   (optional) If FALSE, this loads disabled rather than enabled types.
   *
   * @return FileTypeInterface[]
   *   An array of entity objects indexed by their IDs.
   */
  public static function loadEnabled($status = TRUE);
}
