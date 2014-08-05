<?php

/**
 * @file
 * Contains \Drupal\file_entity\Plugin\Action\FileSetPermanent.
 */

namespace Drupal\file_entity\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\file_entity\Entity\FileEntity;

/**
 * Sets the file status to permanent.
 *
 * @Action(
 *   id = "file_permanent_action",
 *   label = @Translation("Set file status to permanent"),
 *   type = "file"
 * )
 */
class FileSetPermanent extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var FileEntity $entity */
    $entity->setPermanent();
    $entity->save();
  }

}
