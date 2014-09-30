<?php
/**
 * @file
 * Contains \Drupal\file_entity\Entity\FileEntityViewBuilder.
 */

namespace Drupal\file_entity\Entity;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FormatterInterface;
use Drupal\image\Entity\ImageStyle;

/**
 * View builder for File Entity.
 */
class FileEntityViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildComponents($build, $entities, $displays, $view_mode, $langcode);

    /** @var FileEntity[] $entities */
    foreach ($entities as $id => $entity) {
      // Filename is already displayed as title.
      $build[$id]['filename']['#access'] = FALSE;
      $build[$id]['filesize'][0]['#markup'] = format_size($entity->getSize(), $langcode);

      // Try to display a visual.
      if ($entity->bundle() == 'image' && \Drupal::moduleHandler()->moduleExists('image')) {
        // Approach described at http://drupal.stackexchange.com/a/115143
        $definition = BaseFieldDefinition::create('image')
          ->setName('image');
        $items = \Drupal::typedDataManager()->create($definition, $entity->id(), $definition->getName(), $entity->getTypedData());
        /** @var FormatterInterface $formatter */
        $formatter = \Drupal::service('plugin.manager.field.formatter')->getInstance(array(
          'field_definition' => $definition,
          'view_mode' => 'default',
          'configuration' => array(
            'label' => 'hidden',
            'type' => 'image',
          ),
        ));
        // Set image style to 'large' or fallback to none.
        $image_style = ImageStyle::load('large');
        $formatter->setSetting('image_style', isset($image_style) ? $image_style->id() : '');
        $formatter->prepareView(array($items));
        $build[$id]['heyimage'] = $formatter->view($items);
      }
    }
  }

}
