<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_descriptions\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigCollectionInfo;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideBase;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg\Utility\SchemaDotOrgStringHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides Schema.org descriptions overrides for the configuration factory.
 *
 * @see \Drupal\config_override\SiteConfigOverrides
 * @see \Drupal\language\Config\LanguageConfigFactoryOverride
 * @see https://www.flocondetoile.fr/blog/dynamically-override-configuration-drupal-8
 * @see https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-override-system
 */
class SchemaDotOrgDescriptionsConfigFactoryOverride extends ConfigFactoryOverrideBase implements ConfigFactoryOverrideInterface, EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * The cache id.
   */
  const CACHE_ID = 'schemadotorg_descriptions.override';

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Default cache backend instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $defaultCacheBackend;

  /**
   * Discovery cache backend instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $discoveryCacheBackend;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * The Schema.org schema type builder.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface
   */
  protected $schemaTypeBuilder;

  /**
   * Constructs a SchemaDotOrgDescriptionsConfigFactoryOverride object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $default_cache_backend
   *   The default cache backend.
   * @param \Drupal\Core\Cache\CacheBackendInterface $discovery_cache_backend
   *   The discovery cache backend.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface $schema_type_builder
   *   The Schema.org schema type builder.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    CacheBackendInterface $default_cache_backend,
    CacheBackendInterface $discovery_cache_backend,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager,
    SchemaDotOrgSchemaTypeBuilderInterface $schema_type_builder
  ) {
    $this->configFactory = $config_factory;
    $this->defaultCacheBackend = $default_cache_backend;
    $this->discoveryCacheBackend = $discovery_cache_backend;
    $this->schemaTypeManager = $schema_type_manager;
    $this->schemaTypeBuilder = $schema_type_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names): array {
    $overrides = $this->getDescriptionOverrides();
    return array_intersect_key($overrides, array_flip($names));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix(): string {
    return 'schemadotorg_descriptions';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name): CacheableMetadata {
    $metadata = new CacheableMetadata();
    $metadata->addCacheTags(['schemadotorg_descriptions.settings']);
    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION): StorableConfigBase|null {
    return NULL;
  }

  /**
   * Reacts to the ConfigEvents::COLLECTION_INFO event.
   *
   * @param \Drupal\Core\Config\ConfigCollectionInfo $collection_info
   *   The configuration collection info event.
   */
  public function addCollections(ConfigCollectionInfo $collection_info): void {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    $this->onConfigChange($event);
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigDelete(ConfigCrudEvent $event): void {
    $this->onConfigChange($event);
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigRename(ConfigRenameEvent $event): void {
    $this->onConfigChange($event);
  }

  /**
   * Actions to be performed to configuration override on configuration rename.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The config event.
   */
  public function onConfigChange(ConfigCrudEvent $event): void {
    $config = $event->getConfig();
    $name = $config->getName();

    // Purge cached overrides when any mapping is updated.
    if (str_starts_with($name, 'schemadotorg.schemadotorg_mapping.')) {
      $this->resetDescriptionOverrides();
      return;
    }

    // Purge cached overrides when an entity or field definition is updated.
    $overrides = $this->getDescriptionOverrides();
    if (isset($overrides[$name])) {
      $this->resetDescriptionOverrides();
    }
  }

  /**
   * Reset Schema.org description configuration overrides.
   */
  public function resetDescriptionOverrides(): void {
    // Reset config.
    $this->configFactory->reset();
    // Reset default cache item.
    $this->defaultCacheBackend->delete(static::CACHE_ID);
    // Reset the entire plugin discovery cache.
    $this->discoveryCacheBackend->deleteAll();
  }

  /**
   * Get Schema.org description configuration overrides.
   *
   * @return array
   *   An array of description configuration overrides for
   *   mapped entity types and fields.
   */
  public function getDescriptionOverrides(): array {
    if ($cache = $this->defaultCacheBackend->get(static::CACHE_ID)) {
      return $cache->data;
    }

    $overrides = [];
    $type_overrides = [];
    $property_overrides = [];
    // Load the unaltered or not overridden Schema.org mapping configuration.
    $config_names = $this->configFactory->listAll('schemadotorg.schemadotorg_mapping.');
    foreach ($config_names as $config_name) {
      $config = $this->configFactory->getEditable($config_name);

      $schema_type = $config->get('schema_type');
      $entity_type_id = $config->get('target_entity_type_id');
      $bundle = $config->get('target_bundle');

      // Set entity type override.
      $type_overrides["$entity_type_id.type.$bundle"] = $schema_type;

      // Set entity field instance override.
      $type_property_overrides = [];
      $schema_properties = $config->get('schema_properties') ?: [];
      foreach ($schema_properties as $field_name => $schema_property) {
        $type_property_overrides["field.field.$entity_type_id.$bundle.$field_name"] = $schema_property;
      }
      $this->setItemDescriptionOverrides('properties', $type_property_overrides, $schema_type);
      $property_overrides += $type_property_overrides;
    }

    $this->setItemDescriptionOverrides('types', $type_overrides);
    $overrides += $type_overrides + $property_overrides;

    $this->defaultCacheBackend->set(static::CACHE_ID, $overrides);

    return $overrides;
  }

  /**
   * Set configuration override descriptions for Schema.org types or properties.
   *
   * @param string $table
   *   Schema.org types or properties table.
   * @param array $overrides
   *   An associative array of configuration overrides.
   * @param string $type
   *   The Schema.org type.
   *
   * @return array
   *   Donfiguration override descriptions for Schema.org types or properties.
   */
  protected function setItemDescriptionOverrides(string $table, array &$overrides, string $type = ''): array {
    $items = $this->schemaTypeManager->getItems($table, $overrides, ['label', 'comment']);
    $options = ['base_path' => 'https://schema.org/'];

    $trim_descriptions = $this->configFactory
      ->getEditable('schemadotorg_descriptions.settings')
      ->get('trim_descriptions');
    $help_descriptions = $this->configFactory
      ->getEditable('schemadotorg_descriptions.settings')
      ->get('help_descriptions');
    $custom_descriptions = $this->configFactory
      ->getEditable('schemadotorg_descriptions.settings')
      ->get('custom_descriptions');
    foreach ($overrides as $config_name => $id) {
      if ($custom_descriptions && array_key_exists("$type--$id", $custom_descriptions)) {
        $description = $custom_descriptions["$type--$id"];
        $help = $custom_descriptions["$type--$id"];
      }
      elseif ($custom_descriptions && array_key_exists($id, $custom_descriptions)) {
        $description = $custom_descriptions[$id];
        $help = $custom_descriptions[$id];
      }
      elseif (isset($items[$id])) {
        $comment = $items[$id]['comment'];
        // Tidy <br/> tags.
        $comment = preg_replace('#<br[^>]*]>#', '<br/>', $comment);
        // Trim description.
        if ($trim_descriptions) {
          $comment = SchemaDotOrgStringHelper::getFirstSentence($comment);
        }
        $description = $this->schemaTypeBuilder->formatComment($comment, $options);
        $help = $description;
      }
      else {
        $description = '';
        $help = '';
      }

      $data = $this->configFactory->getEditable($config_name)->getRawData();

      if (empty($data)
        || !empty($data['description'])
        || empty($description)) {
        // Having empty overrides allows use to easily purge them as needed.
        // @see \Drupal\schemadotorg_descriptions\Config\SchemaDotOrgDescriptionsConfigFactoryOverride::onConfigChange
        $overrides[$config_name] = [];
      }
      else {
        $overrides[$config_name] = [
          'description' => $description,
        ];
        if ($help_descriptions) {
          $overrides[$config_name]['help'] = $help;
        }
      }
    }

    return $overrides;
  }

}
