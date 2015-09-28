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
          'callback' => 'Drupal\file_entity\Controller\DialogFileController::edit',
        ],
      ];
    }

    return $element;
  }

  public static function submitUpdate($form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

}
