<?php

/**
 * @file
 * Contains \Drupal\file_entity\Form\FileTypeForm.
 */

namespace Drupal\file_entity\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\file_entity\Entity\FileType;
use Drupal\file_entity\Mimetypes;

/**
 * Form controller for file type forms.
 */
class FileTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);

    /* @var FileType $type */
    $type = $this->entity;

    $form['label'] = array(
      '#title' => t('Label'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => t('The human-readable name of the file type.'),
      '#required' => TRUE,
      '#size' => 30,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => array(
        'exists' => 'Drupal\file_entity\Entity\FileType::load',
        'source' => array('label'),
      ),
      '#description' => t('A unique machine-readable name for this file type. It must only contain lowercase letters, numbers, and underscores.'),
    );

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->description,
      '#description' => t('A brief description of this file type.'),
    );

    $form['mimetypes'] = array(
      '#type' => 'textarea',
      '#title' => t('Mimetypes'),
      '#description' => t('Enter one mimetype per line.'),
      '#default_value' => implode("\n", $type->mimetypes),
    );

    $mimetypes = new Mimetypes(\Drupal::moduleHandler());

    $form['mimetype_mapping'] = array(
      '#type' => 'details',
      '#title' => t('Mimetype List'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['mimetype_mapping']['mapping'] = array(
      '#theme' => 'item_list',
      '#items' => $mimetypes->get(),
    );

    $form['actions'] = array('#type' => 'actions');

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
    if (!empty($type->type)) {
      $form['actions']['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
      );
    }

    return $form;
  }

  // @todo delete?
  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save file type');
    $actions['delete']['#value'] = t('Delete file type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);

    $id = trim($form_state['values']['id']);
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $this->setFormError('id', $form_state, $this->t("Invalid machine-readable name. Enter a name other than %invalid.", array('%invalid' => $id)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $type = $this->entity;
    $type->id = trim($type->id());
    $type->label = trim($type->label());

    $status = $type->save();

    $t_args = array('%name' => $type->label());

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The file type %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The file type %name has been added.', $t_args));
      \Drupal::logger('file_entity')->log(WATCHDOG_NOTICE, t('Added file type %name.', $t_args));
    }

    $form_state['redirect_route']['route_name'] = 'file_entity.file_types_overview';
  }

}
