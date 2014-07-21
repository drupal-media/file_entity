<?php

/**
 * @file
 * Contains \Drupal\file_entity\FileEntity.
 */

namespace Drupal\file_entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\file\Entity\File;

/**
 * Replace for the core file entity class.
 */
class FileEntity extends File {
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    $values += array(
      'type' => FILE_TYPE_NONE,
    );
  }

} 
