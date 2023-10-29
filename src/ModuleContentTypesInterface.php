<?php

declare(strict_types=1);

namespace Drupal\localgov_microsites_group;

use Drupal\group\Entity\GroupInterface;


/**
 * ModuleContentTypes interface.
 */
interface ModuleContentTypesInterface {

  /**
   * Retrieve an array of all microsite modules using content types.
   *
   * @return array
   *  Module name keyed by module machine name.
   */
  public static function modules(): array;

  /**
   * Check if a content type is enabled for a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to check.
   * @param string $entity_type_id
   *   Entity type.
   * @param string $entity_bundle
   *   Bundle.
   *
   * @return bool
   *   True if content type is enabled.
   */
  public function isEnabled(GroupInterface $group, string $entity_type_id, string $entity_bundle): bool;

  /**
   * Check if a content type is a microsite group type.
   *
   * @param string $entity_type_id
   *   Entity type.
   * @param string $entity_bundle
   *   Bundle.
   *
   * @return bool
   *   True if content type is used by a microsite module.
   */
  public function isModuleType(string $entity_type_id, string $entity_bundle): bool;

}
