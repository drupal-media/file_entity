<?php
/**
 * @file
 * Contains \Drupal\file_entity\Controller\DialogFileController.
 */

namespace Drupal\file_entity\Controller;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Controller for dialog requests.
 */
class DialogFileController extends ControllerBase {

  /**
   * Return Ajax dialog command for editing a file inline.
   *
   * @param array $form
   *   The containing form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the containing form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response for presenting a dialog for the edited file.
   */
  public function edit($form, FormStateInterface $form_state) {
    $triggering_parents = $form_state->getTriggeringElement()['#parents'];
    array_pop($triggering_parents);
    $value = NestedArray::getValue($form_state->getValues(), $triggering_parents);
    $fid = $value['fids'][0];
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($fid);
    $file_edit_form_object = \Drupal::entityManager()->getFormObject('file', 'edit');
    $file_edit_form_object->setEntity($file);
    $file_edit_form_state = (new FormState())
      ->setFormObject($file_edit_form_object)
      ->disableRedirect();
    $file_edit_form = $file_edit_form_object->buildForm(array(), $file_edit_form_state);

    $response = new AjaxResponse();
    $file_edit_form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $response->setAttachments($file_edit_form['#attached']);
    $response->addCommand(new OpenModalDialogCommand($file_edit_form['#title'], $file_edit_form));
    return $response;
  }

}
