<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_jsonapi\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org JSON:API settings form.
 *
 * @covers \Drupal\schemadotorg_jsonapi\Form\SchemaDotOrgDemoSettingsForm
 * @group schemadotorg
 */
class SchemaDotOrgJsonApiSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_jsonapi'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org JSON:API settings form.
   */
  public function testSettingsForm(): void {
    $this->assertSaveSettingsConfigForm('schemadotorg_jsonapi.settings', '/admin/config/search/schemadotorg/settings/jsonapi');
  }

}
