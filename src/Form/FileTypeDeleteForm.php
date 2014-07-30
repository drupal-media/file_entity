<?php

/**
 * @file
 * Contains \Drupal\file_entity\Form\FileTypeDeleteForm.
 */

namespace Drupal\file_entity\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Builds the form to delete a file type.
 */
class FileTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t(
      'Are you sure you want to delete the %name file type?',
      array('%name' => $this->entity->label())
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message(t(
      'The %label file type has been deleted.',
      array('%label' => $this->entity->label())
    ));
    $form_state['redirect_route'] = $this->getCancelUrl();
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl() {
    return $this->entity->urlInfo();
  }
}
