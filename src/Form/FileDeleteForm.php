<?php
/**
 * @file
 * Contains \Drupal\file_entity\Form\FileDeleteForm.
 */

namespace Drupal\file_entity\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Confirm form for deleting a file.
 */
class FileDeleteForm extends EntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the file %file?', array('%file' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('file_entity.file', array('file' => $this->entity->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message(t('The file %file has been deleted.', array('%file' => $this->entity->label())));
    $form_state->setRedirect(new Url('view.files.page_1'));
  }

} 
