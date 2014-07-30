<?php

/**
 * @file
 * Contains \Drupal\file_entity\Tests\FileEntityTypeTestCase.
 */

namespace Drupal\file_entity\Tests;

use Drupal\field\Entity\FieldInstanceConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file_entity\Entity\FileType;

/**
 * Tests the file entity types.
 *
 * @group file_entity
 */
class FileEntityTypeTestCase extends FileEntityTestBase {

  function setUp() {
    parent::setUp();
    $this->setUpFiles();
  }

  /**
   * Test admin pages access and functionality.
   */
  function dtestAdminPages() {
    // Create a user with file type administration access.
    $user = $this->drupalCreateUser(array('administer file types'));
    $this->drupalLogin($user);

    $this->drupalGet('admin/structure/file-types');
    $this->assertResponse(200, 'File types admin page is accessible');
  }

  /**
   * Test creating a new type. Basic CRUD.
   */
  function dtestCreate() {
    $type_machine_type = 'foo';
    $type_machine_label = 'foobar';
    $type = $this->createFileType(array('id' => $type_machine_type, 'label' => $type_machine_label));
    $loaded_type = FileType::load($type_machine_type);
    $this->assertEqual($loaded_type->label(), $type_machine_label, "Was able to create a type and retreive it.");
  }


  /**
   * Ensures that the weight is respected when types are created.
   * @return unknown_type
   */
  function dtestOrder() {
//    $type = $this->createFileType(array('name' => 'last', 'label' => 'Last', 'weight' => 100));
//    $type = $this->createFileType(array('name' => 'first', 'label' => 'First'));
//    $types = media_type_get_types();
//    $keys = array_keys($types);
//    $this->assertTrue(isset($types['last']) && isset($types['first']), "Both types saved");
//    $this->assertTrue(array_search('last', $keys) > array_search('first', $keys), 'Type which was supposed to be first came first');
  }

  /**
   * Test view mode assignment.  Currently fails, don't know why.
   * @return unknown_type
   */
  function dtestViewModesAssigned() {
  }

  /**
   * Make sure candidates are presented in the case of multiple
   * file types.
   */
  function dtestTypeWithCandidates() {
    // Create multiple file types with the same mime types.
    $types = array(
      'image1' => $this->createFileType(array('type' => 'image1', 'label' => 'Image 1')),
      'image2' => $this->createFileType(array('type' => 'image2', 'label' => 'Image 2')),
    );
    $field_name = drupal_strtolower($this->randomName());

    // Attach a text field to one of the file types.
    // @todo @see node_add_body_field() in node.module
    $field_storage = FieldStorageConfig::create(array(
      'name' => $field_name,
      'type' => 'text',
      'settings' => array(
        'max_length' => 255,
      ),
    ));
    $field_storage->save();
    $field_instance = FieldInstanceConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'file',
      'bundle' => 'image2',
      'widget' => array(
        'type' => 'text_textfield',
      ),
      'display' => array(
        'default' => array(
          'type' => 'text_default',
        ),
      ),
    ));
    $field_instance->save();

    // Create a user with file creation access.
    $user = $this->drupalCreateUser(array('create files'));
    $this->drupalLogin($user);

    // Step 1: Upload file
    $file = reset($this->files['image']);
    $edit = array();
    $edit['files[upload]'] = drupal_realpath($file->uri);
    $this->drupalPostForm('file/add', $edit, t('Next'));

    // Step 2: Select file type candidate
    $this->assertText('Image 1', 'File type candidate list item found.');
    $this->assertText('Image 2', 'File type candidate list item found.');
    $edit = array();
    $edit['type'] = 'image2';
    $this->drupalPostForm(NULL, $edit, t('Next'));

    // Step 3: Select file scheme candidate
    $this->assertText('Public local files served by the webserver.', 'File scheme candidate list item found.');
    $this->assertText('Private local files served by Drupal.', 'File scheme candidate list item found.');
    $edit = array();
    $edit['scheme'] = 'public';
    $this->drupalPostForm(NULL, $edit, t('Next'));

    // Step 4: Complete field widgets
    $langcode = LANGUAGE_NONE;
    $edit = array();
    $edit["{$field_name}[$langcode][0][value]"] = $this->randomName();
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('!type %name was uploaded.', array('!type' => 'Image 2', '%name' => $file->filename)), t('Image 2 file updated.'));
    $this->assertText($field_name, 'File text field was found.');
  }

  /**
   * Make sure no candidates appear when only one mime type is available.
   * NOTE: Depends on file_entity.module default 'image' type.
   */
  function dtestTypeWithoutCandidates() {
    // Attach a text field to the default image file type.
    $field_name = drupal_strtolower($this->randomName());
    $field_storage = FieldStorageConfig::create(array(
      'name' => $field_name,
      'type' => 'text',
      'settings' => array(
        'max_length' => 255,
      ),
    ));
    $field_storage->save();
    $field_instance = FieldInstanceConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'file',
      'bundle' => 'image',
      'widget' => array(
        'type' => 'text_textfield',
      ),
      'display' => array(
        'default' => array(
          'type' => 'text_default',
        ),
      ),
    ));
    $field_instance->save();

    // Create a user with file creation access.
    $user = $this->drupalCreateUser(array('create files'));
    $this->drupalLogin($user);

    // Step 1: Upload file
    $file = reset($this->files['image']);
    $edit = array();
    $edit['files[upload]'] = drupal_realpath($file->uri);
    $this->drupalPostForm('file/add', $edit, t('Next'));

    // Step 2: Scheme selection
    if ($this->xpath('//input[@name="scheme"]')) {
      $this->drupalPostForm(NULL, array(), t('Next'));
    }

    // Step 3: Complete field widgets
    $langcode = LANGUAGE_NONE;
    $edit = array();
    $edit["{$field_name}[$langcode][0][value]"] = $this->randomName();
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('!type %name was uploaded.', array('!type' => 'Image', '%name' => $file->filename)), t('Image file uploaded.'));
    $this->assertText($field_name, 'File text field was found.');
  }

  /**
   * Test file types CRUD UI.
   */
  function testTypesCrudUi() {
    $this->drupalGet('admin/structure/file-types');
    $this->assertResponse(403, 'File types UI page is not accessible to unauthorized users.');

    $user = $this->drupalCreateUser(array('administer file types'));
    $this->drupalLogin($user);

    $this->drupalGet('admin/structure/file-types');
    $this->assertResponse(200, 'File types UI page is accessible to users with adequate permission.');

    // Create new file type.
    $edit = array(
      'label' => t('Test type'),
      'id' => 'test_type',
      'description' => t('This is dummy file type used just for testing.'),
      'mimetypes' => 'image/png',
    );
    $this->drupalGet('admin/structure/file-types/add');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('The file type @type has been added.', array('@type' => $edit['label'])), 'New file type successfully created.');
    $this->assertText($edit['label'], 'New file type created: label found.');
    $this->assertText($edit['description'], 'New file type created: description found.');
    $this->assertLink(t('Disable'), 0, 'Able to disable newly created file type.');
    $this->assertLink(t('Delete'), 0, 'Able to delete newly created file type.');
    $this->assertLinkByHref('admin/structure/file-types/manage/' . $edit['id'] . '/disable', 0, 'Disable link points to disable confirmation page.');
    $this->assertLinkByHref('admin/structure/file-types/manage/' . $edit['id'] . '/delete', 0, 'Delete link points to delete confirmation page.');

    // Edit file type.
    $this->drupalGet('admin/structure/file-types/manage/' . $edit['id'] . '/edit');
    $this->assertRaw(t('Save'), 'Save button found on edit page.');
    $this->assertRaw(t('Delete'), 'Delete button found on edit page.');
    $this->assertRaw($edit['label'], 'Label found on file type edit page');
    $this->assertText($edit['description'], 'Description found on file type edit page');
    $this->assertText($edit['mimetypes'], 'Mime-type configuration found on file type edit page');
    $this->assertText(t('Known MIME types'), 'Mimetype list present on edit form.');

    // Modify file type.
    $edit['label'] = t('New type label');
    $this->drupalPostForm(NULL, array('label' => $edit['label']), t('Save'));
    $this->assertText(t('The file type @type has been updated.', array('@type' => $edit['label'])), 'File type was modified.');
    $this->assertText($edit['label'], 'Modified label found on file types list.');

    // Disable and re-enable file type.
    $this->drupalGet('admin/structure/file-types/manage/' . $edit['id'] . '/disable');
    $this->assertText(t('Are you sure you want to disable the file type @type?', array('@type' => $edit['label'])), 'Disable confirmation page found.');
    $this->drupalPostForm(NULL, array(), t('Disable'));
    $this->assertText(t('The file type @type has been disabled.', array('@type' => $edit['label'])), 'Disable confirmation message found.');
    $this->assertFieldByXPath("//tbody//tr[5]//td[1]", $edit['label'], 'Disabled type moved to the tail of the list.');
    $this->assertLink(t('Enable'), 0, 'Able to re-enable newly created file type.');
    $this->assertLinkByHref('admin/structure/file-types/manage/' . $edit['id'] . '/enable', 0, 'Enable link points to enable confirmation page.');
    $this->drupalGet('admin/structure/file-types/manage/' . $edit['id'] . '/enable');
    $this->assertText(t('Are you sure you want to enable the file type @type?', array('@type' => $edit['label'])), 'Enable confirmation page found.');
    $this->drupalPostForm(NULL, array(), t('Enable'));
    $this->assertText(t('The file type @type has been enabled.', array('@type' => $edit['label'])), 'Enable confirmation message found.');
    $this->assertFieldByXPath("//tbody//tr[4]//td[1]", $edit['label'], 'Enabled type moved from the bottom of the list.');

    // Delete newly created type.
    $this->drupalGet('admin/structure/file-types/manage/' . $edit['id'] . '/delete');
    $this->assertText(t('Are you sure you want to delete the file type @type?', array('@type' => $edit['label'])), 'Delete confirmation page found.');
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertText(t('The file type @type has been deleted.', array('@type' => $edit['label'])), 'Delete confirmation message found.');
    $this->drupalGet('admin/structure/file-types');
    $this->assertNoText($edit['label'], 'File type successfully deleted.');

    // Edit pre-defined file type.
    $this->drupalGet('admin/structure/file-types/manage/image/edit');
    $this->assertRaw(t('Image'), 'Label found on file type edit page');
    $this->assertText("image/*", 'Mime-type configuration found on file type edit page');
    $this->drupalPostForm(NULL, array('label' => t('Funky images')), t('Save'));
    $this->assertText(t('The file type @type has been updated.', array('@type' => t('Funky images'))), 'File type was modified.');
    $this->assertText(t('Funky image'), 'Modified label found on file types list.');
  }
}
