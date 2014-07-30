<?php

/**
 * @file
 * Contains \Drupal\file_entity\Entity\FileType.
 */

namespace Drupal\file_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\file\FileInterface;
use Drupal\file_entity\FileTypeInterface;

/**
 * Defines the File type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "file_type",
 *   label = @Translation("File type"),
 *   controllers = {
 *     "form" = {
 *       "default" = "Drupal\file_entity\Form\FileTypeForm",
 *     },
 *   },
 *   admin_permission = "administer file types",
 *   config_prefix = "type",
 *   bundle_of = "file",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   }
 * )
 */
class FileType extends ConfigEntityBundleBase implements FileTypeInterface {

  /**
   * The machine name of this file type.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the file type.
   *
   * @var string
   */
  public $label;

  /**
   * A brief description of this file type.
   *
   * @var string
   */
  public $description;

  /**
   * MIME types associated with this file type.
   *
   * @var array
   */
  public $mimetypes = array();

  /**
   * {@inheritdoc}
   */
  public static function loadEnabled($status = TRUE) {
    $types = array();
    foreach (self::loadMultiple() as $id => $type) {
      if ($type->status == $status) {
        $types[$id] = $type;
      }
    }
    return $types;
  }
}
