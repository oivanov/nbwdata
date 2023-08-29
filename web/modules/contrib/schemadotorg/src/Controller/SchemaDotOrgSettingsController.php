<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Controller;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Returns responses for Schema.org settings routes.
 */
class SchemaDotOrgSettingsController extends ControllerBase {

  /**
   * The local task manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $localTaskManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->localTaskManager = $container->get('plugin.manager.menu.local_task');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Returns Schema.org settings index page.
   */
  public function index(): array {
    $content = [];

    $definitions = $this->localTaskManager->getDefinitions();
    foreach ($definitions as $definition) {
      if (isset($definition['parent_id']) &&  $definition['parent_id'] === 'schemadotorg.settings') {
        $content[] = [
          'title' => $definition['title'],
          'description' => $this->getDescriptionFromHelp($definition['route_name']),
          'url' => Url::fromRoute($definition['route_name']),
          'weight' => $definition['weight'],
          'options' => [],
        ];
      }
    }

    uasort($content, [SortArray::class, 'sortByWeightElement']);

    return [
      'system_compact_link' => [
        '#type' => 'system_compact_link',
      ],
      'admin_block_content' => [
        '#theme' => 'admin_block_content',
        '#content' => $content,
      ],
    ];
  }

  /**
   * Get route description from help.
   *
   * @param string $route_name
   *   The route name.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The route's description from help.
   */
  protected function getDescriptionFromHelp(string $route_name): MarkupInterface {
    $route = new Route(Url::fromRoute($route_name)->toString());
    $route_match = new RouteMatch($route_name, $route);

    $build = [];
    $this->moduleHandler()->invokeAllWith('help', function (callable $hook, string $module) use (&$build, $route_match): void {
      if ($help = $hook($route_match->getRouteName(), $route_match)) {
        $build[] = is_array($help) ? $help : ['#markup' => $help];
      }
    });
    return $this->renderer->render($build);
  }

}
