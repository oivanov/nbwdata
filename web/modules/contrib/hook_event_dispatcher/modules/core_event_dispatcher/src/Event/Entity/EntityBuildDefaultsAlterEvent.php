<?php

namespace Drupal\core_event_dispatcher\Event\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\core_event_dispatcher\EntityHookEvents;

/**
 * Class EntityBuildDefaultsAlterEvent.
 *
 * @HookEvent(
 *   id = "entity_build_defaults_alter",
 *   alter = "entity_build_defaults"
 * )
 */
class EntityBuildDefaultsAlterEvent extends AbstractEntityEvent {

  /**
   * A renderable array representing the entity content.
   *
   * @var array
   */
  private $build = [];

  /**
   * The view mode.
   *
   * @var string
   */
  private $viewMode;

  /**
   * EntityBuildDefaultsAlterEvent constructor.
   *
   * @param array &$build
   *   A renderable array representing the entity content. It will not have
   *   other elements aside from the entity and a #cache parameter. The
   *   structure of $build is a renderable array as expected by drupal_render().
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $viewMode
   *   The view mode the entity is rendered in.
   */
  public function __construct(
    array &$build,
    EntityInterface $entity,
    string $viewMode
  ) {
    parent::__construct($entity);

    $this->build = &$build;
    $this->viewMode = $viewMode;
  }

  /**
   * Get the build.
   *
   * @return array
   *   The build.
   */
  public function &getBuild(): array {
    return $this->build;
  }

  /**
   * Get the view mode.
   *
   * @return string
   *   The view mode.
   */
  public function getViewMode(): string {
    return $this->viewMode;
  }

  /**
   * {@inheritdoc}
   */
  public function getDispatcherType(): string {
    return EntityHookEvents::ENTITY_BUILD_DEFAULTS_ALTER;
  }

}
