<?php
/**
 * @file
 * Contains \Drupal\file_entity\Tests\FileEntityServicesTest
 */

namespace Drupal\file_entity\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
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
    'basic_auth',
  );

  /**
   * Tests that a file field is correctly handled with REST.
   */
  public function testFileFieldREST() {
    $this->enableService('entity:node', 'GET');

    // Create user and log in.
    $account = $this->drupalCreateUser(array(
      'access content',
      'create resttest content',
      'restful get entity:node',
      'restful post entity:node',
    ));
    $this->drupalLogin($account);

    // Add a file field to the resttest content type.
    $file_field_storage = FieldStorageConfig::create(array(
      'type' => 'file',
      'entity_type' => 'node',
      'name' => 'field_file',
    ));
    $file_field_storage->save();
    $file_field_instance = FieldConfig::create(array(
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
        'display' => 0,
        'description' => 'An attached file',
      ),
      'status' => TRUE,
    ));
    $node->save();

    // GET node.
    $node_url = $node->getSystemPath();
    $response_json = $this->httpRequest($node_url, 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse(200);
    $response_data = Json::decode($response_json);

    // Test that field_file refers to the file entity.
    $normalized_field = $response_data['_embedded'][$this->getAbsoluteUrl('/rest/relation/node/resttest/field_file')];
    $this->assertEqual($normalized_field[0]['_links']['self']['href'], $this->getAbsoluteUrl($file->urlInfo()->toString()));

    // Remove the node.
    $node->delete();
    $this->httpRequest($node_url, 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse(404);

    // POST node to create new.
    $serialized = Json::encode($response_data);
    $this->enableService('entity:node', 'POST');
    $this->httpRequest('entity/node', 'POST', $serialized, $this->defaultMimeType);
    $this->assertResponse(201);

    // Test that the new node has a valid file field.
    $nodes = Node::loadMultiple();
    $last_node = array_pop($nodes);
    $this->assertEqual($last_node->get('field_file')->target_id, $file->id());
  }

}
