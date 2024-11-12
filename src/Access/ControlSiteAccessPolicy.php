<?php

namespace Drupal\localgov_microsites_group\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flexible_permissions\CalculatedPermissionsItem;
use Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\group_sites\Access\GroupSitesNoSiteAccessPolicyInterface;

/**
 * Access policy for control site.
 */
class ControlSiteAccessPolicy implements GroupSitesNoSiteAccessPolicyInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->t('Localgov Microsites Control Site');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->t('Prevent most content: nodes, media, etc being created on the control site.');
  }

  /**
   * {@inheritdoc}
   */
  public function alterPermissions(AccountInterface $account, string $scope, RefinableCalculatedPermissionsInterface $calculated_permissions) {
    // Remove all permissions other than those we want on the control site from
    // user no matter if they come from insider, outsider or individual.
    $items = $calculated_permissions->getItemsByScope($scope);
    foreach ($items as $item) {
      $permissions = $item->getPermissions();
      // Permissions to maintain on the control site.
      // @todo add control site specific permissions.
      $keep = [
        'administer group domain site settings',
        'administer members',
        'edit group',
        'invite users to group',
        'manage microsite enabled module permissions',
        'set localgov microsite theme override',
        'view any unpublished group',
        'view group',
        'view group invitations',
        'view latest group version',
        'view own unpublished group',
      ];
      $permissions = array_intersect($permissions, $keep);

      $control_site_item = new CalculatedPermissionsItem(
        $scope,
        $item->getIdentifier(),
        $permissions,
        $item->isAdmin()
      );
      $calculated_permissions->addItem($control_site_item, TRUE);
    }
  }

}
