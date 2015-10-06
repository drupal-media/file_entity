<?php
/**
 * @file
 * Contains \Drupal\file_entity\Controller\DialogFileController.
 */

namespace Drupal\file_entity\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\file\FileInterface;

/**
 * Controller for dialog requests.
 */
class DialogFileController extends ControllerBase {

  /**
   * Return an Ajax dialog command for editing a file inline.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file being edited.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response with a command for opening or closing the a dialog
   *   containing the edit form.
   */
  public function inlineEdit(FileInterface $file) {
    // Build the file edit form.
    $form_object = $this->entityManager()->getFormObject('file', 'inline_edit');
    $form_object->setEntity($file);
    $form_state = (new FormState())
      ->setFormObject($form_object)
      ->disableRedirect();
    // Building the form also submits.
    $form = $this->formBuilder()->buildForm($form_object, $form_state);

    // Return a response, depending on whether it's successfully submitted.
    if (!$form_state->isExecuted()) {
      // Return the form as a modal dialog.
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $title = $this->t('Edit file @file', ['@file' => $file->label()]);
      $response = AjaxResponse::create()->addCommand(new OpenModalDialogCommand($title    , $form));
      return $response;
    }
    else {
      // Return command for closing the modal.
      return AjaxResponse::create()->addCommand(new CloseModalDialogCommand());
    }
  }

}
