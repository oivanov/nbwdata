<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_paragraphs\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org layout paragraphs.
 *
 * @covers schemadotorg_layout_paragraphs_schemadotorg_mapping_defaults_alter()
 * @covers schemadotorg_layout_paragraphs_schemadotorg_property_field_alter()
 * @group schemadotorg
 */
class SchemaDotOrgLayoutParagraphsTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'schemadotorg_layout_paragraphs',
  ];

  /**
   * Test Schema.org layout paragraphs.
   */
  public function testLayoutParagraphs(): void {
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');

    // Create a WebPage with layout paragraphs.
    $mapping = $this->createSchemaEntity('node', 'WebPage');

    // Change the mapping was created with the mainEntity property.
    $this->assertEquals('WebPage', $mapping->getSchemaType());
    $this->assertEquals('mainEntity', $mapping->getSchemaPropertyMapping('schema_main_entity'));

    // Check that the page content type was created.
    $this->assertNotNull(NodeType::load('page'));

    // Check that the field storage is set up.
    $field_storage = FieldStorageConfig::loadByName('node', 'schema_main_entity');
    $this->assertEquals('entity_reference_revisions', $field_storage->getType());
    $this->assertEquals('paragraph', $field_storage->getSetting('target_type'));

    // Check that the field instance is set up.
    /** @var \Drupal\field\Entity\FieldConfig $field */
    $field = FieldConfig::loadByName('node', 'page', 'schema_main_entity');
    $handler_settings = $field->getSetting('handler_settings');
    $this->assertEquals([
      'layout' => 'layout',
      'header' => 'header',
      'statement' => 'statement',
      'quotation' => 'quotation',
      'item_list_string' => 'item_list_string',
      'item_list_link' => 'item_list_link',
      'item_list_text' => 'item_list_text',
      'collection_page' => 'collection_page',
      'media_gallery' => 'media_gallery',
      'image_gallery' => 'image_gallery',
      'video_gallery' => 'video_gallery',
      'node' => 'node',
    ], $handler_settings['target_bundles']);

    // Check that the form display is set up.
    $form_display = $entity_display_repository->getFormDisplay('node', 'page');
    $form_component = $form_display->getComponent('schema_main_entity');
    $this->assertEquals('layout_paragraphs', $form_component['type']);
    $this->assertNull($form_display->getThirdPartySetting('field_group', 'group_main_content'));

    // Check that the view display is set up.
    $view_display = $entity_display_repository->getViewDisplay('node', 'page');
    $view_component = $view_display->getComponent('schema_main_entity');
    $this->assertEquals('layout_paragraphs_builder', $view_component['type']);
    $this->assertEquals('hidden', $view_component['label']);
    $this->assertNull($view_display->getThirdPartySetting('field_group', 'group_main_content'));

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface $mapping_manager */
    $mapping_manager = $this->container->get('schemadotorg.mapping_manager');

    // Check the WebPage mapping defaults.
    $defaults = $mapping_manager->getMappingDefaults('node', NULL, 'WebPage');
    $expected_values = [
      'name' => 'schema_main_entity',
      'type' => 'field_ui:entity_reference_revisions:paragraph',
      'label' => 'Layout',
      'machine_name' => 'main_entity',
      'unlimited' => TRUE,
      'required' => FALSE,
      'description' => 'A layout built using paragraphs. Layout paragraphs allows site builders to construct a multi-column landing page using Schema.org related paragraphs types.',
    ];
    $this->assertEquals($expected_values, $defaults['properties']['mainEntity']);

    // Check that FAQ mapping defaults.
    $defaults = $mapping_manager->getMappingDefaults('node', NULL, 'FAQPage');
    $expected_values = [
      'name' => 'schema_main_entity',
      'type' => 'string',
      'label' => 'Questions',
      'machine_name' => 'main_entity',
      'unlimited' => TRUE,
      'required' => FALSE,
      'description' => 'Indicates the primary entity described in some page or other CreativeWork.',
    ];
    $this->assertEquals($expected_values, $defaults['properties']['mainEntity']);
  }

}
