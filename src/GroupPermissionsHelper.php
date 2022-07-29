<?php

declare(strict_types=1);

namespace Drupal\localgov_microsites_group;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\group\Access\GroupPermissionHandlerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_permissions\Entity\GroupPermission;
use Drupal\group_permissions\Entity\GroupPermissionInterface;
use Drupal\group_permissions\GroupPermissionsManagerInterface;

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
   * The group module permission handler.
   *
   * @var \Drupal\group\Access\GroupPermissionHandlerInterface
   */
  protected $groupPermissionHandler;

  /**
   * The group permissions module per group permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManagerInterface
   */
  protected $groupPermissionsManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a GroupPermissionsHelper oindex:.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Access\GroupPermissionHandlerInterface $permission_handler
   *   The group module permissions handler.
   * @param \Drupal\group_permissions\GroupPermissionsManagerInterface $group_permissions_manager
   *   The group permissions module manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupPermissionHandlerInterface $permission_handler, GroupPermissionsManagerInterface $group_permissions_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupPermissionHandler = $permission_handler;
    $this->groupPermissionsManager = $group_permissions_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function modulesList(GroupInterface $group): array {
    $modules = [];
    $this->moduleHandler->invokeAllWith('localgov_microsites_roles_default', function ($hook, $module) use (&$modules, $group) {
      $modules[$module] = $this->moduleStatus($module, $group);
    });
    return $modules;
  }

  /**
   * {@inheritdoc}
   */
  public function moduleStatus($module, GroupInterface $group): string {
    $group_permissions_entity = $this->getGroupPermissions($group);
    $group_permissions = $group_permissions_entity->getPermissions();
    $module_permissions = RolesHelper::getModuleRoles($module);
    if (is_null($module_permissions)) {
      throw new \LogicException('Module does not implement hook_localgov_microsites_roles_default');
    }
    if (empty($module_permissions['group'])) {
      return GroupPermissionsHelperInterface::NOT_APPLICABLE;
    }
    $module_group_permissions = [];
    foreach ($module_permissions['group'] as $role => $permissions) {
      $module_group_permissions[$group->bundle() . '-' . $role] = $permissions;
    }
    $permissions_comparison = $this->comparePermissionsArray($group_permissions, $module_group_permissions);
    if ($permissions_comparison == 'empty') {
      return GroupPermissionsHelperInterface::DISABLED;
    }
    elseif ($permissions_comparison == 'changed') {
      return GroupPermissionsHelperInterface::UNKNOWN;
    }
    return GroupPermissionsHelperInterface::ENABLED;
  }

  /**
   * Compare permissions for each role.
   *
   * Checks all the module permissions are present - same;
   * or if none of them are - empty; or if some are, but not all - changed.
   */
  protected function comparePermissionsArray($group, $module) {
    $difference = '';
    foreach ($module as $role => $permissions) {
      $common = array_intersect($permissions, $group[$role] ?? []);
      if (count($common) == count($permissions)) {
        if ($difference == 'same' || $difference == '') {
          $difference = 'same';
        }
        else {
          $difference = 'changed';
          break;
        }
      }
      elseif (count($common) == 0) {
        if ($difference == 'empty' || $difference == '') {
          $difference = 'empty';
        }
        else {
          $difference = 'changed';
          break;
        }
      }
      else {
        $difference = 'changed';
        break;
      }
    }

    return $difference;
  }

  /**
   * {@inheritdoc}
   */
  public function moduleEnable($module, GroupInterface $group) {
    $group_permissions_entity = $this->getGroupPermissions($group);
    $group_permissions = $group_permissions_entity->getPermissions();
    $module_permissions = RolesHelper::getModuleRoles($module);
    if (is_null($module_permissions)) {
      throw new \LogicException('Module does not implement hook_localgov_microsites_roles_default');
    }
    $module_group_permissions = $module_permissions['group'];
    if (empty($module_group_permissions)) {
      throw new \LogicException('Module does not implement group permissions');
    }
    foreach ($module_group_permissions as $role => $permissions) {
      $group_permissions[$group->bundle() . '-' . $role] = array_merge($group_permissions[$group->bundle() . '-' . $role], $permissions);
    }
    $group_permissions_entity->setPermissions($group_permissions);
    $group_permissions_entity->validate();
    $group_permissions_entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function moduleDisable($module, GroupInterface $group) {
    $group_permissions_entity = $this->getGroupPermissions($group);
    $group_permissions = $group_permissions_entity->getPermissions();
    $module_permissions = RolesHelper::getModuleRoles($module);
    if (is_null($module_permissions)) {
      throw new \LogicException('Module does not implement hook_localgov_microsites_roles_default');
    }
    $module_group_permissions = $module_permissions['group'];
    if (empty($module_group_permissions)) {
      throw new \LogicException('Module does not implement group permissions');
    }
    foreach ($module_group_permissions as $role => $permissions) {
      $group_permissions[$group->bundle() . '-' . $role] = array_diff($group_permissions[$group->bundle() . '-' . $role], $permissions);
    }
    $group_permissions_entity->setPermissions($group_permissions);
    $group_permissions_entity->validate();
    $group_permissions_entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): GroupPermissionInterface {
    // GroupPermissionsManager::getGroupRoles also caches like this. Doing so
    // here too makes it slightly more internally consistent for a call for
    // this class. But possisibly not for changes made outside it.
    // See also note testAlteredGroupPermissions::testAlteredGroupPermissions().
    if (empty($this->groupPermissions[$group->id()])) {
      $group_permission = $this->groupPermissionsManager->getGroupPermission($group);
      if (is_null($group_permission)) {
        $group_permission = GroupPermission::create([
          'gid' => $group->id(),
        ]);
      }
      $permissions = $group_permission->getPermissions();
      if (empty($permissions)) {
        $group_roles = $this->groupPermissionsManager->getGroupRoles($group);
        foreach ($group_roles as $role_name => $role) {
          $permissions[$role_name] = $role->getPermissions();
        }
        $group_permission->setPermissions($permissions);
      }

      $this->groupPermissions[$group->id()] = $group_permission;
    }

    return $this->groupPermissions[$group->id()];
  }

}
