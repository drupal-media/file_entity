<?php

/**
 * @file
 *  Contains Drupal\file_entity\Plugin\Field\FieldFormatter\FileAudioFormatter
 */

namespace Drupal\file_entity\Plugin\Field\FieldFormatter;


use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'file_audio' formatter.
 *
 * @FieldFormatter(
 *   id = "file_audio",
 *   label = @Translation("Audio"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileAudioFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'controls' => TRUE,
      'autoplay' => FALSE,
      'loop' => FALSE,
      'multiple_file_behavior' => 'tags',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['controls'] = array(
      '#title' => t('Show audio controls'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('controls'),
    );
    $element['autoplay'] = array(
      '#title' => t('Autoplay'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('autoplay'),
    );
    $element['loop'] = array(
      '#title' => t('Loop'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('loop'),
    );
    $element['multiple_file_behavior'] = array(
      '#title' => t('Display of multiple files'),
      '#type' => 'radios',
      '#options' => array(
        'tags' => t('Use multiple @tag tags, each with a single source', array('@tag' => '<audio>')),
        'sources' => t('Use multiple sources within a single @tag tag', array('@tag' => '<audio>')),
      ),
      '#default_value' => $this->getSetting('multiple_file_behavior'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('Controls: %controls', array('%controls' => $this->getSetting('controls') ? 'visible' : 'hidden'));
    $summary[] = t('Autoplay: %autoplay', array('%autoplay' => $this->getSetting('autoplay') ? t('yes') : t('no')));
    $summary[] = t('Loop: %loop', array('%loop' => $this->getSetting('loop') ? t('yes') : t('no')));
    $summary[] = t('Multiple files: %multiple', array('%multiple' => $this->getSetting('multiple_file_behavior')));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $multiple_file_behavior = $this->getSetting('multiple_file_behavior');
    $source_files = array();
    // Because we can have the files grouped in a single audio tag, we do a
    // grouping in case the multiple file behavior is not 'tags'.
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      if ($file->bundle() == 'audio') {
        $source_attributes = new Attribute();
        $source_attributes->setAttribute('src', file_create_url($file->getFileUri()));
        $source_attributes->setAttribute('type', $file->getMimeType());
        if ($multiple_file_behavior == 'tags') {
          $source_files[] = array(array('file' => $file, 'source_attributes' => $source_attributes));
        }
        else {
          $source_files[0][] = array('file' => $file, 'source_attributes' => $source_attributes);
        }
      }
    }
    if (!empty($source_files)) {
      // Prepare the audio attributes according to the settings.
      $audio_attributes = new Attribute();
      foreach (array('controls', 'autoplay', 'loop') as $attribute) {
        if ($this->getSetting($attribute)) {
          $audio_attributes->setAttribute($attribute, $attribute);
        }
      }
      foreach ($source_files as $delta => $files) {
        // The cache tags for each element should be the combination of all the
        // cache tags, from all the files.
        $cache_tags = array();
        foreach ($files as $file) {
          $cache_tags = array_merge($cache_tags, $file['file']->getCacheTags());
        }
        $elements[$delta] = array(
          '#theme' => 'file_entity_audio',
          '#audio_attributes' => $audio_attributes,
          '#files' => $files,
          '#cache' => array(
            // We may have duplicate cache tags, so only use the unique ones.
            'tags' => array_unique($cache_tags),
          ),
        );
      }
    }

    return $elements;
  }

}
