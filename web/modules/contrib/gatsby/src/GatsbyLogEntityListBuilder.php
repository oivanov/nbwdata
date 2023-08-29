<?php

namespace Drupal\gatsby;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\gatsby\Entity\GatsbyLogEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Gatsby log entity entities.
 *
 * @ingroup gatsby
 */
class GatsbyLogEntityListBuilder extends EntityListBuilder {

  /**
   * Drupal\Core\Datetime\DateFormatter definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this
      ->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort('created', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query
        ->pager($this->limit);
    }
    return $query
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Entity Title');
    $header['entity_uuid'] = $this->t('Entity UUID');
    $header['entity'] = $this->t('Entity Type');
    $header['bundle'] = $this->t('Bundle');
    $header['langcode'] = $this->t('Language');
    $header['action'] = $this->t('Action');
    $header['preview'] = $this->t('Preview?');
    $header['published'] = $this->t('Status');
    $header['created'] = $this->t('Log Entry Created');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if (!($entity instanceof GatsbyLogEntity)) {
      return parent::buildRow($entity);
    }

    $row['id'] = $entity->id();
    $row['title'] = $entity->getTitle();
    $row['entity_uuid'] = $entity->get('entity_uuid')->value;
    $row['entity'] = $entity->get('entity')->value;
    $row['bundle'] = $entity->get('bundle')->value;
    $row['langcode'] = $entity->get('langcode')->value;
    $row['action'] = $entity->get('action')->value;
    $row['preview'] = $entity->get('preview')->value ? 'Yes' : 'No';

    // Check published value.
    if (isset($entity->get('published')->value)) {
      $row['published'] = $entity->get('published')->value == 1 ? 'published' : 'unpublished';
    }
    else {
      $row['published'] = 'N/A';
    }

    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime());
    return $row;
  }

}
