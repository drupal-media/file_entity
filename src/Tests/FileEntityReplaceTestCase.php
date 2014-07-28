<?php

class FileEntityReplaceTestCase extends FileEntityTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File replacement',
      'description' => 'Test file replace functionality.',
      'group' => 'File entity',
    );
  }

  function setUp() {
    parent::setUp();
    $this->setUpFiles();
  }

  /**
   * @todo Test image dimensions for an image field are reset when a file is replaced.
   * @todo Test image styles are cleared when an image is updated.
   */
  function testReplaceFile() {
    // Select the first text test file to use.
    $file = reset($this->files['text']);

    // Create a user with file edit permissions.
    $user = $this->drupalCreateUser(array('edit any document files'));
    $this->drupalLogin($user);

    // Test that the Upload widget appears for a local file.
    $this->drupalGet('file/' . $file->fid . '/edit');
    $this->assertFieldByName('files[replace_upload]');

    // Test that file saves without uploading a file.
    $this->drupalPost(NULL, array(), t('Save'));
    $this->assertText(t('Document @file has been updated.', array('@file' => $file->filename)), 'File was updated without file upload.');

    // Get the next text file to use as a replacement.
    $original = clone $file;
    $replacement = next($this->files['text']);

    // Test that the file saves when uploading a replacement file.
    $edit = array();
    $edit['files[replace_upload]'] = drupal_realpath($replacement->uri);
    $this->drupalPost('file/' . $file->fid . '/edit', $edit, t('Save'));
    $this->assertText(t('Document @file has been updated.', array('@file' => $file->filename)), 'File was updated with file upload.');

    // Re-load the file from the database.
    $file = file_load($file->fid);

    // Test how file properties changed after the file has been replaced.
    $this->assertEqual($file->filename, $original->filename, 'Updated file name did not change.');
    $this->assertNotEqual($file->filesize, $original->filesize, 'Updated file size changed from previous file.');
    $this->assertEqual($file->filesize, $replacement->filesize, 'Updated file size matches uploaded file.');
    $this->assertEqual(file_get_contents($file->uri), file_get_contents($replacement->uri), 'Updated file contents matches uploaded file.');
    $this->assertFalse(entity_load('file', FALSE, array('status' => 0)), 'Temporary file used for replacement was deleted.');

    // Get an image file.
    $image = reset($this->files['image']);
    $edit['files[replace_upload]'] = drupal_realpath($image->uri);

    // Test that validation works by uploading a non-text file as a replacement.
    $this->drupalPost('file/' . $file->fid . '/edit', $edit, t('Save'));
    $this->assertRaw(t('The specified file %file could not be uploaded. Only files with the following extensions are allowed:', array('%file' => $image->filename)), 'File validation works, upload failed correctly.');

    // Create a non-local file record.
    $file2 = new stdClass();
    $file2->uri = 'oembed://' . $this->randomName();
    $file2->filename = drupal_basename($file2->uri);
    $file2->filemime = 'image/oembed';
    $file2->type = 'image';
    $file2->uid = 1;
    $file2->timestamp = REQUEST_TIME;
    $file2->filesize = 0;
    $file2->status = 0;
    // Write the record directly rather than calling file_save() so we don't
    // invoke the hooks.
    $this->assertTrue(drupal_write_record('file_managed', $file2), 'Non-local file was added to the database.');

    // Test that Upload widget does not appear for non-local file.
    $this->drupalGet('file/' . $file2->fid . '/edit');
    $this->assertNoFieldByName('files[replace_upload]');
  }
}
