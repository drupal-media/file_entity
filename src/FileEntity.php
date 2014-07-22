<?php

/**
 * @file
 * Contains \Drupal\file_entity\FileEntity.
 */

namespace Drupal\file_entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\file\Entity\File;

/**
 * Replace for the core file entity class.
 */
class FileEntity extends File {

  const FILE_TYPE_NONE = 'undefined';

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    $values += array(
      'type' => static::FILE_TYPE_NONE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = array()) {
    if (!$bundle) {
      $values['type'] = static::FILE_TYPE_NONE;
      $bundle = static::FILE_TYPE_NONE;
    }
    parent::__construct($values, $entity_type, $bundle, $translations);
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    // Update the bundle if necessary.
    $this->updateBundle();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Always ensure the filemime property is current.
    if (!$this->isNew() || !$this->getMimeType()) {
      $this->setMimeType(\Drupal::service('file.mime_type.guesser')->guess($this->getFileUri()));
    }

    // Update the bundle if necessary.
    $this->updateBundle();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['type'] = FieldDefinition::create('string')
      ->setLabel(t('File type'))
      ->setDescription(t('The type of the file.'));
    return $fields;
  }

  public function updateBundle($force = FALSE) {
    $bundle = $this->bundle();
    if (!$bundle || $bundle === static::FILE_TYPE_NONE || $force) {
      $this->setBundle($this->getFileType());
    }
  }

  public function setBundle($bundle) {
    if ($this->bundle() != $bundle) {
      // Set the bundle value.
      $this->get('type')->value = $bundle;
      // Clear the field definitions, so that they will be fetched for the new bundle.
      $this->fieldDefinitions = NULL;
      // Update the entity keys cache.
      $this->entityKeys['bundle'] = $bundle;
    }
  }

  /**
   * Determines the file type for this file.
   *
   * @return string
   *   Machine name of file type that should be used for this file.
   */
  public function getFileType() {
    $types = $this->moduleHandler()->invokeAll('file_type', array($this));
    $this->moduleHandler()->alter('file_type', $types, $this);
    return !empty($types) ? $this::FILE_TYPE_NONE : reset($types);
  }

  protected function moduleHandler() {
    return \Drupal::moduleHandler();
  }

}
