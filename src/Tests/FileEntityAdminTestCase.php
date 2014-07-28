<?php

/**
 * @file
 * Contains \Drupal\file_entity\Tests\FileEntityAdminTestCase.
 */

namespace Drupal\file_entity\Tests;

/**
 * Test file administration page functionality.
 *
 * @group file_entity
 */
class FileEntityAdminTestCase extends FileEntityTestBase {

  function setUp() {
    parent::setUp();

    // Remove the "view files" permission which is set
    // by default for all users so we can test this permission
    // correctly.
    $roles = user_roles();
    foreach ($roles as $rid => $role) {
      user_role_revoke_permissions($rid, array('view files'));
    }

    $this->admin_user = $this->drupalCreateUser(array('administer files', 'bypass file access'));
    $this->base_user_1 = $this->drupalCreateUser(array('administer files'));
    $this->base_user_2 = $this->drupalCreateUser(array('administer files', 'view own private files'));
    $this->base_user_3 = $this->drupalCreateUser(array('administer files', 'view private files'));
    $this->base_user_4 = $this->drupalCreateUser(array('administer files', 'edit any document files', 'delete any document files', 'edit any image files', 'delete any image files'));
  }

  /**
   * Tests that the table sorting works on the files admin pages.
   */
  function testFilesAdminSort() {
    $this->drupalLogin($this->admin_user);
    $i = 0;
    foreach (array('dd', 'aa', 'DD', 'bb', 'cc', 'CC', 'AA', 'BB') as $prefix) {
      $this->createFileEntity(array('filepath' => $prefix . $this->randomName(6), 'timestamp' => $i));
      $i++;
    }

    // Test that the default sort by file_managed.timestamp DESC actually fires properly.
    $files_query = db_select('file_managed', 'fm')
      ->fields('fm', array('fid'))
      ->orderBy('timestamp', 'DESC')
      ->execute()
      ->fetchCol();

    $files_form = array();
    $this->drupalGet('admin/content/file');
    foreach ($this->xpath('//table/tbody/tr/td/div/input/@value') as $input) {
      $files_form[] = $input;
    }
    $this->assertEqual($files_query, $files_form, 'Files are sorted in the form according to the default query.');

    // Compare the rendered HTML node list to a query for the files ordered by
    // filename to account for possible database-dependent sort order.
    $files_query = db_select('file_managed', 'fm')
      ->fields('fm', array('fid'))
      ->orderBy('filename')
      ->execute()
      ->fetchCol();

    $files_form = array();
    $this->drupalGet('admin/content/file', array('query' => array('sort' => 'asc', 'order' => 'Title')));
    foreach ($this->xpath('//table/tbody/tr/td/div/input/@value') as $input) {
      $files_form[] = $input;
    }
    $this->assertEqual($files_query, $files_form, 'Files are sorted in the form the same as they are in the query.');
  }

  /**
   * Tests files overview with different user permissions.
   */
  function testFilesAdminPages() {
    $this->drupalLogin($this->admin_user);

    $files['public_image'] = $this->createFileEntity(array('scheme' => 'public', 'uid' => $this->base_user_1->uid, 'type' => 'image'));
    $files['public_document'] = $this->createFileEntity(array('scheme' => 'public', 'uid' => $this->base_user_2->uid, 'type' => 'document'));
    $files['private_image'] = $this->createFileEntity(array('scheme' => 'private', 'uid' => $this->base_user_1->uid, 'type' => 'image'));
    $files['private_document'] = $this->createFileEntity(array('scheme' => 'private', 'uid' => $this->base_user_2->uid, 'type' => 'document'));

    // Verify view, edit, and delete links for any file.
    $this->drupalGet('admin/content/file');
    $this->assertResponse(200);
    foreach ($files as $file) {
      $this->assertLinkByHref('file/' . $file->fid);
      $this->assertLinkByHref('file/' . $file->fid . '/edit');
      $this->assertLinkByHref('file/' . $file->fid . '/delete');
      // Verify tableselect.
      $this->assertFieldByName('files[' . $file->fid . ']', '', t('Tableselect found.'));
    }

    // Verify no operation links are displayed for regular users.
    $this->drupalLogout();
    $this->drupalLogin($this->base_user_1);
    $this->drupalGet('admin/content/file');
    $this->assertResponse(200);
    $this->assertLinkByHref('file/' . $files['public_image']->fid);
    $this->assertLinkByHref('file/' . $files['public_document']->fid);
    $this->assertNoLinkByHref('file/' . $files['public_image']->fid . '/edit');
    $this->assertNoLinkByHref('file/' . $files['public_image']->fid . '/delete');
    $this->assertNoLinkByHref('file/' . $files['public_document']->fid . '/edit');
    $this->assertNoLinkByHref('file/' . $files['public_document']->fid . '/delete');

    // Verify no tableselect.
    $this->assertNoFieldByName('files[' . $files['public_image']->fid . ']', '', t('No tableselect found.'));

    // Verify private file is displayed with permission.
    $this->drupalLogout();
    $this->drupalLogin($this->base_user_2);
    $this->drupalGet('admin/content/file');
    $this->assertResponse(200);
    $this->assertLinkByHref('file/' . $files['private_document']->fid);
    // Verify no operation links are displayed.
    $this->assertNoLinkByHref('file/' . $files['private_document']->fid . '/edit');
    $this->assertNoLinkByHref('file/' . $files['private_document']->fid . '/delete');

    // Verify user cannot see private file of other users.
    $this->assertNoLinkByHref('file/' . $files['private_image']->fid);
    $this->assertNoLinkByHref('file/' . $files['private_image']->fid . '/edit');
    $this->assertNoLinkByHref('file/' . $files['private_image']->fid . '/delete');

    // Verify no tableselect.
    $this->assertNoFieldByName('files[' . $files['private_document']->fid . ']', '', t('No tableselect found.'));

    // Verify private file is displayed with permission.
    $this->drupalLogout();
    $this->drupalLogin($this->base_user_3);
    $this->drupalGet('admin/content/file');
    $this->assertResponse(200);

    // Verify user can see private file of other users.
    $this->assertLinkByHref('file/' . $files['private_document']->fid);
    $this->assertLinkByHref('file/' . $files['private_image']->fid);

    // Verify operation links are displayed for users with appropriate permission.
    $this->drupalLogout();
    $this->drupalLogin($this->base_user_4);
    $this->drupalGet('admin/content/file');
    $this->assertResponse(200);
    foreach ($files as $file) {
      $this->assertLinkByHref('file/' . $file->fid);
      $this->assertLinkByHref('file/' . $file->fid . '/edit');
      $this->assertLinkByHref('file/' . $file->fid . '/delete');
    }

    // Verify file access can be bypassed.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/content/file');
    $this->assertResponse(200);
    foreach ($files as $file) {
      $this->assertLinkByHref('file/' . $file->fid);
      $this->assertLinkByHref('file/' . $file->fid . '/edit');
      $this->assertLinkByHref('file/' . $file->fid . '/delete');
    }
  }
}
