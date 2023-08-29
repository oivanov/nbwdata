<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_content_moderation\Kernel;

use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests the functionality of the Schema.org content moderation.
 *
 * @covers schemadotorg_content_moderation_schemadotorg_mapping_inser()
 * @group schemadotorg
 */
class SchemaDotOrgContentModerationTest extends SchemaDotOrgKernelEntityTestBase {
  use ContentModerationTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'system',
    'user',
    'workflows',
    'content_moderation',
    'schemadotorg_content_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig([
      'content_moderation',
      'schemadotorg_content_moderation',
    ]);
    $this->createEditorialWorkflow();
  }

  /**
   * Test Schema.org content moderation.
   */
  public function testContentModeration(): void {
    // Enable the editorial workflow for all nodes except Person.
    $this->config('schemadotorg_content_moderation.settings')
      ->set('default_workflows', [
        'node' => 'editorial',
        'node--Person' => '',
      ])
      ->save();

    // Create event, place, and person Schema.org content types.
    $this->createSchemaEntity('node', 'Event');
    $this->createSchemaEntity('node', 'Place');
    $this->createSchemaEntity('node', 'Person');

    // Check that editorial workflow for all nodes except Person.
    $workflow = Workflow::load('editorial');
    /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface $content_moderation */
    $content_moderation = $workflow->getTypePlugin();
    $this->assertEquals(
      ['event', 'place'],
      $content_moderation->getBundlesForEntityType('node')
    );
  }

}
