<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_jsonld\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org JSON-LD settings form.
 *
 * @covers \Drupal\schemadotorg_jsonld\Form\SchemaDotOrgJsonLdSettingsForm
 * @group schemadotorg
 */
class SchemaDotOrgJsonLdSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_jsonld'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org JSON-LD settings form.
   */
  public function testSettingsForm(): void {
    $this->assertSaveSettingsConfigForm('schemadotorg_jsonld.settings', '/admin/config/search/schemadotorg/settings/jsonld');
  }

}
