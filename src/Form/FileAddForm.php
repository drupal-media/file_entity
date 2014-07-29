<?php

/**
 * @file
 * Contains \Drupal\file_entity\Form\FileAddForm.
 */

namespace Drupal\file_entity\Form;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Form\FormBase;

/**
 * Form controller for file type forms.
 */
class FileAddForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'file_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, array $options = array()) {
    $step = (isset($form_state['step']) && in_array($form_state['step'], array(1, 2, 3, 4))) ? $form_state['step'] : 1;
    $form['#step'] = $step;
    $form['#options'] = $options;

    switch ($step) {
      case 1:
        return $this->stepUpload($form, $form_state);

      case 2:
        return file_entity_add_upload_step_filetype($form, $form_state);

      case 3:
        return file_entity_add_upload_step_scheme($form, $form_state);

      case 4:
        return file_entity_add_upload_step_fields($form, $form_state);

    }
  }

  /**
   * Generate form fields for the first step in the add file wizard.
   *
   * @param $form
   * @param $type
   */
  function stepUpload($form, $form_state) {
    $form['upload'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => 'description',
      '#description' => t('A brief description of this file type.'),
    );

    $form['upload'] = array(
      '#type' => 'managed_file',
      '#title' => t('Upload a new file'),
      '#description' => t('Just a description'),
      '#upload_location' => $this->getUploadDestinationUri($form['#options']),
      '#upload_validators' => $this->getUploadValidators($form['#options']),
      '#progress_indicator' => 'bar',
      '#required' => TRUE,
      '#pre_render' => array('file_managed_file_pre_render', 'file_entity_upload_validators_pre_render'),
      '#default_value' => isset($form_state['storage']['upload']) ? $form_state['storage']['upload'] : NULL,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['next'] = array(
      '#type' => 'submit',
      '#value' => t('Next'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_state['storage'] = isset($form_state['storage']) ? $form_state['storage'] : array();
    $form_state['storage'] = array_merge($form_state['storage'], $form_state['values']);

    // This var is set to TRUE when we are ready to save the file.
    $save = FALSE;
    $trigger = $form_state['triggering_element']['#id'];

    $steps_to_check = array(2, 3);
    if ($trigger == 'edit-previous') {
      // If the previous button was hit,
      // the step checking order should be reversed 3, 2.
      $steps_to_check = array_reverse($steps_to_check);
    }

    foreach ($steps_to_check as $step) {
      // Check if we can skip step 2 and 3.
      if (($form['#step'] == $step - 1 && $trigger == 'edit-next') || ($form['#step'] == $step + 1 && $trigger == 'edit-previous')) {
        $file = file_load($form_state['storage']['upload']);
        if ($step == 2) {
          // Check if we can skip step 2.
          $candidates = file_entity_get_filetype_candidates($file);
          if (count($candidates) == 1) {
            $candidates_keys = array_keys($candidates);
            // There is only one possible filetype for this file.
            // Skip the second page.
            $form['#step'] += ($trigger == 'edit-previous') ? -1 : 1;
            $form_state['storage']['type'] = reset($candidates_keys);
          }
          elseif (\Drupal::config('file_entity')->get('file_upload_wizard_skip_file_type', FALSE)) {
            // Do not assign the file a file type.
            $form['#step'] += ($trigger == 'edit-previous') ? -1 : 1;
            $form_state['storage']['type'] = FILE_TYPE_NONE;
          }
        }
        else {
          // Check if we can skip step 3.
          $schemes = file_get_stream_wrappers(STREAM_WRAPPERS_WRITE_VISIBLE);
          if (!file_entity_file_is_writeable($file)) {
            // The file is read-only (remote) and must use its provided scheme.
            $form['#step'] += ($trigger == 'edit-previous') ? -1 : 1;
            $form_state['storage']['scheme'] = file_uri_scheme($file->uri);
          }
          elseif (count($schemes) == 1) {
            // There is only one possible stream wrapper for this file.
            // Skip the third page.
            $form['#step'] += ($trigger == 'edit-previous') ? -1 : 1;
            $form_state['storage']['scheme'] = key($schemes);
          }
          elseif (\Drupal::config('file_entity')->get('file_upload_wizard_skip_scheme', FALSE)) {
            // Assign the file the default scheme.
            $form['#step'] += ($trigger == 'edit-previous') ? -1 : 1;
            $form_state['storage']['scheme'] = file_default_scheme();
          }
        }
      }
    }

    // We have the filetype, check if we can skip step 4.
    if (($form['#step'] == 3 && $trigger == 'edit-next')) {
      $file = file_load($form_state['storage']['upload']);
      if (!field_info_instances('file', $form_state['storage']['type'])) {
        // This filetype doesn't have fields, save the file.
        $save = TRUE;
      }
      elseif (\Drupal::config('file_entity')->get('file_upload_wizard_skip_fields', FALSE)) {
        // Save the file with blanks fields.
        $save = TRUE;
      }
    }

    // Form id's can vary depending on how many other forms are displayed, so we
    // need to do string comparissons. e.g edit-submit--2.
    if (strpos($trigger, 'edit-next') !== FALSE) {
      $form_state['step'] = $form['#step'] + 1;
    }
    elseif (strpos($trigger, 'edit-previous') !== FALSE) {
      $form_state['step'] = $form['#step'] - 1;
    }
    elseif (strpos($trigger, 'edit-submit') !== FALSE) {
      $save = TRUE;
    }

    if ($save) {
      $file = file_load($form_state['storage']['upload']);
      if ($file) {
        if (file_uri_scheme($file->uri) != $form_state['storage']['scheme']) {
          if ($moved_file = file_move($file, $form_state['storage']['scheme'] . '://' . file_uri_target($file->uri), FILE_EXISTS_RENAME)) {
            // Only re-assign the file object if file_move() did not fail.
            $file = $moved_file;
          }
        }
        $file->type = $form_state['storage']['type'];
        $file->display = TRUE;

        // Change the file from temporary to permanent.
        $file->status = FILE_STATUS_PERMANENT;

        // Save the form fields.
        // Keep in mind that the values for the Field API fields must be in
        // $form_state['values'] and not in ['storage']. This is true as long as
        // the fields are on the last page of the multi step form.
        entity_form_submit_build_entity('file', $file, $form, $form_state);

        file_save($file);
        $form_state['file'] = $file;
        drupal_set_message(t('@type %name was uploaded.', array('@type' => file_entity_type_get_name($file), '%name' => $file->filename)));
      }
      else {
        drupal_set_message(t('An error occurred and no file was uploaded.'), 'error');
        return;
      }

      // Figure out destination.
      if (isset($_GET['destination'])) {
        $destination = drupal_get_destination();
        unset($_GET['destination']);
      }
      elseif (user_access('administer files')) {
        $destination = array('destination' => 'admin/content/file');
      }
      else {
        $destination = array('destination' => 'file/' . $file->fid);
      }
      $form_state['redirect'] = $destination['destination'];
    }
    else {
      $form_state['rebuild'] = TRUE;
    }

  }


  /**
   * Determines the upload location for the file add upload form.
   *
   * @param array $params
   *   An array of parameters from the media browser.
   * @param array $data
   *   (optional) An array of token objects to pass to token_replace().
   *
   * @return string
   *   A file directory URI with tokens replaced.
   *
   * @see token_replace()
   */
  function getUploadDestinationUri(array $params, array $data = array()) {
    $params += array(
      'uri_scheme' => file_default_scheme(),
      'file_directory' => '',
    );

    $destination = trim($params['file_directory'], '/');

    // Replace tokens.
    $destination = \Drupal::token()->replace($destination, $data);

    return $params['uri_scheme'] . '://' . $destination;
  }

  /**
   * Retrieves the upload validators for a file.
   *
   * @param array $options
   *   (optional) An array of options for file validation.
   *
   * @return array
   *   An array suitable for passing to file_save_upload() or for a managed_file
   *   or upload element's '#upload_validators' property.
   */
  function getUploadValidators(array $options = array()) {
    // Set up file upload validators.
    $validators = array();

    // Validate file extensions. If there are no file extensions in $params and
    // there are no Media defaults, there is no file extension validation.
    if (!empty($options['file_extensions'])) {
      $validators['file_validate_extensions'] = array($options['file_extensions']);
    }
    else {
      $validators['file_validate_extensions'] = array(\Drupal::config('file_entity.settings')->get('default_allowed_extensions'));
    }

    // Cap the upload size according to the system or user defined limit.
    $max_filesize = Bytes::toInt(file_upload_max_size());
    $file_entity_max_filesize = Bytes::toInt(\Drupal::config('file_entity.settings')->get('max_filesize'));

    // If the user defined a size limit, use the smaller of the two.
    if (!empty($file_entity_max_filesize)) {
      $max_filesize = min($max_filesize, $file_entity_max_filesize);
    }

    if (!empty($options['max_filesize']) && $options['max_filesize'] < $max_filesize) {
      $max_filesize = Bytes::toInt($options['max_filesize']);
    }

    // There is always a file size limit due to the PHP server limit.
    $validators['file_validate_size'] = array($max_filesize);

    // Add image validators.
    $options += array('min_resolution' => 0, 'max_resolution' => 0);
    if ($options['min_resolution'] || $options['max_resolution']) {
      $validators['file_validate_image_resolution'] = array($options['max_resolution'], $options['min_resolution']);
    }

    // Add other custom upload validators from options.
    if (!empty($options['upload_validators'])) {
      $validators += $options['upload_validators'];
    }

    return $validators;
  }
}
