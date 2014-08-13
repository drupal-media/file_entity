<?php
/**
 * @file
 * Contains \Drupal\file_entity\Normalizer\FileEntityNormalizer.
 */

namespace Drupal\file_entity\Normalizer;

use Drupal\hal\Normalizer\ContentEntityNormalizer;

/**
 * Normalizer for File entity, setting href to entity URI rather than file URI.
 */
class FileEntityNormalizer extends ContentEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\file\FileInterface';

  /**
   * {@inheritdoc}
   */
  protected function getEntityUri($entity) {
    global $base_url;
    return $base_url . $entity->urlInfo()->toString();
  }
}
