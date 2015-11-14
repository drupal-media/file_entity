<?php

/**
 * @file
 * API extensions of Drupal core's file.inc.
 */
use Drupal\file\FileInterface;
use Drupal\file_entity\Entity\FileType;
use Drupal\file_entity\FileTypeInterface;
use Drupal\file\Entity\File;

/**
 * The {file_managed}.type value when the file type has not yet been determined.
 */
define('FILE_TYPE_NONE', 'undefined');

/**
 * Returns information about file formatters from hook_file_formatter_info().
 *
 * @param string $formatter_type
 *   (optional) A file formatter type name. If ommitted, all file formatter
 *   will be returned.
 *
 * @return string|array
 *   Either a file formatter description, as provided by
 *   hook_file_formatter_info(), or an array of all existing file formatters,
 *   keyed by formatter type name.
 */
function file_info_formatter_types($formatter_type = NULL) {
  $info = &drupal_static(__FUNCTION__);
  if (!isset($info)) {
    $info = module_invoke_all('file_formatter_info');
    drupal_alter('file_formatter_info', $info);
    uasort($info, '_file_entity_sort_weight_label');
  }
  if ($formatter_type) {
    if (isset($info[$formatter_type])) {
      return $info[$formatter_type];
    }
  }
  else {
    return $info;
  }
}

/**
 * Clears caches that are related to file entity.
 *
 * Clears all cached configuration related to file types, formatters, and
 * display settings.
 */
function file_info_cache_clear() {
  // Clear the formatter type cache, managed by file_info_formatter_types().
  drupal_static_reset('file_info_formatter_types');

  // Clear file type caches.
  drupal_static_reset('file_type_get_names');
}

/**
 * Generate an array for rendering just the file portion of a file entity.
 *
 * @param object $file
 *   A file object.
 * @param string|array $displays
 *   Can be either:
 *   - the name of a view mode;
 *   - or an array of custom display settings, as returned by file_displays().
 * @param string $langcode
 *   (optional) A language code to use for rendering. Defaults to the global
 *   content language of the current request.
 *
 * @return array
 *   An array as expected by drupal_render().
 */
function file_view_file($file, $displays = 'full', $langcode = NULL) {
  if (!isset($langcode)) {
    $langcode = $GLOBALS['language_content']->language;
  }

  // Prepare incoming display specifications.
  if (is_string($displays)) {
    $view_mode = $displays;
    $displays = file_displays($file->type, $view_mode);
  }
  else {
    $view_mode = '_custom_display';
  }
  drupal_alter('file_displays', $displays, $file, $view_mode);
  _file_sort_array_by_weight($displays);

  // Attempt to display the file with each of the possible displays. Stop after
  // the first successful one. See file_displays() for details.
  $element = NULL;
  foreach ($displays as $formatter_type => $display) {
    if (!empty($display['status'])) {
      $formatter_info = file_info_formatter_types($formatter_type);
      // Under normal circumstances, the UI prevents enabling formatters for
      // incompatible MIME types. In case this was somehow circumvented (for
      // example, a module updated its formatter definition without updating
      // existing display settings), perform an extra check here.
      if (isset($formatter_info['mime types'])) {
        if (!file_entity_match_mimetypes($formatter_info['mime types'], $file->filemime)) {
          continue;
        }
      }
      if (isset($formatter_info['view callback']) && ($function = $formatter_info['view callback']) && function_exists($function)) {
        $display['type'] = $formatter_type;
        if (!empty($formatter_info['default settings'])) {
          if (empty($display['settings'])) {
            $display['settings'] = array();
          }
          $display['settings'] += $formatter_info['default settings'];
        }
        $element = $function($file, $display, $langcode);
        if (isset($element)) {
          break;
        }
      }
    }
  }

  // As a last resort, fall back to showing a link to the file.
  if (!isset($element)) {
    $element = array(
      '#theme' => 'file_link',
      '#file' => $file,
    );
  }

  // Add defaults and return the element.
  $element += array(
    '#file' => $file,
    '#view_mode' => $view_mode,
    '#language' => $langcode,
  );

  return $element;
}

/**
 * @} End of "defgroup file_displays".
 */

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
 * Sorts an array by weight.
 *
 * Helper function to sort an array by the value of each item's 'weight' key,
 * while preserving relative order of items that have equal weight.
 */
function _file_sort_array_by_weight(&$a) {
  $i = 0;
  foreach ($a as $key => $item) {
    if (!isset($a[$key]['weight'])) {
      $a[$key]['weight'] = 0;
    }
    $original_weight[$key] = $a[$key]['weight'];
    $a[$key]['weight'] += $i / 1000;
    $i++;
  }
  uasort($a, 'drupal_sort_weight');
  foreach ($a as $key => $item) {
    $a[$key]['weight'] = $original_weight[$key];
  }
}

/**
 * User sort function to sort by weight, then label/name.
 */
function _file_entity_sort_weight_label($a, $b) {
  $a_weight = isset($a['weight']) ? $a['weight'] : 0;
  $b_weight = isset($b['weight']) ? $b['weight'] : 0;
  if ($a_weight == $b_weight) {
    $a_label = isset($a['label']) ? $a['label'] : '';
    $b_label = isset($b['label']) ? $b['label'] : '';
    return strcasecmp($a_label, $b_label);
  }
  else {
    return $a_weight < $b_weight ? -1 : 1;
  }
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
