<?php

/**
 * @file
 * Contains \Drupal\file_entity\FileTypeListBuilder.
 */

namespace Drupal\file_entity;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\file_entity\Entity\FileType;

/**
 * Builds a list of file types.
 *
 * @see \Drupal\file_entity\Entity\FileType
 */
class FileTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    $header['description'] = t('Description');
    $header['status'] = t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var FileType $entity */
    $row['label'] = $this->getLabel($entity);
    $row['description'] = $entity->getDescription();
    $row['status'] = $entity->status() ? t('Enabled') : t('Disabled');
    return $row + parent::buildRow($entity);
  }
}
