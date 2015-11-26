<?php

/**
 * @file
 * Contains \Drupal\file_entity\Form\FileAddArchiveForm.
 */

namespace Drupal\file_entity\Form;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Bytes;
use Drupal\file\Entity\File;

/**
 * Class FileAddArchiveForm.
 *
 * @package Drupal\file_entity\Form
 */
class FileAddArchiveForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'file_add_archive_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $validators = $this->getUploadValidators($form_state->get('options'));

    $form['upload'] = array(
      '#type' => 'managed_file',
      '#title' => $this->t('Upload an archive file'),
      '#upload_location' => NULL,
      '#progress_indicator' => 'bar',
      '#default_value' => $form_state->has('file') ? array($form_state->get('file')->id()) : NULL,
      '#required' => TRUE,
      '#description' => 'Files must be less than <strong>' . format_size($validators['file_validate_size'][0]) . '</strong><br> Allowed file types: <strong>' . $validators['file_validate_extensions'][0] . '</strong>',
      '#upload_validators' => $validators,
    );

    $form['pattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Pattern'),
      '#description' => $this->t('Only files matching this pattern will be imported. For example, to import all jpg and gif files, the pattern would be <em>*.jpg|*.gif</em>. Use <em>.*</em> to extract all files in the archive.'),
      '#default_value' => '.*',
      '#required' => TRUE,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($archive = File::load($form_state->getValue('upload')[0])) {
      if ($archiver = archiver_get_archiver($archive->getFileUri())) {

        $extract_dir = file_default_scheme() . '://' . pathinfo($archive->getFilename(), PATHINFO_FILENAME);
        $extract_dir = file_destination($extract_dir, FILE_EXISTS_RENAME);
        if (!file_prepare_directory($extract_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY)) {
          throw new \Exception(t('Unable to prepar, e directory %dir for extraction.', array('%dir' => $extract_dir)));
        }
        $archiver->extract($extract_dir);
        $pattern = '/' . $form_state->getValue('pattern') . '/';
        if ($files = file_scan_directory($extract_dir, $pattern)) {
          foreach ($files as $file) {
            $file = File::create([
              'uri' => $file->uri,
              'filename' => $file->filename,
              'status' => FILE_STATUS_PERMANENT,
            ]);
            $file->save();
          }
        }
        drupal_set_message($this->t('Extracted %file and added @count new files.', array('%file' => $archive->getFilename(), '@count' => count($files))));
      }
      else {
        throw new \Exception(t('Cannot extract %file, not a valid archive.', array('%file' => $archive->getFileUri())));
      }
    }
    $this->redirect('entity.file.collection')->send();
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
  public function getUploadValidators(array $options = array()) {
    // Set up file upload validators.
    $validators = array();

    // Validate file extensions. If there are no file extensions in $params and
    // there are no Media defaults, there is no file extension validation.
    if (!empty($options['file_extensions'])) {
      $validators['file_validate_extensions'] = array($options['file_extensions']);
    }
    else {
      $validators['file_validate_extensions'] = array(archiver_get_extensions());
    }

    // Cap the upload size according to the system or user defined limit.
    $max_filesize = file_upload_max_size();
    $user_max_filesize = Bytes::toInt(\Drupal::config('file_entity.settings')
      ->get('max_filesize'));

    // If the user defined a size limit, use the smaller of the two.
    if (!empty($user_max_filesize)) {
      $max_filesize = min($max_filesize, $user_max_filesize);
    }

    if (!empty($options['max_filesize']) && $options['max_filesize'] < $max_filesize) {
      $max_filesize = Bytes::toInt($options['max_filesize']);
    }

    // There is always a file size limit due to the PHP server limit.
    $validators['file_validate_size'] = array($max_filesize);

    // Add other custom upload validators from options.
    if (!empty($options['upload_validators'])) {
      $validators += $options['upload_validators'];
    }

    return $validators;
  }

}
