<?php

/**
 * @file
 *  Contains Drupal\file_entity\Plugin\Field\FieldFormatter\FileDownloadLinkFormatter
 */

namespace Drupal\file_entity\Plugin\Field\FieldFormatter;


use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Template\Attribute;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'file_download_link' formatter.
 *
 * @FieldFormatter(
 *   id = "file_download_link",
 *   label = @Translation("Download link"),
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class FileDownloadLinkFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Link text'),
      '#description' => t('This field supports tokens.'),
      '#default_value' => $this->getSetting('text'),
    );
    // If we have the token module available, add the token tree link.
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $token_types = array('file');
      if (!empty($form['#entity_type'])) {
        $token_types[] = $form['#entity_type'];
      }
      $element['token_tree_link'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => $token_types,
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'text' => 'Download [file:name]',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    // For token replace, we also want to use the parent entity of the file.
    $parent_entity = $items->getParent()->getValue();
    if (!empty($parent_entity)) {
      $parent_entity_type = $parent_entity->getEntityType()->id();
      $token_data[$parent_entity_type] = $parent_entity;
    }
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      // Prepare the attributes for the main container of the template.
      $mime_type = $file->getMimeType();
      // Classes to add to the file field for icons.
      $classes = array(
        'file',
        // Add a specific class for each and every mime type.
        'file--mime-' . strtr($mime_type, array('/' => '-', '.' => '-')),
        // Add a more general class for groups of well known mime types.
        'file--' . file_icon_class($mime_type),
      );
      $attributes = new Attribute();
      $attributes->addClass($classes);

      // Prepare the text and the URL of the link.
      $token_data['file'] = $file;
      $link_text = \Drupal::token()->replace($this->getSetting('text'), $token_data);
      // Set options as per anchor format described at
      // http://microformats.org/wiki/file-format-examples
      $download_url = file_entity_download_url($file, array('attributes' => array('type' => $mime_type . '; length=' . $file->getSize())));
      $elements[$delta] = array(
        '#theme' => 'file_entity_download_link',
        '#file' => $file,
        '#download_link' => Link::fromTextAndUrl($link_text, $download_url),
        '#attributes' => $attributes,
        '#file_size' => format_size($file->getSize()),
        '#cache' => array(
          'tags' => $file->getCacheTags(),
        ),
      );
    }

    return $elements;
  }
}
