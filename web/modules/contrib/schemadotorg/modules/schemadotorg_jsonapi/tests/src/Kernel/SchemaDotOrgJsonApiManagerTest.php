<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_jsonapi\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\jsonapi_extras\Entity\JsonapiResourceConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelTestBase;
use Drupal\Tests\schemadotorg_subtype\Traits\SchemaDotOrgTestSubtypeTrait;

/**
 * Tests the functionality of the Schema.org JSON:API manager.
 *
 * @covers \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApiManager;
 * @group schemadotorg
 */
class SchemaDotOrgJsonApiManagerTest extends SchemaDotOrgKernelTestBase {
  use SchemaDotOrgTestSubtypeTrait;

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'options',
    'file',
    'serialization',
    'jsonapi',
    'jsonapi_extras',
    'schemadotorg_subtype',
    'schemadotorg_jsonapi',
  ];

  /**
   * The JSON:API resource storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $resourceStorage;

  /**
   * The Schema.org mapping storage.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingStorage
   */
  protected $mappingStorage;

  /**
   * Schema.org JSON:API manager.
   *
   * @var \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApiManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('schemadotorg_mapping');
    $this->installEntitySchema('schemadotorg_mapping_type');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);
    $this->installConfig(['schemadotorg']);
    $this->installConfig(['schemadotorg_jsonapi']);

    $this->installer = $this->container->get('schemadotorg.installer');
    $this->installer->install();

    $this->mappingStorage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping');
    $this->resourceStorage = $this->container->get('entity_type.manager')->getStorage('jsonapi_resource_config');
    $this->manager = $this->container->get('schemadotorg_jsonapi.manager');
  }

  /**
   * Test Schema.org JSON:API manager.
   */
  public function testSchemaDotOrgJsonApiManager(): void {

    /* ********************************************************************** */
    // Insert Schema.org mapping JSON:API resource config.
    // @see \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApi::insertMappingResourceConfig
    /* ********************************************************************** */

    // Create Thing node with field.
    $thing_node = NodeType::create([
      'type' => 'thing',
      'name' => 'Thing',
    ]);
    $thing_node->save();
    $this->createSchemaDotOrgField('node', 'Thing');
    $this->createSchemaDotOrgSubTypeField('node', 'Thing');

    // Create Thing with mapping.
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $thing_mapping */
    $thing_mapping = $this->mappingStorage->create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'thing',
      'schema_type' => 'Thing',
      'schema_properties' => [
        'title' => 'name',
        'schema_alternate_name' => 'alternateName',
        'schema_thing_subtype' => 'subtype',
      ],
    ]);
    $thing_mapping->save();

    // Check that JSON:API resource was created for Thing.
    /** @var \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig $resource */
    $resource = $this->resourceStorage->load('node--thing');
    $resource_fields = $resource->get('resourceFields');

    // Check enabling selected base fields.
    $this->assertFalse($resource_fields['status']['disabled']);
    $this->assertFalse($resource_fields['langcode']['disabled']);
    $this->assertFalse($resource_fields['title']['disabled']);

    // Check enabling selected Schema.org fields.
    $this->assertFalse($resource_fields['schema_thing_subtype']['disabled']);
    $this->assertFalse($resource_fields['schema_alternate_name']['disabled']);

    // Check disabling internal fields.
    $this->assertTrue($resource_fields['revision_timestamp']['disabled']);
    $this->assertTrue($resource_fields['revision_uid']['disabled']);
    $this->assertTrue($resource_fields['revision_log']['disabled']);

    // Check that Schema.org property base field public names are not aliased.
    $this->assertEquals('status', $resource_fields['status']['publicName']);
    $this->assertEquals('langcode', $resource_fields['langcode']['publicName']);
    $this->assertEquals('title', $resource_fields['title']['publicName']);

    // Check that Schema.org property field public names are aliased.
    $this->assertEquals('subtype', $resource_fields['schema_thing_subtype']['publicName']);
    $this->assertEquals('alternate_name', $resource_fields['schema_alternate_name']['publicName']);

    /* ********************************************************************** */
    // Update Schema.org mapping JSON:API resource config.
    // @see \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApi::updateMappingResourceConfig
    /* ********************************************************************** */

    // Remove alterateName from mapping.
    $thing_mapping
      ->removeSchemaProperty('schema_alternate_name')
      ->save();

    // Check that existing resource field is unchanged.
    $resource = $this->loadResource('node--thing');
    $resource_fields = $resource->get('resourceFields');
    $this->assertEquals('alternate_name', $resource_fields['schema_alternate_name']['publicName']);

    /* ********************************************************************** */
    // Insert field into JSON:API resource config.
    // @see \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApi::insertFieldConfigResource
    /* ********************************************************************** */

    // Insert new field outside of the mapping.
    // Add some field.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'some_field',
      'type' => 'string',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'thing',
      'field_name' => 'some_field',
      'label' => 'Some field',
    ])->save();
    $resource = $this->loadResource('node--thing');
    $resource_fields = $resource->get('resourceFields');
    $this->assertTrue($resource_fields['some_field']['disabled']);

    // Insert new Schema.org description field.
    $this->createSchemaDotOrgField('node', 'Thing', 'description');

    // Check not inserting field into JSON:API resource config if the Scheme.org
    // entity type builder is adding it via the 'schemaDotOrgAddFieldToEntity'
    // property.
    $resource = $this->loadResource('node--thing');
    $resource_fields = $resource->get('resourceFields');
    $this->assertArrayNotHasKey('schema_description', $resource_fields);

    // Add description to the Thing mapping and save.
    $thing_mapping
      ->setSchemaPropertyMapping('schema_description', 'description')
      ->save();

    // Check that new Schema.org field is now added to the  JSON:API resource.
    $resource = $this->loadResource('node--thing');
    $resource_fields = $resource->get('resourceFields');
    $this->assertArrayHasKey('schema_description', $resource_fields);

    /* ********************************************************************** */
    // Enable type aliases.
    /* ********************************************************************** */

    // Use Schema.org types as the JSON:API resource's type and path names.
    \Drupal::configFactory()
      ->getEditable('schemadotorg_jsonapi.settings')
      ->set('resource_type_schemadotorg', TRUE)
      ->save();

    // Create Place (Location) with mapping.
    $location_node = NodeType::create([
      'type' => 'location',
      'name' => 'Location',
    ]);
    $location_node->save();
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $location_mapping */
    $location_mapping = $this->mappingStorage->create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'location',
      'schema_type' => 'place',
    ]);
    $location_mapping->save();

    // Check that Schema.org types are used as the JSON:API resource's
    // type and path names.
    $resource = $this->loadResource('node--location');
    $this->assertNotEquals('node--location', $resource->get('resourceType'));
    $this->assertEquals('node--place', $resource->get('resourceType'));
    $this->assertNotEquals('node/location', $resource->get('path'));
    $this->assertEquals('node/place', $resource->get('path'));

    /* ********************************************************************** */
    // Enabling all base fields.
    /* ********************************************************************** */

    // Enable all base fields by leaving it blank and enable base field aliases.
    \Drupal::configFactory()
      ->getEditable('schemadotorg_jsonapi.settings')
      ->set('default_base_fields', [])
      ->set('resource_base_field_schemadotorg', TRUE)
      ->save();

    // Create Event with mapping.
    $event_node = NodeType::create([
      'type' => 'event',
      'name' => 'Event',
    ]);
    $event_node->save();
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $event_mapping */
    $event_mapping = $this->mappingStorage->create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'event',
      'schema_type' => 'Event',
      'schema_properties' => [
        'langcode' => 'inLanguage',
        'title' => 'name',
      ],
    ]);
    $event_mapping->save();

    // Check that JSON:API resource was created for Event.
    /** @var \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig $resource */
    $resource = $this->resourceStorage->load('node--event');
    $resource_fields = $resource->get('resourceFields');

    // Check enabled internal fields.
    $this->assertFalse($resource_fields['revision_timestamp']['disabled']);
    $this->assertFalse($resource_fields['revision_uid']['disabled']);
    $this->assertFalse($resource_fields['revision_log']['disabled']);

    // Check that Schema.org property base field public names are aliased.
    $this->assertEquals('status', $resource_fields['status']['publicName']);
    $this->assertEquals('in_language', $resource_fields['langcode']['publicName']);
    $this->assertEquals('name', $resource_fields['title']['publicName']);
  }

  /**
   * Load a JSON:API resource.
   *
   * @param string $id
   *   Resource ID.
   *
   * @return \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig
   *   A JSON:API resource.
   */
  protected function loadResource(string $id): JsonapiResourceConfig {
    $this->resourceStorage->resetCache([$id]);
    return $this->resourceStorage->load($id);
  }

}
