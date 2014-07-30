<?php

/**
 * @file
 * Contains \Drupal\file_entity\Tests\FileEntityCreationTestCase.
 */

namespace Drupal\file_entity\Tests;

/**
 * Tests creating and saving a file.
 *
 * @group file_entity
 */
class FileEntityCreationTestCase extends FileEntityTestBase {

  public static $modules = array('views');

  function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('create files', 'edit own document files', 'administer files', 'access files overview', 'administer site configuration', 'view private files'));
    $this->drupalLogin($web_user);
  }

  /**
   * Create a "document" file and verify its consistency in the database.
   */
  function atestSingleFileEntityCreation() {
    // Configure private file system path
    $config = \Drupal::config('system.file');
    $private_file_system_path = NULL;
    $config->set('path.private', $private_file_system_path);
    $this->assertIdentical($config->get('path.private'), $private_file_system_path, 'Private Path is succesfully disabled.');
    $config->save();
    $this->drupalGet('admin/config/media/file-system');

    $test_file = $this->getTestFile('text');
    // Create a file.
    $edit = array();
    $edit['files[upload]'] = drupal_realpath($test_file->uri);
    $this->drupalPostForm('file/add', $edit, t('Next'));

    // Check that the document file has been uploaded.
    $this->assertRaw(t('!type %name was uploaded.', array('!type' => 'Document', '%name' => $test_file->filename)), t('Document file uploaded.'));

    // Check that the file exists in the database.
    $file = $this->getFileByFilename($test_file->filename);
    $this->assertTrue($file, t('File found in database.'));
  }


  function testFileEntityCreationMultipleSteps() {
    $test_file = $this->getTestFile('text');
    // Create a file.
    $edit = array();
    $edit['files[upload]'] = drupal_realpath($test_file->uri);
    $this->drupalPostForm('file/add', $edit, t('Next'));

    $this->assertTrue($this->xpath('//input[@name="scheme"]'), "Loaded select destination scheme page.");

    $this->assertFieldChecked('edit-scheme-public', 'Public Scheme is checked');

    $edit = array();
    $edit['scheme'] = 'private';
    $this->drupalPostForm(NULL, $edit, t('Next'));

    // Check that the document file has been uploaded.
    $this->assertRaw(t('!type %name was uploaded.', array('!type' => 'Document', '%name' => $test_file->filename)), t('Document file uploaded.'));

    $fids = \Drupal::entityQuery('file')->condition('filename', $test_file->filename)->execute();

    // Check that the file exists in the database.
    $file = $this->getFileByFilename($test_file->filename);

    $this->assertTrue($file, t('File found in database.'));
  }
}
