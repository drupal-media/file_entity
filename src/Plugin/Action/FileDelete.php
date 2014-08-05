<?php

/**
 * @file
 * Contains \Drupal\file_entity\Plugin\Action\FileDelete.
 */

namespace Drupal\file_entity\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\file_entity\Entity\FileEntity;

/**
 * Delete a file.
 *
 * @Action(
 *   id = "file_delete_action",
 *   label = @Translation("Delete file"),
 *   type = "file"
 * )
 */
class FileDelete extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var FileEntity $entity */
    $entity->delete();
  }

}
