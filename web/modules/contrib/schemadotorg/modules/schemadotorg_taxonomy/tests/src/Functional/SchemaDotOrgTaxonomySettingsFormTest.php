<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_taxonomy\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org taxnomy settings form.
 *
 * @group schemadotorg
 */
class SchemaDotOrgTaxonomySettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org Taxonomy settings form.
   */
  public function testSettingsForm(): void {
    $this->assertSaveSettingsConfigForm('schemadotorg_taxonomy.settings', '/admin/config/search/schemadotorg/settings/types');
    $this->assertSaveSettingsConfigForm('schemadotorg_taxonomy.settings', '/admin/config/search/schemadotorg/settings/properties');
  }

}
