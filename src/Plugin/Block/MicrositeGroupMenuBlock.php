<?php

namespace Drupal\localgov_microsites_group\Plugin\Block;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\domain_group\DomainGroupHelper;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Drupal\group_content_menu\Plugin\Block\GroupMenuBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a menu block for LocalGov Drupal microsites.
 *
 * @todo Remove or rewrite this class.
 * The code here is largely copied from the GroupMenuBlock class. As such, this
 * is liable to break if the code in that class changes substantially.
 * @see https://github.com/localgovdrupal/localgov_microsites_group/pull/41
 *
 * @Block(
 *   id = "microsites_group_content_menu",
 *   admin_label = @Translation("Microsites Group Menu"),
 *   deriver = "Drupal\group_content_menu\Plugin\Derivative\GroupMenuBlock",
 *   context_definitions = {
 *     "group" = @ContextDefinition("entity:group", required = FALSE)
 *   }
 * )
 */
class MicrositeGroupMenuBlock extends GroupMenuBlock implements ContainerFactoryPluginInterface {

  /**
   * The Domain Group Helper.
   *
   * @var \Drupal\domain_group\DomainGroupHelper
   */
  protected $domainGroupHelper;

  /**
   * GroupMenuBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu tree service.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The active menu trail service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MenuLinkTreeInterface $menu_tree, MenuActiveTrailInterface $menu_active_trail, EntityTypeManagerInterface $entity_type_manager, ClassResolverInterface $class_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $menu_tree, $menu_active_trail, $entity_type_manager);
    $this->domainGroupHelper = $class_resolver->getInstanceFromDefinition(DomainGroupHelper::class);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.link_tree'),
      $container->get('menu.active_trail'),
      $container->get('entity_type.manager'),
      $container->get('class_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu_name = $this->getMenuName();
    // If unable to determine the menu, prevent the block from rendering.
    if (!$menu_name = $this->getMenuName()) {
      return [];
    }
    if ($this->configuration['expand_all_items']) {
      $parameters = new MenuTreeParameters();
      $active_trail = $this->menuActiveTrail->getActiveTrailIds($menu_name);
      $parameters->setActiveTrail($active_trail);
    }
    else {
      $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
    }

    // Adjust the menu tree parameters based on the block's configuration.
    $level = $this->configuration['level'];
    $depth = $this->configuration['depth'];
    $parameters->setMinDepth($level);
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
    }

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    $build = $this->menuTree->build($tree);
    $menu_instance = $this->getMenuInstance();
    if ($menu_instance instanceof GroupContentMenuInterface) {
      $build['#contextual_links']['group_menu'] = [
        'route_parameters' => [
          'group' => $this->domainGroupHelper->getActiveDomainGroup(),
          'group_content_menu' => $menu_instance->id(),
        ],
      ];

    }
    if ($menu_instance) {
      $build['#theme'] = 'menu__group_menu';
    }
    return $build;
  }

  /**
   * Gets the menu instance for the current group.
   *
   * @return \Drupal\group_content_menu\GroupContentMenuInterface|null
   *   The instance of the menu or null if no instance is found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMenuInstance() {

    // Get group.
    $group_id = $this->domainGroupHelper->getActiveDomainGroup();
    if (is_null($group_id)) {
      $group_id = \Drupal::request()->attributes->get('group');
    }
    if (is_null($group_id)) {
      return NULL;
    }

    // Don't load menu for group entities that are new/unsaved.
    $entity = $this->entityTypeManager->getStorage('group')->load($group_id);
    if (!$entity || $entity->isNew()) {
      return NULL;
    }

    // As we're extending the group_content_menu block we need to strip
    // 'microsites_' from the plugin ID.
    $plugin_id = $this->getPluginId();
    $prefix = 'microsites_';
    if (str_starts_with($plugin_id, $prefix)) {
      $plugin_id = substr($plugin_id, strlen($prefix));
    }

    /** @var \Drupal\group\Entity\Storage\GroupContentStorage $groupStorage */
    $groupStorage = $this->entityTypeManager->getStorage('group_content');
    $contentPluginId = $groupStorage->loadByContentPluginId($plugin_id);
    if (empty($contentPluginId)) {
      return NULL;
    }

    $instances = $groupStorage->loadByGroup($entity, $plugin_id);
    if ($instances) {
      return array_pop($instances)->getEntity();
    }
    return NULL;
  }

}
