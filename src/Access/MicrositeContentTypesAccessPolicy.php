<?php

namespace Drupal\localgov_microsites_group\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flexible_permissions\CalculatedPermissionsItem;
use Drupal\flexible_permissions\ChainPermissionCalculatorInterface;
use Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\group_sites\Access\GroupSitesSiteAccessPolicyInterface;
use Drupal\group_sites\GroupSitesAdminModeInterface;

class MicrositeContentTypesAccessPolicy implements GroupSitesSiteAccessPolicyInterface {

  use StringTranslationTrait;

  /**
   * Constructs a SingleSiteAccessPolicy object.
   *
   * @param \Drupal\group_sites\GroupSitesAdminModeInterface $adminMode
   *   The admin mode service.
   * @param \Drupal\flexible_permissions\ChainPermissionCalculatorInterface $chainCalculator
   *   The chain permission calculator.
   */
  public function __construct(
    protected GroupSitesAdminModeInterface $adminMode,
    protected ChainPermissionCalculatorInterface $chainCalculator
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->t('LocalGov Microsite');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->t('Single group permissions, with ability to disable specific module content types.');
  }

  /**
   * {@inheritdoc}
   */
  public function alterPermissions(GroupInterface $group, AccountInterface $account, string $scope, RefinableCalculatedPermissionsInterface $calculated_permissions): void {
    if ($scope === PermissionScopeInterface::INDIVIDUAL_ID) {
      // We only keep the item of the active group.
      $individual_item = $calculated_permissions->getItem($scope, $group->id());

      // The member check below varies based on membership of the group.
      $calculated_permissions->addCacheContexts(['user.is_group_member:' . $group->id()]);

      // Temporarily activate admin mode so we can calculate the actual insider
      // or outsider permissions and then flatten those into an individual item.
      $this->adminMode->setAdminModeOverride(TRUE);
      $bundle_permissions_scope = $group->getMember($account) ? PermissionScopeInterface::INSIDER_ID : PermissionScopeInterface::OUTSIDER_ID;
      $bundle_permissions = $this->chainCalculator->calculatePermissions($account, $bundle_permissions_scope);
      $this->adminMode->setAdminModeOverride(FALSE);

      if ($bundle_item = $bundle_permissions->getItem($bundle_permissions_scope, $group->bundle())) {
        if ($individual_item) {
          $permissions = array_merge($bundle_item->getPermissions(), $individual_item->getPermissions());
          $is_admin = $bundle_item->isAdmin() || $individual_item->isAdmin();
        }
        else {
          $permissions = $bundle_item->getPermissions();
          $is_admin = $bundle_item->isAdmin();
        }

        // All permissions granted by any modules.
        $all_module_permissions = $this->allModulePermissions();
        // Permissions the user has that are not granted by those modules.
        $non_module_permissions = array_diff($permissions, $all_module_permissions);
        // All permissions granted by modules, with disabled modules excluded.
        $enabled_module_permissions = $this->enabledModulePermissions($group);
        // All the permissions the user has that are granted by enabled modules.
        $include_module_permissions = array_intersect($permissions, $enabled_module_permissions);
        // Permissions granted by enabled modules, and permissions granted to
        // the user that are not covered by any of the modules.
        $permissions = array_merge($include_module_permissions, $non_module_permissions);

        $item = new CalculatedPermissionsItem(
          $scope,
          $group->id(),
          $permissions,
          $is_admin
        );
      }
      else {
        // If we're here we're being applied to the wrong group type.
        $item = $individual_item;
      }
    }

    // Remove all items, regardless of scope.
    $calculated_permissions->removeItemsByScope($scope);

    // Add back the individual item, along with merged synchronized item.
    if (!empty($item)) {
      $calculated_permissions->addItem($item);
    }
  }

  /**
   * Move below to the content type helper service?
   */

  private function allModulePermissions(): array {
    $default_permissions = \Drupal::moduleHandler()->invokeAll('localgov_microsites_roles_default');
    if (isset($default_permissions['group'])) {
      return array_merge(... array_values($default_permissions['group']));
    }
    return [];
  }

  private function enabledModulePermissions(GroupInterface $group): array {
    $disabled_modules = [];
    if ($group->hasField('lgms_modules_disabled')) {
      $disabled_modules = array_column($group->lgms_modules_disabled->getValue(), 'value');
    }
    $permissions = [];

    // Gather all permissions from modules that are not disabled.
    \Drupal::moduleHandler()->invokeAllWith('localgov_microsites_roles_default', function ($hook, $module) use ($disabled_modules, &$permissions) {
      if (!in_array($module, $disabled_modules, TRUE)) {
        $result = $hook();
        $permissions = array_merge_recursive($permissions, $result);
      }
    });
    if (isset($permissions['group'])) {
      return array_merge(... array_values($permissions['group']));
    }
    return [];
  }

 }
