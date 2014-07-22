<?php

/**
 * @file
 * Contains \Drupal\file_entity\Entity\FileEntity.
 */

namespace Drupal\file_entity\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\file\Entity\File;

/**
 * Replace for the core file entity class.
 */
class FileEntity extends File {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    $values += array(
      'type' => FILE_TYPE_NONE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = array()) {
    if (!$bundle) {
      $values['type'] = FILE_TYPE_NONE;
      $bundle = FILE_TYPE_NONE;
    }
    parent::__construct($values, $entity_type, $bundle, $translations);
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    // Update the bundle.
    if ($this->bundle() === FILE_TYPE_NONE) {
      $this->updateBundle();
    }
  }


  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    $this->setMimeType(\Drupal::service('file.mime_type.guesser')->guess($this->getFileUri()));

    // Update the bundle.
    if ($this->bundle() === FILE_TYPE_NONE) {
      $this->updateBundle();
    }

    // Fetch image dimensions.
    module_load_include('inc', 'file_entity', 'file_entity.file');
    file_entity_metadata_fetch_image_dimensions($this);
  }

  /**
   * Updates the file bundle.
   */
  protected function updateBundle() {
    $type = file_get_type($this);
    // Update the type field.
    $this->get('type')->value = $type;
    // Clear the field definitions, so that they will be fetched for the new bundle.
    $this->fieldDefinitions = NULL;
    // Update the entity keys cache.
    $this->entityKeys['bundle'] = $type;
  }

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['type'] = FieldDefinition::create('string')
      ->setLabel(t('File type'))
      ->setDescription(t('The type of the file.'));
    return $fields;
  }

}
