<?php

/**
 * @file
 * Supports file operations including View, Edit, and Delete.
 */

function file_entity_upload_archive_form($form, &$form_state) {
  $options = array(
    'file_extensions' => archiver_get_extensions(),
  );

  $form['upload'] = array(
    '#type' => 'managed_file',
    '#title' => t('Upload an archive file'),
    '#upload_location' => NULL, // Upload to the temporary directory.
    '#upload_validators' => file_entity_get_upload_validators($options),
    '#progress_indicator' => 'bar',
    '#required' => TRUE,
    '#pre_render' => array('file_managed_file_pre_render', 'file_entity_upload_validators_pre_render'),
  );

  $form['pattern'] = array(
    '#type' => 'textfield',
    '#title' => t('Pattern'),
    '#description' => t('Only files matching this pattern will be imported. For example, to import all jpg and gif files, the pattern would be <em>*.jpg|*.gif</em>. Use <em>.*</em> to extract all files in the archive.'),
    '#default_value' => '.*',
    '#required' => TRUE,
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
  );

  form_load_include($form_state, 'inc', 'file_entity', 'file_entity.pages');

  return $form;
}

/**
 * Upload a file.
 */
function file_entity_upload_archive_form_submit($form, &$form_state) {
  $form_state['files'] = array();

  if ($archive = file_load($form_state['values']['upload'])) {
    if ($archiver = archiver_get_archiver($archive->uri)) {
      $files = $archiver->listContents();

      $extract_dir = file_default_scheme() . '://' . pathinfo($archive->filename, PATHINFO_FILENAME);
      $extract_dir = file_destination($extract_dir, FILE_EXISTS_RENAME);
      if (!file_prepare_directory($extract_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY)) {
        throw new Exception(t('Unable to prepar, e directory %dir for extraction.', array('%dir' => $extract_dir)));
      }

      $archiver->extract($extract_dir);
      $pattern = '/' . $form_state['values']['pattern'] . '/';
      if ($files = file_scan_directory($extract_dir, $pattern)) {
        foreach ($files as $file) {
          $file->status = FILE_STATUS_PERMANENT;
          $file->uid = $archive->uid;
          file_save($file);
          $form_state['files'][$file->fid] = $file;
        }
      }
      drupal_set_message(t('Extracted %file and added @count new files.', array('%file' => $archive->filename, '@count' => count($files))));
    }
    else {
      throw new Exception(t('Cannot extract %file, not a valid archive.', array('%file' => $archive->uri)));
    }
  }

  // Redirect to the file edit page.
  if (file_entity_access('edit') && module_exists('multiform')) {
    $destination = array('destination' => 'admin/content/file');
    if (isset($_GET['destination'])) {
      $destination = drupal_get_destination();
      unset($_GET['destination']);
    }
    $form_state['redirect'] = array('admin/content/file/edit-multiple/' . implode(' ', array_keys($form_state['files'])), array('query' => $destination));
  }
  else {
    $form_state['redirect'] = 'admin/content/file';
  }
}
