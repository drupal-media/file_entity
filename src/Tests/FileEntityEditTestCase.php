<?php

/**
 * @file
 * Test integration for the file_entity module.
 */

namespace Drupal\file_entity\Tests;

/**
 * Create a file and test file edit functionality.
 *
 * @group file_entity
 */

class FileEntityEditTestCase extends FileEntityTestBase {
  protected $web_user;
  protected $admin_user;

  function setUp() {
    parent::setUp();

    $this->web_user = $this->drupalCreateUser(array('edit own document files', 'create files'));
    $this->admin_user = $this->drupalCreateUser(array('bypass file access', 'administer files'));
  }

  /**
   * Check file edit functionality.
   */
  function testFileEntityEdit() {
    $this->drupalLogin($this->web_user);

    $test_file = $this->getTestFile('text');
    $name_key = "filename";

    // Create file to edit.
    $edit = array();
    $edit['files[upload]'] = drupal_realpath($test_file->uri);
    $this->drupalPost('file/add', $edit, t('Next'));
    if ($this->xpath('//input[@name="scheme"]')) {
      $this->drupalPost(NULL, array(), t('Next'));
    }

    // Check that the file exists in the database.
    $file = $this->getFileByFilename($test_file->filename);
    $this->assertTrue($file, t('File found in database.'));

    // Check that "edit" link points to correct page.
    $this->clickLink(t('Edit'));
    $edit_url = url("file/$file->fid/edit", array('absolute' => TRUE));
    $actual_url = $this->getURL();
    $this->assertEqual($edit_url, $actual_url, t('On edit page.'));

    // Check that the name field is displayed with the correct value.
    $active = '<span class="element-invisible">' . t('(active tab)') . '</span>';
    $link_text = t('!local-task-title!active', array('!local-task-title' => t('Edit'), '!active' => $active));
    $this->assertText(strip_tags($link_text), 0, t('Edit tab found and marked active.'));
    $this->assertFieldByName($name_key, $file->filename, t('Name field displayed.'));

    // The user does not have "delete" permissions so no delete button should be found.
    $this->assertNoFieldByName('op', t('Delete'), 'Delete button not found.');

    // Edit the content of the file.
    $edit = array();
    $edit[$name_key] = $this->randomName(8);
    // Stay on the current page, without reloading.
    $this->drupalPost(NULL, $edit, t('Save'));

    // Check that the name field is displayed with the updated values.
    $this->assertText($edit[$name_key], t('Name displayed.'));
  }

  /**
   * Check changing file associated user fields.
   */
  function testFileEntityAssociatedUser() {
    $this->drupalLogin($this->admin_user);

    // Create file to edit.
    $test_file = $this->getTestFile('text');
    $name_key = "filename";
    $edit = array();
    $edit['files[upload]'] = drupal_realpath($test_file->uri);
    $this->drupalPost('file/add', $edit, t('Next'));

    // Check that the file was associated with the currently logged in user.
    $file = $this->getFileByFilename($test_file->filename);
    $this->assertIdentical($file->uid, $this->admin_user->uid, 'File associated with admin user.');

    // Try to change the 'associated user' field to an invalid user name.
    $edit = array(
      'name' => 'invalid-name',
    );
    $this->drupalPost('file/' . $file->fid . '/edit', $edit, t('Save'));
    $this->assertText('The username invalid-name does not exist.');

    // Change the associated user field to an empty string, which should assign
    // association to the anonymous user (uid 0).
    $edit['name'] = '';
    $this->drupalPost('file/' . $file->fid . '/edit', $edit, t('Save'));
    $file = file_load($file->fid);
    $this->assertIdentical($file->uid, '0', 'File associated with anonymous user.');

    // Change the associated user field to another user's name (that is not
    // logged in).
    $edit['name'] = $this->web_user->name;
    $this->drupalPost('file/' . $file->fid . '/edit', $edit, t('Save'));
    $file = file_load($file->fid);
    $this->assertIdentical($file->uid, $this->web_user->uid, 'File associated with normal user.');

    // Check that normal users cannot change the associated user information.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('file/' . $file->fid . '/edit');
    $this->assertNoFieldByName('name');
  }
}
