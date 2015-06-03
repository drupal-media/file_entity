<?php
/**
 * @file
 * Contains \Drupal\file_entity\Tests\FileEntityPathautoTest.
 */

namespace Drupal\file_entity\Tests;

/**
 * Tests Pathauto support.
 *
 * @dependencies pathauto
 *
 * @group file_entity
 */
class FileEntityPathautoTest extends FileEntityTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('pathauto');

  /**
   * Tests Pathauto support.
   */
  public function testPathauto() {
    $file = $this->createFileEntity();

    $path = \Drupal::service('path.alias_storage')->load(array('source' => $file->urlInfo()->getInternalPath()));
    $this->assertTrue($path, t('Alias for file found.'));
  }

}
