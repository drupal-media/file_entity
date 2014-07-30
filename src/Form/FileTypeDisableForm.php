<?php

/**
 * @file
 * Contains \Drupal\file_entity\Form\FileTypeDisableForm.
 */

namespace Drupal\file_entity\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\file_entity\Entity\FileType;

/**
 * Builds the form to disable a file type.
 */
class FileTypeDisableForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t(
      'Are you sure you want to disable the file type %name?',
      array('%name' => $this->entity->label())
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Disable');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    /** @var FileType $type */
    $type = $this->entity;
    $type->enable();
    drupal_set_message(t(
      'The file type %label has been disabled.',
      array('%label' => $type->label())
    ));
    $form_state['redirect_route'] = $this->getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->urlInfo();
  }
}
