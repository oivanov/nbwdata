<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_taxonomy\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org taxonomy default vocabulary manager.
 *
 * @covers \Drupal\schemadotorg_taxonomy\SchemaDotOrgTaxonomyDefaultVocabularyManager
 * @group schemadotorg
 */
class SchemaDotOrgTaxonomyDefaultVocabularyManagerTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'language',
    'content_translation',
    'taxonomy',
    'field_group',
    'schemadotorg_taxonomy',
  ];

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['schemadotorg_taxonomy']);

    $this->contentTranslationManager = $this->container->get('content_translation.manager');
  }

  /**
   * Test Schema.org taxonomy property vocabulary manager.
   */
  public function testManager(): void {
    // Check that the tags vocabulary does not exist.
    $this->assertNull(Vocabulary::load('tags'));

    // Create an Article.
    $this->createSchemaEntity('node', 'Article');

    // Check that the tags vocabulary does exist.
    $this->assertNotNull(Vocabulary::load('tags'));

    // Check that the field storage is created.
    $this->assertNotNull(FieldStorageConfig::loadByName('node', 'field_tags'));

    // Check that the field is created.
    $this->assertNotNull(FieldConfig::loadByName('node', 'article', 'field_tags'));

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');

    // Create that the form display and component are created.
    $form_display = $entity_display_repository->getFormDisplay('node', 'article');
    $this->assertNotNull($form_display);
    $form_component = $form_display->getComponent('field_tags');
    $this->assertEquals('entity_reference_autocomplete_tags', $form_component['type']);
    $form_group = $form_display->getThirdPartySetting('field_group', 'group_taxonomy');
    $this->assertEquals('Tags / Categories', $form_group['label']);
    $this->assertEquals('details', $form_group['format_type']);
    $this->assertEquals(['field_tags'], $form_group['children']);

    // Create that the view display and component are created.
    $view_display = $entity_display_repository->getViewDisplay('node', 'article');
    $this->assertNotNull($view_display);
    $view_component = $view_display->getComponent('field_tags');
    $this->assertEquals('entity_reference_label', $view_component['type']);
    $view_group = $view_display->getThirdPartySetting('field_group', 'group_taxonomy');
    $this->assertEquals('Tags / Categories', $view_group['label']);
    $this->assertEquals('fieldset', $view_group['format_type']);
    $this->assertEquals(['field_tags'], $view_group['children']);

    // Check that tags vocabulary is translated.
    $this->assertNotNull(ContentLanguageSettings::load('taxonomy_term.tags'));
    $this->assertTrue($this->contentTranslationManager->isEnabled('taxonomy_term', 'tags'));
  }

}
