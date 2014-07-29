<?php

/**
 * @file
 * File hooks implemented by the File entity module.
 */

use Drupal\file\FileInterface;
use Drupal\file_entity\Entity\FileType;

/**
 * Implements hook_file_type().
 */
function file_entity_file_type(FileInterface $file) {
  $types = array();
  foreach (FileType::loadEnabled() as $type) {
    if (file_entity_match_mimetypes($type->mimetypes, $file->getMimeType())) {
      $types[] = $type->id();
    }
  }

  return $types;
}

/**
 * Implements hook_file_insert().
 */
function file_entity_file_insert(FileInterface $file) {
  // Save file metadata.
  if (!empty($file->metadata)) {
    $query = db_insert('file_metadata')->fields(array('fid', 'name', 'value'));
    foreach ($file->metadata as $name => $value) {
      $query->values(array(
        'fid' => $file->id(),
        'name' => $name,
        'value' => serialize($value),
      ));
    }
    $query->execute();
  }

  // Clear any related field caches.
  // @todo: No longer needed?
  //file_entity_invalidate_field_caches($file);
}

/**
 * Implements hook_file_update().
 */
function file_entity_file_update(FileInterface $file) {
  // Save file metadata.
  db_delete('file_metadata')->condition('fid', $file->id())->execute();
  if (!empty($file->metadata)) {
    $query = db_insert('file_metadata')->fields(array('fid', 'name', 'value'));
    foreach ($file->metadata as $name => $value) {
      $query->values(array(
        'fid' => $file->id(),
        'name' => $name,
        'value' => serialize($value),
      ));
    }
    $query->execute();
  }

  if (\Drupal::moduleHandler()->moduleExists('image') && file_entity_file_get_mimetype_type($file) == 'image' && $file->getFileSize) {
    // If the image dimensions have changed, update any image field references
    // to this file and flush image style derivatives.
    if ($file->metadata['width'] != $file->metadata['width'] || $file->metadata['height'] != $file->metadata['height']) {
      _file_entity_update_image_field_dimensions($file);
    }

    // Flush image style derivatives whenever an image is updated.
    image_path_flush($file->getFileUri());
  }

  // Clear any related field caches.
  // @todo: No longer needed?
  //file_entity_invalidate_field_caches($file);
}

/**
 * Implements hook_file_delete().
 */
function file_entity_file_delete($file) {
  // This is safe to call since the file's records from the usage table have
  // not yet been deleted.
  // @todo No longer needed?
  //file_entity_invalidate_field_caches($file);

  // Remove file metadata.
  db_delete('file_metadata')->condition('fid', $file->id())->execute();

  // Remove this file from the search index if needed.
  // This code is implemented in file entity module rather than in search
  // module because file entity is implementing search module's API, not the
  // other way around.
  if (\Drupal::moduleHandler()->moduleExists('search')) {
    search_reindex($file->id(), 'file');
  }
}

/**
 * Implements hook_file_mimetype_mapping_alter().
 */
function file_entity_file_mimetype_mapping_alter(&$mapping) {
  // Add support for mka and mkv.
  // @todo Remove when http://drupal.org/node/1443070 is fixed in core.
  $new_mappings['mka'] = 'audio/x-matroska';
  $new_mappings['mkv'] = 'video/x-matroska';

  // Add support for weba, webm, and webp.
  // @todo Remove when http://drupal.org/node/1443070 is fixed in core.
  $new_mappings['weba'] = 'audio/webm';
  $new_mappings['webm'] = 'video/webm';
  $new_mappings['webp'] = 'image/webp';

  foreach ($new_mappings as $extension => $mime_type) {
    if (!in_array($mime_type, $mapping['mimetypes'])) {
      // If the mime type does not already exist, add it.
      $mapping['mimetypes'][] = $mime_type;
    }

    // Get the index of the mime type and assign the extension to that key.
    $index = array_search($mime_type, $mapping['mimetypes']);
    $mapping['extensions'][$extension] = $index;
  }
}

/**
 * Implements hook_file_load().
 */
function file_entity_file_load($files) {
  /*
  $alt = variable_get('file_entity_alt', '[file:field_file_image_alt_text]');
  $title = variable_get('file_entity_title', '[file:field_file_image_title_text]');

  $replace_options = array(
    'clear' => TRUE,
    'sanitize' => FALSE,
  );

  foreach ($files as $file) {
    $file->metadata = array();

    // Load alt and title text from fields.
    if (!empty($alt)) {
      $file->alt = token_replace($alt, array('file' => $file), $replace_options);
    }
    if (!empty($title)) {
      $file->title = token_replace($title, array('file' => $file), $replace_options);
    }
  }*/

  // Load and unserialize metadata.
  $results = db_query("SELECT * FROM {file_metadata} WHERE fid IN (:fids)", array(':fids' => array_keys($files)));
  foreach ($results as $result) {
    $files[$result->fid]->metadata[$result->name] = unserialize($result->value);
  }
}

/**
 * Fetch the dimensions of an image and store them in the file metadata array.
 */
function file_entity_metadata_fetch_image_dimensions(FileInterface $file) {
  // Prevent PHP notices when trying to read empty files.
  // @see http://drupal.org/node/681042
  if (!$file->getSize()) {
    return;
  }

  // Do not bother proceeding if this file does not have an image mime type.
  if (file_entity_file_get_mimetype_type($file) != 'image') {
    return;
  }

  // We have a non-empty image file.
  $image = Drupal::service('image.factory')->get($file->getFileUri());
  if ($image) {
    $file->metadata['width'] = $image->getWidth();
    $file->metadata['height'] = $image->getHeight();
  }
  else {
    // Fallback to NULL values.
    $file->metadata['width'] = NULL;
    $file->metadata['height'] = NULL;
  }
}

/**
 * Update the image dimensions stored in any image fields for a file.
 *
 * @param object $file
 *   A file object that is an image.
 *
 * @see http://drupal.org/node/1448124
 */
function _file_entity_update_image_field_dimensions($file) {
  // Prevent PHP notices when trying to read empty files.
  // @see http://drupal.org/node/681042
  if (!$file->filesize) {
    return;
  }

  // Do not bother proceeding if this file does not have an image mime type.
  if (file_entity_file_get_mimetype_type($file) != 'image') {
    return;
  }

  // Find all image field enabled on the site.
  $image_fields = _file_entity_get_fields_by_type('image');

  foreach ($image_fields as $image_field) {
    $query = new EntityFieldQuery();
    $query->fieldCondition($image_field, 'fid', $file->fid);
    $results = $query->execute();

    foreach ($results as $entity_type => $entities) {
      $entities = entity_load($entity_type, array_keys($entities));
      foreach ($entities as $entity) {
        foreach ($entity->{$image_field} as $langcode => $items) {
          foreach ($items as $delta => $item) {
            if ($item['fid'] == $file->fid) {
              $entity->{$image_field}[$langcode][$delta]['width'] = $file->metadata['width'];
              $entity->{$image_field}[$langcode][$delta]['height'] = $file->metadata['height'];
            }
          }
        }

        // Save the updated field column values.
        _file_entity_entity_fields_update($entity_type, $entity);
      }
    }
  }
}

/**
 * Update an entity's field values without changing anything on the entity.
 */
function _file_entity_entity_fields_update($entity_type, $entity) {
  list($id) = entity_extract_ids($entity_type, $entity);
  if (empty($id)) {
    throw new Exception(t('Cannot call _file_entity_update_entity_fields() on a new entity.'));
  }

  // Some modules use the original property.
  if (!isset($entity->original)) {
    $entity->original = $entity;
  }

  // Ensure that file_field_update() will not trigger additional usage.
  unset($entity->revision);

  // Invoke the field presave and update hooks.
  field_attach_presave($entity_type, $entity);
  field_attach_update($entity_type, $entity);

  // Clear the cache for this entity now.
  entity_get_controller($entity_type)->resetCache(array($id));
}

/**
 * Implements hook_file_metadata_info().
 */
function file_entity_file_metadata_info() {
  $info['width'] = array('label' => t('Width'), 'type' => 'integer');
  $info['height'] = array('label' => t('Height'), 'type' => 'integer');
  return $info;
}
