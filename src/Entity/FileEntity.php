<?php

/**
 * @file
 * Contains \Drupal\file_entity\Entity\FileEntity.
 */

namespace Drupal\file_entity\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
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
  public function updateBundle($type = NULL) {
    if (!$type) {
      $type = file_get_type($this);

      if (!$type) {
        return;
      }
    }

    // Update the type field.
    $this->get('type')->target_id = $type;
    // Clear the field definitions, so that they will be fetched for the new bundle.
    $this->fieldDefinitions = NULL;
    $this->dataDefinition = NULL;
    // Update the entity keys cache.
    $this->entityKeys['bundle'] = $type;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['type'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('File type'))
      ->setDescription(t('The type of the file.'))
      ->setSetting('target_type', 'file_type');

    $fields['filename']
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'label' => 'hidden',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid']->setDisplayOptions('view', array(
      'type' => 'link',
      'weight' => 1,
    ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        )
      ));

    $fields['filemime']->setDisplayOptions('view', array(
      'type' => 'string',
      'weight' => 2,
    ));
    $fields['filesize']->setDisplayOptions('view', array(
      'type' => 'string',
      'weight' => 3,
    ));

    return $fields;
  }

}
