<?php
/**
 * @file
 * Contains \Drupal\file_entity\Normalizer\FileEntityNormalizer.
 */

namespace Drupal\file_entity\Normalizer;

use Drupal\Component\Utility\String;
use Drupal\hal\Normalizer\ContentEntityNormalizer;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Normalizer for File entity.
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
    // The URI should refer to the entity, not only directly to the file.
    global $base_url;
    return $base_url . $entity->urlInfo()->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    $data = parent::normalize($entity, $format, $context);
    if (!isset($context['included_fields']) || in_array('data', $context['included_fields'])) {
      // Save base64-encoded file contents to the "data" property.
      // @todo Allow non-binary but beware of line-ending characters.
      $file_data = base64_encode(file_get_contents($entity->getFileUri()));
      $data += array(
        'data' => array(array('value' => $file_data)),
      );
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    // Avoid 'data' being treated as a field.
    $file_data = $data['data'][0]['value'];
    unset($data['data']);
    // Decode and save to file.
    $file_contents = base64_decode($file_data);
    $entity = parent::denormalize($data, $class, $format, $context);
    $file_resource = fopen($entity->getFileUri(), 'xb');
    if (!$file_resource) {
      // @todo Instead append number to filename?
      throw new UnexpectedValueException(String::format('The file @filename already exists.', array('@filename' => $entity->getFilename())));
    }
    fwrite($file_resource, $file_contents);
    fclose($file_resource);
    return $entity;
  }
}
