<?php

/**
 * @file
 * Contains \Drupal\file_entity\Tests\FileEntityAdminTest.
 */

namespace Drupal\file_entity\Tests;

use Drupal\file_entity\Entity\FileEntity;
use Drupal\views\Entity\View;
use Symfony\Component\DependencyInjection\SimpleXMLElement;

/**
 * Test file administration page functionality.
 *
 * @group file_entity
 */
class FileEntityAdminTest extends FileEntityTestBase {

  protected $userAdmin;
  protected $userBasic;
  protected $userViewOwn;
  protected $userViewPrivate;
  protected $userEditDelete;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Remove the "view files" permission which is set
    // by default for all users so we can test this permission
    // correctly.
    $roles = user_roles();
    foreach ($roles as $rid => $role) {
      user_role_revoke_permissions($rid, array('view files'));
    }

    $this->userAdmin = $this->drupalCreateUser(array('administer files', 'bypass file access'));
    $this->userBasic = $this->drupalCreateUser(array('administer files'));
    $this->userViewOwn = $this->drupalCreateUser(array('administer files', 'view own private files'));
    $this->userViewPrivate = $this->drupalCreateUser(array('administer files', 'view private files'));
    $this->userEditDelete = $this->drupalCreateUser(array(
      'administer files',
      'edit any document files',
      'delete any document files',
      'edit any image files',
      'delete any image files',
    ));

    // Enable the enhanced Files view.
    View::load('files')->disable()->save();
    View::load('file_entity_files')->enable()->save();
  }

  /**
   * Tests that the table sorting works on the files admin pages.
   */
  public function testFilesAdminSort() {
    $this->drupalLogin($this->userAdmin);
    $i = 0;
    foreach (array('dd', 'aa', 'DD', 'bb', 'cc', 'CC', 'AA', 'BB') as $prefix) {
      $this->createFileEntity(array('filename' => $prefix . $this->randomName(6), 'created' => $i * 90000));
      $i++;
    }

    // Test that the default sort by file_managed.created DESC fires properly.
    $files_query = db_select('file_managed', 'fm')
      ->fields('fm', array('filename'))
      ->orderBy('created', 'DESC')
      ->execute()
      ->fetchCol();

    $this->drupalGet('admin/content/files');
    $this->assertEqual($files_query, $this->xpath('//table/tbody/tr/td[2]/a'), 'Files are sorted in the view according to the default query.');

    // Compare the rendered HTML node list to a query for the files ordered by
    // filename to account for possible database-dependent sort order.
    $files_query = db_select('file_managed', 'fm')
      ->fields('fm', array('filename'))
      ->orderBy('filename')
      ->execute()
      ->fetchCol();

    $this->drupalGet('admin/content/files', array('query' => array('sort' => 'asc', 'order' => 'filename')));
    $this->assertEqual($files_query, $this->xpath('//table/tbody/tr/td[2]/a'), 'Files are sorted in the view the same as they are in the query.');
  }

  /**
   * Tests files overview with different user permissions.
   */
  public function dtestFilesAdminPages() {
    $this->drupalLogin($this->userAdmin);

    /** @var FileEntity[] $files */
    $files['public_image'] = $this->createFileEntity(array(
      'scheme' => 'public',
      'uid' => $this->userBasic->uid,
      'type' => 'image',
    ));
    $files['public_document'] = $this->createFileEntity(array(
      'scheme' => 'public',
      'uid' => $this->userViewOwn->uid,
      'type' => 'document',
    ));
    $files['private_image'] = $this->createFileEntity(array(
      'scheme' => 'private',
      'uid' => $this->userBasic->uid,
      'type' => 'image',
    ));
    $files['private_document'] = $this->createFileEntity(array(
      'scheme' => 'private',
      'uid' => $this->userViewOwn->uid,
      'type' => 'document',
    ));

    // Verify view, edit, and delete links for any file.
    $this->drupalGet('admin/content/files');
    $this->assertResponse(200);
    foreach ($files as $file) {
      $this->assertLinkByHref('file/' . $file->id());
      $this->assertLinkByHref('file/' . $file->id() . '/edit');
      $this->assertLinkByHref('file/' . $file->id() . '/delete');
      // Verify tableselect.
      $this->assertFieldByName('files[' . $file->id() . ']', '', t('Tableselect found.'));
    }

    // Verify no operation links are displayed for regular users.
    $this->drupalLogout();
    $this->drupalLogin($this->userBasic);
    $this->drupalGet('admin/content/files');
    $this->assertResponse(200);
    $this->assertLinkByHref('file/' . $files['public_image']->id());
    $this->assertLinkByHref('file/' . $files['public_document']->id());
    $this->assertNoLinkByHref('file/' . $files['public_image']->id() . '/edit');
    $this->assertNoLinkByHref('file/' . $files['public_image']->id() . '/delete');
    $this->assertNoLinkByHref('file/' . $files['public_document']->id() . '/edit');
    $this->assertNoLinkByHref('file/' . $files['public_document']->id() . '/delete');

    // Verify no tableselect.
    $this->assertNoFieldByName('files[' . $files['public_image']->id() . ']', '', t('No tableselect found.'));

    // Verify private file is displayed with permission.
    $this->drupalLogout();
    $this->drupalLogin($this->userViewOwn);
    $this->drupalGet('admin/content/files');
    $this->assertResponse(200);
    $this->assertLinkByHref('file/' . $files['private_document']->id());
    // Verify no operation links are displayed.
    $this->assertNoLinkByHref('file/' . $files['private_document']->id() . '/edit');
    $this->assertNoLinkByHref('file/' . $files['private_document']->id() . '/delete');

    // Verify user cannot see private file of other users.
    $this->assertNoLinkByHref('file/' . $files['private_image']->id());
    $this->assertNoLinkByHref('file/' . $files['private_image']->id() . '/edit');
    $this->assertNoLinkByHref('file/' . $files['private_image']->id() . '/delete');

    // Verify no tableselect.
    $this->assertNoFieldByName('files[' . $files['private_document']->id() . ']', '', t('No tableselect found.'));

    // Verify private file is displayed with permission.
    $this->drupalLogout();
    $this->drupalLogin($this->userViewPrivate);
    $this->drupalGet('admin/content/files');
    $this->assertResponse(200);

    // Verify user can see private file of other users.
    $this->assertLinkByHref('file/' . $files['private_document']->id());
    $this->assertLinkByHref('file/' . $files['private_image']->id());

    // Verify operation links are displayed for users with appropriate
    // permission.
    $this->drupalLogout();
    $this->drupalLogin($this->userEditDelete);
    $this->drupalGet('admin/content/files');
    $this->assertResponse(200);
    foreach ($files as $file) {
      $this->assertLinkByHref('file/' . $file->id());
      $this->assertLinkByHref('file/' . $file->id() . '/edit');
      $this->assertLinkByHref('file/' . $file->id() . '/delete');
    }

    // Verify file access can be bypassed.
    $this->drupalLogout();
    $this->drupalLogin($this->userAdmin);
    $this->drupalGet('admin/content/files');
    $this->assertResponse(200);
    foreach ($files as $file) {
      $this->assertLinkByHref('file/' . $file->id());
      $this->assertLinkByHref('file/' . $file->id() . '/edit');
      $this->assertLinkByHref('file/' . $file->id() . '/delete');
    }
  }
}
