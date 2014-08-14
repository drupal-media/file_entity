<?php
/**
 * @file
 * Contains \Drupal\file_entity\Tests\FileEntityServicesTest
 */

namespace Drupal\file_entity\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\field\Entity\FieldInstanceConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests File entity REST services
 *
 * @group file_entity
 */
class FileEntityServicesTest extends RESTTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = array(
    'node',
    'hal',
    'file_entity',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->enableService('entity:node');
  }

  /**
   * Tests that the entity reference normalizer is used.
   */
  public function testNormalizer() {
    // Create user and log in.
    $this->drupalLogin($this->drupalCreateUser(array(
      'access content',
      'restful get entity:node',
    )));

    // Setup node type to use file field.
    $file_field_storage = FieldStorageConfig::create(array(
      'type' => 'file',
      'entity_type' => 'node',
      'name' => 'field_file',
    ));
    $file_field_storage->save();
    $file_field_instance = FieldInstanceConfig::create(array(
      'field_storage' => $file_field_storage,
      'entity_type' => 'node',
      'bundle' => 'resttest',
    ));
    $file_field_instance->save();

    // Create a file.
    $file_uri = 'public://' . $this->randomMachineName();
    file_put_contents($file_uri, 'This is some file contents');
    $file = File::create(array('uri' => $file_uri));
    $file->save();

    // Create a node with a file.
    $node = entity_create('node', array(
      'title' => 'A node with a file',
      'type' => 'resttest',
      'field_file' => array(
        'target_id' => $file->id(),
        'display' => TRUE,
        'description' => '',
      ),
      'status' => TRUE,
    ));
    $node->save();

    // GET node.
    $url = url($node->getSystemPath(), array('absolute' => FALSE));
    $response_json = $this->httpRequest($url, 'GET', NULL, $this->defaultMimeType);
    $response_data = Json::decode($response_json);

    // Test that field_file refers to the file entity.
    $normalized_field = $response_data['_embedded'][$this->getAbsoluteUrl('/rest/relation/node/resttest/field_file')];
    $this->assertEqual($normalized_field[0]['_links']['self']['href'], $this->getAbsoluteUrl($file->urlInfo()->toString()));
  }

  /**
   * Tests file normalization and denormalization.
   */
  public function testFileNormalize() {
    // Create user and log in.
    $this->drupalLogin($this->drupalCreateUser(array(
      'view files',
      'restful get entity:file',
    )));

    foreach ($this->drupalGetTestFiles('binary') as $file_uri) {
      $file_contents = file_get_contents($file_uri);

      // Create file entity.
      $file = File::create(array('uri' => $file_uri));
      $file->save();

      // Get normalized entity.
      $response = Json::decode($this->httpRequest($file->urlInfo()->toString(), 'GET', NULL, $this->defaultMimeType));

      // Remove file.
      $file->delete();
      $this->assertEqual(FALSE, file_exists($file_uri));

      // Post normalized entity.
      unset($response['uuid']);
      $this->httpRequest($this->getAbsoluteUrl('/entity/file'), 'POST', $response, $this->defaultMimeType);
      $this->assertResponse(201);
      $last_file = array_pop(File::loadMultiple());

      // Assert file is equal.
      foreach (array('filename', 'uri', 'filemime', 'filesize', 'type') as $property) {
        $this->assertEqual($response[$property], $last_file[$property]);
      }
      $this->assertEqual($file_contents, file_get_contents($last_file->urlInfo()->toString()), 'File contents are equal.');
    }
  }
}
