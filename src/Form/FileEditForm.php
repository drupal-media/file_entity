<?php

/**
 * @file
 * Definition of Drupal\file_entity\Form\FileEditForm.
 */

namespace Drupal\file_entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for file type forms.
 */
class FileEditForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityInterface $file */
    $file = $this->entity;

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit @type</em> "@title"', array(
        '@type' => $file->type->entity->label(),
        '@title' => $file->label(),
      ));
    }

    return parent::form($form, $form_state, $file);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $file = $this->entity;
    $insert = $file->isNew();
    $file->save();

    $t_args = array('%title' => $file->label());

    if ($insert) {
      drupal_set_message(t('%title has been created.', $t_args));
    }
    else {
      drupal_set_message(t('%title has been updated.', $t_args));
    }

    // Check if file ID exists.
    if ($file->id()) {
      $form_state['values']['nid'] = $file->id();
      $form_state['nid'] = $file->id();

      $form_state['redirect_route'] = $file->urlInfo();
    }
    else {
      // In the unlikely case something went wrong on save, the node will be
      // rebuilt and node form redisplayed the same way as in preview.
      drupal_set_message(t('The post could not be saved.'), 'error');
      $form_state['rebuild'] = TRUE;
    }
  }

}
