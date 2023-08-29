<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Unit\Breadcrumb;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \Drupal\schemadotorg\Breadcrumb\SchemaDotOrgBreadcrumbBuilder
 * @group schemadotorg
 */
class SchemaDotOrgBreadcrumbBuilderTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock cache contexts manager serer which is used by the
    // breadcrumb builder.
    // @see \Drupal\Core\Breadcrumb\Breadcrumb
    // @see \Drupal\Core\Cache\RefinableCacheableDependencyTrait
    // @see \Drupal\Core\Cache\Cache
    $cache_contexts_manager = $this->createMock('Drupal\Core\Cache\Context\CacheContextsManager');
    // Always return TRUE for ::assertValidTokens so that any cache context
    // will be accepted.
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);

    // Create a new container with the 'cache_contexts_manager'.
    $container = new Container();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests SchemaDotOrgBreadcrumbBuilder::applies().
   *
   * @param bool $expected
   *   SchemaDotOrgBreadcrumbBuilder::applies() expected result.
   * @param string|null $route_name
   *   (optional) A route name.
   *
   * @dataProvider providerTestApplies
   * @covers ::applies
   */
  public function testApplies(bool $expected, ?string $route_name = NULL): void {
    $breadcrumb_builder = $this->createPartialMock('\Drupal\schemadotorg\Breadcrumb\SchemaDotOrgBreadcrumbBuilder', []);

    $route_match = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');
    $route_match->expects($this->once())
      ->method('getRouteName')
      ->will($this->returnValue($route_name));

    $this->assertEquals($expected, $breadcrumb_builder->applies($route_match));
  }

  /**
   * Provides test data for testApplies().
   *
   * @return array
   *   Array of datasets for testApplies(). Structured as such:
   *   - SchemaDotOrgBreadcrumbBuilder::applies() expected result.
   *   - SchemaDotOrgBreadcrumbBuilder::applies() route name.
   */
  public function providerTestApplies(): array {
    return [
      [FALSE],
      [FALSE, 'schemadotorg'],
      [TRUE, 'schemadotorg.settings'],
      [TRUE, 'entity.schemadotorg_mapping'],
      [TRUE, 'entity.schemadotorg_mapping.add_form'],
    ];
  }

  /**
   * Tests ForumBreadcrumbBuilderBase::build().
   *
   * @see \Drupal\forum\Breadcrumb\ForumBreadcrumbBuilderBase::build()
   *
   * @covers ::build
   */
  public function testBuild(): void {
    // Build a breadcrumb builder to test.
    $breadcrumb_builder = $this->createPartialMock('\Drupal\schemadotorg\Breadcrumb\SchemaDotOrgBreadcrumbBuilder', []);

    // Add a translation manager for t().
    $translation_manager = $this->getStringTranslationStub();
    $breadcrumb_builder->setStringTranslation($translation_manager);

    // Check the breadcrumb links.
    $route_match = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');
    $breadcrumb = $breadcrumb_builder->build($route_match);
    $expected = [
      Link::createFromRoute('Home', '<front>'),
      Link::createFromRoute('Administration', 'system.admin'),
      Link::createFromRoute('Configuration', 'system.admin_config'),
      Link::createFromRoute('Search and metadata', 'system.admin_config_search'),
      Link::createFromRoute('Schema.org', 'entity.schemadotorg_mapping.collection'),
    ];
    $this->assertEquals($expected, $breadcrumb->getLinks());

    // Check the breadcrumb cache contexts.
    $this->assertEquals(['route'], $breadcrumb->getCacheContexts());

    // Check the breadcrumb cache max-age.
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());

    // Check the mapping  add breadcrumb links.
    $route_match = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');
    $route_match->method('getRouteName')->willReturn('entity.schemadotorg_mapping.add_form');
    $breadcrumb = $breadcrumb_builder->build($route_match);
    $expected = [
      Link::createFromRoute('Home', '<front>'),
      Link::createFromRoute('Administration', 'system.admin'),
      Link::createFromRoute('Configuration', 'system.admin_config'),
      Link::createFromRoute('Search and metadata', 'system.admin_config_search'),
      Link::createFromRoute('Schema.org', 'entity.schemadotorg_mapping.collection'),
      Link::createFromRoute('Mappings', 'entity.schemadotorg_mapping.collection'),
    ];
    $this->assertEquals($expected, $breadcrumb->getLinks());

    // Check the mapping type add breadcrumb links.
    $route_match = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');
    $route_match->method('getRouteName')->willReturn('entity.schemadotorg_mapping_type.add_form');
    $breadcrumb = $breadcrumb_builder->build($route_match);
    $expected = [
      Link::createFromRoute('Home', '<front>'),
      Link::createFromRoute('Administration', 'system.admin'),
      Link::createFromRoute('Configuration', 'system.admin_config'),
      Link::createFromRoute('Search and metadata', 'system.admin_config_search'),
      Link::createFromRoute('Schema.org', 'entity.schemadotorg_mapping.collection'),
      Link::createFromRoute('Mapping types', 'entity.schemadotorg_mapping_type.collection'),
    ];
    $this->assertEquals($expected, $breadcrumb->getLinks());
  }

}
