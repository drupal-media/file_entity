<?php
/**
 * @file
 * Contains \Drupal\file_entity\Entity\FileEntityViewBuilder.
 */

namespace Drupal\file_entity\Entity;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * View builder for File Entity.
 */
class FileEntityViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildComponents($build, $entities, $displays, $view_mode, $langcode);

    /** @var FileEntity[] $entities */
    foreach ($entities as $id => $entity) {
      // Filename is already displayed as title.
      $build[$id]['filename']['#access'] = FALSE;
      $build[$id]['filesize'][0]['#markup'] = format_size($entity->getSize(), $langcode);
    }
  }


}
