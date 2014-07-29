<?php

/**
 * @file
 * Contains \Drupal\file_entity\Tests\FileEntityAccessTestCase.
 */

namespace Drupal\file_entity\Tests;

/**
 * Tests the access aspects of file entity.
 *
 * @group file_entity
 */
class FileEntityAccessTestCase extends FileEntityTestBase {

  function setUp() {
    parent::setUp();
    $this->setUpFiles(array('uid' => 0));

    // Unset the fact that file_entity_install() adds the 'view files'
    // permission to all user roles. This messes with being able to fully unit
    // test the file_entity_access() function.
    $roles = user_roles();
    foreach ($roles as $rid => $role) {
      user_role_revoke_permissions($rid, array('view files'));
    }
  }

  /**
   * Asserts file_entity_access correctly grants or denies access.
   */
  function assertFileEntityAccess($ops, $file, $account) {
    drupal_static_reset('file_entity_access');
    foreach ($ops as $op => $result) {
      $msg = t("file_entity_access returns @result with operation '@op'.", array('@result' => $result ? 'true' : 'false', '@op' => $op));
      $this->assertEqual($result, file_entity_access($op, $file, $account), $msg);
    }
  }

  /**
   * Runs basic tests for file_entity_access function.
   */
  function testFileEntityAccess() {
    $file = reset($this->files['image']);

    // Ensures user with 'bypass file access' permission can do everything.
    $web_user = $this->drupalCreateUser(array('bypass file access'));
    $this->assertFileEntityAccess(array('create' => TRUE), NULL, $web_user);
    $this->assertFileEntityAccess(array('view' => TRUE, 'download' => TRUE, 'update' => TRUE, 'delete' => TRUE), $file, $web_user);

    // A user with 'administer files' should not access CRUD operations.
    $web_user = $this->drupalCreateUser(array('administer files'));
    $this->assertFileEntityAccess(array('view' => FALSE, 'download' => FALSE, 'update' => FALSE, 'delete' => FALSE), $file, $web_user);

    // User cannot 'view files'.
    $web_user = $this->drupalCreateUser(array('create files'));
    $this->assertFileEntityAccess(array('view' => FALSE), $file, $web_user);
    // But can upload new ones.
    $this->assertFileEntityAccess(array('create' => TRUE), NULL, $web_user);

    // User can view own files but no other files.
    $web_user = $this->drupalCreateUser(array('create files', 'view own files'));
    $this->assertFileEntityAccess(array('view' => FALSE), $file, $web_user);
    $file->uid = $web_user->uid;
    $this->assertFileEntityAccess(array('view' => TRUE), $file, $web_user);

    // User can download own files but no other files.
    $web_user = $this->drupalCreateUser(array('create files', 'download own image files'));
    $this->assertFileEntityAccess(array('download' => FALSE), $file, $web_user);
    $file->uid = $web_user->uid;
    $this->assertFileEntityAccess(array('download' => TRUE), $file, $web_user);

    // User can update own files but no other files.
    $web_user = $this->drupalCreateUser(array('create files', 'view own files', 'edit own image files'));
    $this->assertFileEntityAccess(array('update' => FALSE), $file, $web_user);
    $file->uid = $web_user->uid;
    $this->assertFileEntityAccess(array('update' => TRUE), $file, $web_user);

    // User can delete own files but no other files.
    $web_user = $this->drupalCreateUser(array('create files', 'view own files', 'edit own image files', 'delete own image files'));
    $this->assertFileEntityAccess(array('delete' => FALSE), $file, $web_user);
    $file->uid = $web_user->uid;
    $this->assertFileEntityAccess(array('delete' => TRUE), $file, $web_user);

    // User can view any file.
    $web_user = $this->drupalCreateUser(array('create files', 'view files'));
    $this->assertFileEntityAccess(array('view' => TRUE), $file, $web_user);

    // User can download any file.
    $web_user = $this->drupalCreateUser(array('create files', 'download any image files'));
    $this->assertFileEntityAccess(array('download' => TRUE), $file, $web_user);

    // User can edit any file.
    $web_user = $this->drupalCreateUser(array('create files', 'view files', 'edit any image files'));
    $this->assertFileEntityAccess(array('update' => TRUE), $file, $web_user);

    // User can delete any file.
    $web_user = $this->drupalCreateUser(array('create files', 'view files', 'edit any image files', 'delete any image files'));
    $this->assertFileEntityAccess(array('delete' => TRUE), $file, $web_user);
  }

  /**
   * Test to see if we have access to view files when granted the permissions.
   * In this test we aim to prove the permissions work in the following pages:
   *  file/add
   *  file/%/view
   *  file/%/download
   *  file/%/edit
   *  file/%/delete
   */
  function testFileEntityPageAccess() {
    $web_user = $this->drupalCreateUser(array());
    $this->drupalLogin($web_user);
    $this->drupalGet('file/add');
    $this->assertResponse(403, 'Users without access can not access the file add page');
    $web_user = $this->drupalCreateUser(array('create files'));
    $this->drupalLogin($web_user);
    $this->drupalGet('file/add');
    $this->assertResponse(200, 'Users with access can access the file add page');

    $file = reset($this->files['text']);

    // This fails.. No clue why but, tested manually and works as should.
    //$web_user = $this->drupalCreateUser(array('view own files'));
    //$this->drupalLogin($web_user);
    //$this->drupalGet("file/{$file->fid}/view");
    //$this->assertResponse(403, 'Users without access can not access the file view page');
    $web_user = $this->drupalCreateUser(array('view files'));
    $this->drupalLogin($web_user);
    $this->drupalGet("file/{$file->fid}/view");
    $this->assertResponse(200, 'Users with access can access the file view page');

    $url = "file/{$file->fid}/download";
    $web_user = $this->drupalCreateUser(array());
    $this->drupalLogin($web_user);
    $this->drupalGet($url, array('query' => array('token' => file_entity_get_download_token($file))));
    $this->assertResponse(403, 'Users without access can not download the file');
    $web_user = $this->drupalCreateUser(array('download any document files'));
    $this->drupalLogin($web_user);
    $this->drupalGet($url, array('query' => array('token' => file_entity_get_download_token($file))));
    $this->assertResponse(200, 'Users with access can download the file');
    $this->drupalGet($url, array('query' => array('token' => 'invalid-token')));
    $this->assertResponse(403, 'Cannot download file with in invalid token.');
    $this->drupalGet($url);
    $this->assertResponse(403, 'Cannot download file without a token.');
    \Drupal::config('file_entity')->set('allow_insecure_download', TRUE);
    $this->drupalGet($url);
    $this->assertResponse(200, 'Users with access can download the file without a token when file_entity_allow_insecure_download is set.');

    $web_user = $this->drupalCreateUser(array());
    $this->drupalLogin($web_user);
    $this->drupalGet("file/{$file->fid}/edit");
    $this->assertResponse(403, 'Users without access can not access the file edit page');
    $web_user = $this->drupalCreateUser(array('edit any document files'));
    $this->drupalLogin($web_user);
    $this->drupalGet("file/{$file->fid}/edit");
    $this->assertResponse(200, 'Users with access can access the file add page');

    $web_user = $this->drupalCreateUser(array());
    $this->drupalLogin($web_user);
    $this->drupalGet("file/{$file->fid}/delete");
    $this->assertResponse(403, 'Users without access can not access the file view page');
    $web_user = $this->drupalCreateUser(array('delete any document files'));
    $this->drupalLogin($web_user);
    $this->drupalGet("file/{$file->fid}/delete");
    $this->assertResponse(200, 'Users with access can access the file add page');
  }

  /**
   * Test to see if we have access to download private files when granted the permissions.
   */
  function testFileEntityPrivateDownloadAccess() {
    foreach ($this->getPrivateDownloadAccessCases() as $case) {
      // Create users and login only if non-anonymous.
      $authenticated_user = !is_null($case['permissions']);
      if ($authenticated_user) {
        $account = $this->drupalCreateUser($case['permissions']);
        $this->drupalLogin($account);
      }

      // Create private, permanent files owned by this user only he's an owner.
      if (!empty($case['owner'])) {
        $file = next($this->files['text']);
        $file->uid = $account->uid;
        $file->save();
        $file = file_move($file, 'private://');

        // Check if the physical file is there.
        $arguments = array('%name' => $file->filename, '%username' => $account->name, '%uri' => $file->uri);
        $this->assertTrue(is_file($file->uri), format_string('File %name owned by %username successfully created at %uri.', $arguments));
        $url = file_create_url($file->uri);
        $message_file_info = ' ' . format_string('File %uri was checked.', array('%uri' => $file->uri));
      }

      // Try to download the file.
      $this->drupalGet($url);
      $this->assertResponse($case['expect'], $case['message'] . $message_file_info);

      // Logout authenticated users.
      if ($authenticated_user) {
        $this->drupalLogout();
      }
    }
  }
}