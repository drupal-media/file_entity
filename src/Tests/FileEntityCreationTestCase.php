<?php

/**
 * @file
 * Contains \Drupal\file_entity\Tests\FileEntityCreationTestCase.
 */

namespace Drupal\file_entity\Tests;
use Drupal\file_entity\Entity\FileType;

/**
 * Tests creating and saving a file.
 *
 * @group file_entity
 */
class FileEntityCreationTestCase extends FileEntityTestBase {

  function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('create files', 'edit own document files'));
    $this->drupalLogin($web_user);
  }

  /**
   * Create a "document" file and verify its consistency in the database.
   */
  function testFileEntityCreation() {
    $test_file = $this->getTestFile('text');
    // Create a file.
    $edit = array();
    $edit['files[upload]'] = drupal_realpath($test_file->uri);
    $this->drupalPost('file/add', $edit, t('Next'));

    // Step 2: Scheme selection
    if ($this->xpath('//input[@name="scheme"]')) {
      $this->drupalPost(NULL, array(), t('Next'));
    }

    // Check that the document file has been uploaded.
    $this->assertRaw(t('!type %name was uploaded.', array('!type' => 'Document', '%name' => $test_file->filename)), t('Document file uploaded.'));

    // Check that the file exists in the database.
    $file = $this->getFileByFilename($test_file->filename);
    $this->assertTrue($file, t('File found in database.'));
  }
}
