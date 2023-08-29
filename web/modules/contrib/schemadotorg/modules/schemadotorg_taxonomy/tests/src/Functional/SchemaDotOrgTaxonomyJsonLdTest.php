<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_taxonomy\Functional;

use Drupal\Core\Url;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org taxonomy JSON-LD support.
 *
 * @group schemadotorg
 */
class SchemaDotOrgTaxonomyJsonLdTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'filter',
    'schemadotorg_jsonld',
    'schemadotorg_jsonld_endpoint',
    'schemadotorg_jsonld_preview',
    'schemadotorg_taxonomy',
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
    $this->builder = $this->container->get('schemadotorg_jsonld.builder');
  }

  /**
   * Test Schema.org taxonomy.
   */
  public function testTaxonomy(): void {
    $assert_session = $this->assertSession();

    $vocabulary = Vocabulary::create([
      'name' => 'tags',
      'vid' => 'tags',
    ]);
    $vocabulary->save();

    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'taxonomy_term',
      'target_bundle' => 'tags',
      'schema_type' => 'DefinedTerm',
      'schema_properties' => [
        'name' => 'name',
      ],
    ])->save();

    // Create a Schema.org mapping for Thing.
    $term = Term::create([
      'vid' => 'tags',
      'name' => 'Some term',
    ]);
    $term->save();

    $vocabulary = Vocabulary::load('tags');

    // Check term JSON-LD..
    $expected_result = [
      '@type' => 'DefinedTerm',
      '@url' => $term->toUrl()->setAbsolute()->toString(),
      'identifier' => [
          [
            '@type' => 'PropertyValue',
            'propertyID' => 'uuid',
            'value' => $term->uuid(),
          ],
      ],
      'name' => 'Some term',
      'inDefinedTermSet' => [
        '@type' => 'DefinedTermSet',
        'name' => 'tags',
        'identifier' => [
          [
            '@type' => 'PropertyValue',
            'propertyID' => 'uuid',
            'value' => $vocabulary->uuid(),
          ],
        ],
      ],
    ];
    $actual_result = $this->builder->buildEntity($term);
    $this->assertEquals($expected_result, $actual_result);

    // Check term JSON-LD..
    $expected_result = [
      '@type' => 'DefinedTermSet',
      'identifier' => [
        [
          '@type' => 'PropertyValue',
          'propertyID' => 'uuid',
          'value' => $vocabulary->uuid(),
        ],
      ],
      'name' => 'tags',
    ];
    $actual_result = $this->builder->buildEntity($vocabulary);
    $this->assertEquals($expected_result, $actual_result);

    $term_endpoint_url = Url::fromRoute('schemadotorg_jsonld_endpoint.taxonomy_term', ['entity' => $term->uuid()])->setAbsolute();
    $vocabulary_endpoint_url = Url::fromRoute('schemadotorg_jsonld_endpoint.taxonomy_vocabulary', ['entity' => $vocabulary->uuid()])->setAbsolute();

    $this->drupalLogin($this->rootUser);

    // Check term and vocabulary preview label and links.
    $this->drupalGet('/taxonomy/term/' . $term->id());
    $assert_session->responseContains('JSON-LD Term endpoint');
    $assert_session->linkExists($term_endpoint_url->toString());
    $assert_session->responseContains('JSON-LD Vocabulary endpoint');
    $assert_session->linkExists($vocabulary_endpoint_url->toString());

    // Check term and vocabulary endpoints.
    $this->drupalGet($term_endpoint_url->toString());
    $assert_session->statusCodeEquals(200);
    $this->drupalGet($vocabulary_endpoint_url->toString());
    $assert_session->statusCodeEquals(200);
  }

}
