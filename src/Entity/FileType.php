<?php

/**
 * @file
 * Contains \Drupal\file_entity\Entity\FileType.
 */

namespace Drupal\file_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\file\FileInterface;
use Drupal\file_entity\FileTypeInterface;
use string;

/**
 * Defines the File type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "file_type",
 *   label = @Translation("File type"),
 *   controllers = {
 *     "list_builder" = "Drupal\file_entity\FileTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\file_entity\Form\FileTypeForm",
 *       "delete" = "Drupal\file_entity\Form\FileTypeDeleteForm",
 *       "enable" = "Drupal\file_entity\Form\FileTypeEnableForm",
 *       "disable" = "Drupal\file_entity\Form\FileTypeDisableForm",
 *     },
 *   },
 *   admin_permission = "administer file types",
 *   config_prefix = "type",
 *   bundle_of = "file",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "file_entity.file_types_overview",
 *     "edit-form" = "file_entity.file_types_manage",
 *     "delete-form" = "file_entity.file_types_manage_delete",
 *     "enable" = "file_entity.file_types_manage_enable",
 *     "disable" = "file_entity.file_types_manage_disable",
 *   },
 * )
 */
class FileType extends ConfigEntityBundleBase implements FileTypeInterface {

  /**
   * The machine name of this file type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the file type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this file type.
   *
   * @var string
   */
  protected $description;

  /**
   * MIME types associated with this file type.
   *
   * @var array
   */
  protected $mimetypes = array();

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeTypes() {
    return $this->mimetypes ?: array();
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
  }

  /**
   * {@inheritdoc}
   */
  public function setMimeTypes($mimetypes) {
    $this->mimetypes = array_values($mimetypes);
  }

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

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    // Sort primarily by status, secondarily by label.
    if ($a->status() == $b->status()) {
      return strnatcasecmp($a->label(), $b->label());
    }
    return ($b->status() - $a->status());
  }
}
