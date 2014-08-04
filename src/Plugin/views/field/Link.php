<?php

/**
 * @file
 * Contains Drupal\file_entity\Plugin\views\field\Link.
 */

namespace Drupal\file_entity\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to the file.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("file_entity_link")
 */
class Link extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = array('default' => '', 'translatable' => TRUE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    parent::buildOptionsForm($form, $form_state);

    // The path is set by renderLink function so don't allow to set it.
    $form['alter']['path'] = array('#access' => FALSE);
    $form['alter']['external'] = array('#access' => FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if ($entity = $this->getEntity($values)) {
      return $this->renderLink($entity, $values);
    }
  }

  /**
   * Prepares the link to the file.
   *
   * @param \Drupal\Core\Entity\EntityInterface $file
   *   The file entity this field belongs to.
   * @param ResultRow $values
   *   The values retrieved from the view's result set.
   *
   * @return string
   *   Returns a string for the link text or returns null if user has no access.
   */
  protected function renderLink($file, ResultRow $values) {
    $text = NULL;

    // Ensure user has access to delete this media item.
    if ($file->access('delete')) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = "file/" . $file->id() . "/view";
      $this->options['alter']['query'] = drupal_get_destination();

      $text = !empty($this->options['text']) ? $this->options['text'] : t('View');
    }

    return $text;
  }

}
