<?php

declare(strict_types=1);

namespace Drupal\localgov_microsites_group;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Associate group permissions with enabled disabled patterns.
 */
class GroupPermissionsHelper implements GroupPermissionsHelperInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a GroupPermissionsHelper instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function modulesList(GroupInterface $group): array {
  }

  /**
   * {@inheritdoc}
   */
  public function moduleStatus($check_module, GroupInterface $group): string {
  }

  /**
   * {@inheritdoc}
   */
  public function moduleEnable($enable_module, GroupInterface $group) {
  }

  /**
   * {@inheritdoc}
   */
  public function moduleDisable($disable_module, GroupInterface $group) {
  }

}
