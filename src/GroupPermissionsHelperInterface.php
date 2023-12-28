<?php

declare(strict_types=1);

namespace Drupal\localgov_microsites_group;

use Drupal\group\Entity\GroupInterface;

/**
 * GroupPermissionsHelper interface.
 */
interface GroupPermissionsHelperInterface {

  /**
   * Module permissions status: Has no group permissions.
   */
  const NOT_APPLICABLE = 'not_applicable';

  /**
   * Module permissions status: has modifications to permissions.
   */
  const UNKNOWN = 'unknown';

  /**
   * Module permissions status: enabled, all permissions in place.
   */
  const ENABLED = 'enabled';

  /**
   * Module permissions status: disabled, all permissions removed.
   */
  const DISABLED = 'disabled';

  /**
   * Get all modules status for a microsite group.
   *
   * List of all modules that can have their status changed.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to check.
   *
   * @return array
   *   Array keyed by module name, with class module permissions status as
   *   returned by moduleStatus().
   */
  public function modulesList(GroupInterface $group): array;

  /**
   * Get module status for a microsite group.
   *
   * Checks if all permissions are present, not present, or if permissions no
   * longer match either.
   *
   * @param string $module
   *   The module machine name.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to check.
   *
   * @return string
   *   Class module permissions status constant. One of self::ENABLED
   *   self::DISABLED self::UNKNOWN self::NOT_APPLICABLE.
   */
  public function moduleStatus($module, GroupInterface $group): string;

  /**
   * Enable permissions for a module.
   *
   * @param string $module
   *   The module machine name.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to enable permissions for.
   */
  public function moduleEnable($module, GroupInterface $group);

  /**
   * Disable permissions for a module.
   *
   * @param string $module
   *   The module machine name.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to disable permissions for.
   */
  public function moduleDisable($module, GroupInterface $group);

}
