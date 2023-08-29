<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Schema.org entity display builder service.
 */
class SchemaDotOrgEntityDisplayBuilder implements SchemaDotOrgEntityDisplayBuilderInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Schema.org config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The Schema.org names service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface
   */
  protected $schemaNames;

  /**
   * Constructs a SchemaDotOrgBuilder object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository
   *   The entity display repository.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names
   *   The Schema.org names service.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $display_repository,
    SchemaDotOrgNamesInterface $schema_names
  ) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $display_repository;
    $this->schemaNames = $schema_names;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFieldWeights(): array {
    $weights = $this->configFactory
      ->get('schemadotorg.settings')
      ->get('schema_properties.default_field_weights');
    $weights = array_flip($weights);
    // Start field weights at 1 since most default fields are set to 0.
    array_walk($weights, function (&$weight): void {
      $weight += 1;
    });
    return $weights;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldDisplays(array $field_values, ?string $widget_id, array $widget_settings, ?string $formatter_id, array $formatter_settings): void {
    $entity_type_id = $field_values['entity_type'];
    $bundle = $field_values['bundle'];
    $field_name = $field_values['field_name'];

    // Form display.
    $form_modes = $this->getFormModes($entity_type_id, $bundle);
    foreach ($form_modes as $form_mode) {
      $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle, $form_mode);
      $this->setComponent($form_display, $field_name, $widget_id, $widget_settings);
      $form_display->save();
    }

    // View display.
    $view_modes = $this->getViewModes($entity_type_id, $bundle);
    foreach ($view_modes as $view_mode) {
      $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle, $view_mode);
      $this->setComponent($view_display, $field_name, $formatter_id, $formatter_settings);
      $view_display->save();
    }
  }

  /**
   * Set entity display component.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display.
   * @param string $field_name
   *   The field name to be set.
   * @param string|null $type
   *   The component's plugin id.
   * @param array $settings
   *   The component's plugin settings.
   */
  protected function setComponent(EntityDisplayInterface $display, string $field_name, ?string $type, array $settings): void {
    // Only add the 'body' to 'teaser' and 'content_browser' view modes
    // for node types. This mirrors the default behavior for
    // adding new node types.
    // @see \Drupal\node\NodeTypeForm::save
    // @see node_add_body_field()
    if ($this->isNodeTeaserDisplay($display)) {
      if ($field_name !== 'body') {
        $display->removeComponent($field_name);
        return;
      }
      $type = 'text_summary_or_trimmed';
      $settings = ['label' => 'hidden'];
    }

    $options = [];

    // Set custom component type.
    if ($type) {
      $options['type'] = $type;
    }

    // Converted some $settings to $options.
    if (!empty($settings)) {
      $option_names = ['label', 'third_party_settings'];
      foreach ($option_names as $option_name) {
        if (isset($settings[$option_name])) {
          $options[$option_name] = $settings[$option_name];
          unset($settings[$option_name]);
        }
      }
      $options['settings'] = $settings;
    }

    $display->setComponent($field_name, $options);
  }

  /**
   * Set entity display field weights for Schema.org properties.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param array $properties
   *   The Schema.org properties to be weighted.
   */
  public function setFieldWeights(string $entity_type_id, string $bundle, array $properties): void {
    // Form display.
    $form_modes = $this->getFormModes($entity_type_id, $bundle);
    foreach ($form_modes as $form_mode) {
      $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle, $form_mode);
      foreach ($properties as $field_name => $property) {
        $this->setFieldWeight($form_display, $field_name, $property);
      }
      $form_display->save();
    }

    // View display.
    $view_modes = $this->getViewModes($entity_type_id, $bundle);
    foreach ($view_modes as $view_mode) {
      $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle, $view_mode);
      foreach ($properties as $field_name => $property) {
        $this->setFieldWeight($view_display, $field_name, $property);
      }
      $view_display->save();
    }
  }

  /**
   * Set entity display field weight for a Schema.org property.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display.
   * @param string $field_name
   *   The field name to be set.
   * @param string $schema_property
   *   The field name's associated Schema.org property.
   */
  protected function setFieldWeight(EntityDisplayInterface $display, string $field_name, string $schema_property): void {
    // Make sure the field component exists.
    if (!$display->getComponent($field_name)) {
      return;
    }

    $default_field_weights = $this->getDefaultFieldWeights();
    if (empty($default_field_weights)) {
      return;
    }

    // Use the property's default field weight or the lowest weight plus one.
    $field_weight = $default_field_weights[$schema_property] ?? max($default_field_weights) + 1;

    $component = $display->getComponent($field_name);
    $component['weight'] = $field_weight;
    $display->setComponent($field_name, $component);
  }

  /**
   * {@inheritdoc}
   */
  public function setComponentWeights(string $entity_type_id, string $bundle): void {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface $mapping_type */
    $mapping_type = $this->entityTypeManager
      ->getStorage('schemadotorg_mapping_type')
      ->load($entity_type_id);
    if (!$mapping_type) {
      return;
    }

    $component_weights = $mapping_type->getDefaultComponentWeights();
    if (empty($component_weights)) {
      return;
    }

    $this->setDisplayComponentWeights($entity_type_id, $bundle, 'form', $component_weights);
    $this->setDisplayComponentWeights($entity_type_id, $bundle, 'view', $component_weights);
  }

  /**
   * Set the display component weights.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The entity bundle.
   * @param string $display_type
   *   The entity display type.
   * @param array $component_weights
   *   The entity display component weights.
   */
  protected function setDisplayComponentWeights(string $entity_type_id, string $bundle, string $display_type, array $component_weights): void {
    $display_type = ucfirst($display_type);
    $modes_method = "get{$display_type}Modes";
    $display_method = "get{$display_type}Display";

    $modes = $this->$modes_method($entity_type_id, $bundle);
    foreach ($modes as $mode) {
      $display = $this->entityDisplayRepository->$display_method($entity_type_id, $bundle, $mode);
      foreach ($component_weights as $component_name => $component_weight) {
        $is_updated = FALSE;
        $component = $display->getComponent($component_name);
        if ($component && isset($component['region'])) {
          $component['weight'] = $component_weight;
          $display->setComponent($component_name, $component);
          $is_updated = TRUE;
        }
      }
      if ($is_updated) {
        $display->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isNodeTeaserDisplay(EntityDisplayInterface $display): bool {
    $entity_type_id = $display->getTargetEntityTypeId();
    $mode = $display->getMode();
    if ($mode !== EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE
      && $display instanceof EntityViewDisplayInterface
      && $entity_type_id === 'node'
      && in_array($mode, ['teaser', 'content_browser'])) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModes(string $entity_type_id, string $bundle): array {
    return $this->getModes(
      $entity_type_id,
      $bundle,
      'Form',
      []
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModes(string $entity_type_id, string $bundle): array {
    $default_view_modes = ['teaser', 'content_browser'];
    return $this->getModes(
      $entity_type_id,
      $bundle,
      'View',
      $default_view_modes
    );
  }

  /**
   * Get display modes for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $type
   *   The display modes.
   * @param array $default_modes
   *   An array of default display modes.
   *
   * @return array
   *   An array of display modes.
   */
  protected function getModes(string $entity_type_id, string $bundle, string $type = 'View', array $default_modes = []): array {
    $mode_method = "get{$type}ModeOptionsByBundle";
    $mode_options = $this->entityDisplayRepository->$mode_method($entity_type_id, $bundle);

    if ($default_modes) {
      $modes = array_intersect_key(
        array_combine($default_modes, $default_modes),
        $mode_options
      );
    }
    else {
      $mode_keys = array_keys($mode_options);
      $modes = array_combine($mode_keys, $mode_keys);
    }

    return ['default' => 'default'] + $modes;
  }

}
