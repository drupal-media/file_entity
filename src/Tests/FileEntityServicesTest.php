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
}
