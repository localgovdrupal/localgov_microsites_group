<?php

namespace Drupal\localgov_microsites_group\Theme;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Plugin\Exception\MissingValueContextException;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\localgov_microsites_group\Entity\MicrositeGroupInterface;

/**
 * Negotiate the current theme based on theme_switcher_rules rules.
 */
class ThemeSwitcherNegotiator implements ThemeNegotiatorInterface {

  use ConditionAccessResolverTrait;

  /**
   * The route admin context to determine whether a route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The context manager service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The theme to be applied.
   *
   * @var string
   */
  private $theme;

  /**
   * ThemeSwitcherNegotiator constructor.
   *
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The route admin context service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The ContextHandler for applying contexts to conditions properly.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   */
  public function __construct(AdminContext $admin_context, EntityTypeManagerInterface $entity_type_manager, ContextHandlerInterface $context_handler, ContextRepositoryInterface $context_repository) {
    $this->adminContext = $admin_context;
    $this->entityTypeManager = $entity_type_manager;
    $this->contextHandler = $context_handler;
    $this->contextRepository = $context_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'entity.group.canonical' &&
      $route_match->getParameter('group') instanceof MicrositeGroupInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return \Drupal::config('system.theme')->get('admin');
  }

}
