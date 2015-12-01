<?php

/**
 * @file
 * Field API integration for the file_entity module.
 */

/**
 * Implements hook_field_formatter_info_alter().
 */
function file_entity_field_formatter_info_alter(&$info) {
  return;
  // Add descriptions to core formatters.
  $descriptions = array(
    'file_default' => t('Create a simple link to the file. The link is prefixed by a file type icon and the name of the file is used as the link text'),
    'file_table' => t('Build a two-column table where the first column contains a generic link to the file and the second column displays the size of the file.'),
    'file_url_plain' => t('Display a plain text URL to the file.'),
    'image' => t('Format the file as an image. The image can be displayed using an image style and can optionally be linked to the image file itself or its parent content.'),
  );
  foreach ($descriptions as $key => $description) {
    if (isset($info[$key]) && empty($info[$key]['description'])) {
      $info[$key]['description'] = $description;
    }
  }

  // Formatters that can be used for images but not files, should have a
  // default mimetype restriction added to the image/* mime type for use with
  // file formatters.
  foreach ($info as &$formatter) {
    if (!isset($formatter['file formatter']) && in_array('image', $formatter['field types']) && !in_array('file', $formatter['field types'])) {
      $formatter['file formatter']['mime types'] = array('image/*');
    }
  }
}
