<?php

namespace Drupal\localgov_microsites_group\Plugin\Block;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\domain_group\DomainGroupHelper;
use Drupal\group\Entity\Group;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a local tastsk of group as a sitewide block.
 *
 * @Block(
 *   id = "microsite_tasks_block",
 *   admin_label = @Translation("Microsite tasks"),
 * )
 */
class MicrositeTasksBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The local task manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $localTaskManager;

  /**
   * The Domain Group Helper.
   *
   * @var \Drupal\domain_group\DomainGroupHelper
   */
  protected $domainGroupHelper;

  /**
   * Access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Creates a LocalTasksBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\LocalTaskManagerInterface $local_task_manager
   *   The local task manager.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver service.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LocalTaskManagerInterface $local_task_manager, ClassResolverInterface $class_resolver, AccessManagerInterface $access_manager, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->localTaskManager = $local_task_manager;
    $this->domainGroupHelper = $class_resolver->getInstanceFromDefinition(DomainGroupHelper::class);
    $this->accessManager = $access_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.menu.local_task'),
      $container->get('class_resolver'),
      $container->get('access_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheableDependency($this->localTaskManager);

    $menu = [];
    if ($group_id = $this->domainGroupHelper->getActiveDomainGroup()) {
      $menu['#theme'] = 'microsites_task_block';
      $route = new RouteMatch(
        'entity.group.canonical',
        new Route('group/{group}'),
        ['group' => Group::load($group_id)],
        ['group' => $group_id],
      );
      $tasks = $this->localTaskManager->getLocalTasksForRoute('entity.group.canonical');
      foreach ($tasks[0] as $task) {
        $route_name = $task->getRouteName();
        $route_parameters = $task->getRouteParameters($route);
        $link = [
          '#type' => 'link',
          '#title' => $task->getTitle(),
          '#url' => Url::fromRoute($route_name, $route_parameters),
          '#options' => $task->getOptions($route),
        ];
        $access = $this->accessManager
          ->checkNamedRoute($route_name, $route_parameters, $this->currentUser, TRUE);
        $menu['links'][] = [
          'link' => $link,
          '#access' => $access,
          '#weight' => $task->getWeight(),
        ];
        $cacheability
          ->addCacheableDependency($task)
          ->addCacheableDependency($access);
      }
    }

    $cacheability->applyTo($menu);
    return $menu;
  }

}
