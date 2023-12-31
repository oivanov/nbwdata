<?php

namespace Drupal\entity_events\EventSubscriber;

use Drupal\entity_events\EntityEventType;
use Drupal\entity_events\Event\EntityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for entity presave event.
 */
abstract class EntityEventPresaveSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[EntityEventType::PRESAVE][] = ['onEntityPresave', 800];
    return $events;
  }

  /**
   * Method called when Event occurs.
   *
   * @param \Drupal\entity_events\Event\EntityEvent $event
   *   The event.
   */
  abstract public function onEntityPresave(EntityEvent $event);

}
