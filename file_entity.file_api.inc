<?php

/**
 * @file
 * API extensions of Drupal core's file.inc.
 */

use Drupal\file\FileInterface;
use Drupal\file\Entity\File;

/**
 * The {file_managed}.type value when the file type has not yet been determined.
 */
define('FILE_TYPE_NONE', 'undefined');

/**
 * Determines file type for a given file.
 *
 * @param \Drupal\file\FileInterface $file
 *   File object.
 *
 * @return string
 *   Machine name of file type that should be used for given file.
 */
function file_get_type(FileInterface $file) {
  $types = \Drupal::moduleHandler()->invokeAll('file_type', array($file));
  \Drupal::moduleHandler()->alter('file_type', $types, $file);

  return empty($types) ? NULL : reset($types);
}

/**
 * Returns a file object which can be passed to file_save().
 *
 * @param string $uri
 *   A string containing the URI, path, or filename.
 * @param bool $use_existing
 *   (Optional) If TRUE and there's an existing file in the {file_managed}
 *   table with the passed in URI, then that file object is returned.
 *   Otherwise, a new file object is returned. Default is TRUE.
 *
 * @return FileInterface|bool
 *   A file object, or FALSE on error.
 *
 * @todo This should probably be named
 *   file_load_by_uri($uri, $create_if_not_exists).
 * @todo Remove this function when http://drupal.org/node/685818 is fixed.
 */
function file_uri_to_object($uri, $use_existing = TRUE) {
  $file = FALSE;
  $uri = file_stream_wrapper_uri_normalize($uri);

  if ($use_existing) {
    // We should always attempt to re-use a file if possible.
    $files = entity_load_multiple_by_properties('file', array('uri' => $uri));
    $file = !empty($files) ? reset($files) : FALSE;
  }

  if (empty($file)) {
    $file = File::create(array(
      'uid' => \Drupal::currentUser()->id(),
      'filename' => drupal_basename($uri),
      'uri' => $uri,
      'filemime' => \Drupal::service('file.mime_type.guesser')->guess($uri),
      'filesize' => @filesize($uri),
      'created' => REQUEST_TIME,
      'status' => FILE_STATUS_PERMANENT,
    ));
  }

  return $file;
}
