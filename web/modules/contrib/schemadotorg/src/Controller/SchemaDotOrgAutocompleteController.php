<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Schema.org autocomplete routes.
 */
class SchemaDotOrgAutocompleteController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(Connection $database, SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager) {
    $this->database = $database;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('schemadotorg.schema_type_manager'),
    );
  }

  /**
   * Returns response for Schema.org (types or properties) autocomplete request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param string $table
   *   Types or properties table name.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocomplete(Request $request, string $table): JsonResponse {
    $input = $request->query->get('q');
    if (!$input) {
      return new JsonResponse([]);
    }

    $types = NULL;
    if ($this->schemaTypeManager->isType($table)) {
      // @todo Possibly cache the children to reduce the number of db queries.
      $children = array_keys($this->schemaTypeManager->getAllTypeChildren($table, ['label'], ['Enumeration']));
      sort($children);
      $labels = [];
      foreach ($children as $child) {
        if (stripos($child, $input) !== FALSE) {
          $labels[] = ['value' => $child, 'label' => $child];
        }
        if (count($labels) === 10) {
          break;
        }
      }
      return new JsonResponse($labels);
    }
    else {
      $query = $this->database->select('schemadotorg_' . $table, $table);
      $query->addField($table, 'label', 'value');
      $query->addField($table, 'label', 'label');
      $query->condition('label', '%' . $input . '%', 'LIKE');
      if ($types) {
        $query->condition('label', $types, 'IN');
      }
      $query->orderBy('label');
      $query->range(0, 10);
      $labels = $query->execute()->fetchAllAssoc('label');
      return new JsonResponse(array_values($labels));
    }
  }

}
