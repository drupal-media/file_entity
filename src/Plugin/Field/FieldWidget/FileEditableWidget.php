<?php
/**
 * @file
 * Contains \Drupal\file_entity\Plugin\Field\FieldWidget\FileEditableWidget.
 */

namespace Drupal\file_entity\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

/**
 * File widget with support for editing the referenced file inline.
 *
 * @FieldWidget(
 *   id = "file_editable",
 *   label = @Translation("Editable file"),
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class FileEditableWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    return $element;
  }

  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);

    if (!$element['#files']) {
      return $element;
    }

    foreach ($element['#files'] as $fid => $file) {
      $element['edit_button'] = [
        '#type' => 'submit',
        '#value' => t('Edit'),
        '#submit' => [get_called_class(), 'submitUpdate'],
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxEdit'],
        ],
      ];
    }

    return $element;
  }

  public static function submitUpdate($form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  public static function ajaxEdit($form, FormStateInterface $form_state) {
    $triggering_parents = $form_state->getTriggeringElement()['#parents'];
    array_pop($triggering_parents);
    $value = NestedArray::getValue($form_state->getValues(), $triggering_parents);
    $fid = $value['fids'][0];
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($fid);
    $file_edit_form_object = \Drupal::entityManager()->getFormObject('file', 'edit');
    $file_edit_form_object->setEntity($file);
    $file_edit_form = $file_edit_form_object->buildForm(array(), new FormState());

    $response = new AjaxResponse();
    $file_edit_form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $response->setAttachments($file_edit_form['#attached']);
    $response->addCommand(new OpenModalDialogCommand($file_edit_form['#title'], $file_edit_form));
    return $response;
  }

}
