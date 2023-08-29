<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_office_hours\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

require_once __DIR__ . '/../../../schemadotorg_office_hours.install';

/**
 * Tests the functionality of the Schema.org Office Hours integration.
 *
 * @cover schemadotorg_office_hours_schemadotorg_property_field_alter()
 * @covers schemadotorg_office_hours_schemadotorg_jsonld_schema_property_alter()
 * @group schemadotorg
 */
class SchemaDotOrgOfficeHoursJsonLdManagerTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'office_hours',
    'schemadotorg_jsonld',
    'schemadotorg_office_hours',
  ];

  /**
   * Schema.org JSON-LD builder.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface
   */
  protected $builder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['schemadotorg_jsonld']);

    $this->builder = $this->container->get('schemadotorg_jsonld.builder');
  }

  /**
   * Test Schema.org alter the JSON-LD eventSchedule property.
   *
   * @covers ::alterProperty
   */
  public function testOpening(): void {
    schemadotorg_office_hours_install(FALSE);

    // Create Place with eventSchedule.
    $this->createSchemaEntity('node', 'Place');

    // Create a place.
    $place_node = Node::create([
      'type' => 'place',
      'title' => 'Somewhere',
      'schema_opening_hours_spec' => [
        [
          'day' => 0,
          'starthours' => 1000,
          'endhours' => 1200,
          'comment' => 'Day 1',
        ],
        [
          'day' => 1,
          'starthours' => 1000,
          'endhours' => 1200,
          'comment' => 'Day 2',
        ],
        [
          'day' => 3,
          'starthours' => 1000,
          'endhours' => 1200,
          'comment' => 'Day 3',
        ],
      ],
    ]);
    $place_node->save();

    // Check openingHoursSpecification field storage settings.
    $field_storage_config = FieldStorageConfig::loadByName('node', 'schema_opening_hours_spec');
    $this->assertEquals(-1, $field_storage_config->getCardinality());
    $this->assertEquals([
      'time_format' => 'G',
      'element_type' => 'office_hours_datelist',
      'increment' => 30,
      'required_start' => FALSE,
      'limit_start' => '',
      'required_end' => FALSE,
      'limit_end' => '',
      'comment' => 1,
      'valhrs' => FALSE,
      'cardinality_per_day' => 2,
    ], $field_storage_config->getSettings());

    // Check Place openingHoursSpecification JSON-LD value.
    $expected_value = [
      [
        '@type' => 'OpeningHoursSpecification',
        'dayOfWeek' => 'https://schema.org/Sunday',
        'opens' => '10:00',
        'closes' => '12:00',
        'description' => 'Day 1',
      ],
      [
        '@type' => 'OpeningHoursSpecification',
        'dayOfWeek' => 'https://schema.org/Monday',
        'opens' => '10:00',
        'closes' => '12:00',
        'description' => 'Day 2',
      ],
      [
        '@type' => 'OpeningHoursSpecification',
        'dayOfWeek' => 'https://schema.org/Wednesday',
        'opens' => '10:00',
        'closes' => '12:00',
        'description' => 'Day 3',
      ],
    ];
    $jsonld = $this->builder->buildEntity($place_node);
    $this->assertEquals($expected_value, $jsonld['openingHoursSpecification']);
  }

}
