<?php

declare(strict_types=1);

namespace Drupal\localgov_microsites_group;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\group\Entity\GroupInterface;


/**
 * Content entity types and bundles required by submodules.
 */
class ModuleContentTypes implements ModuleContentTypesInterface {

  /**
   * Constructs a GroupPermissionsHelper instance.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(protected ModuleHandlerInterface $moduleHandler) {
  }

  /**
   * {@inheritdoc}
   */
  public static function modules(): array {
    $modules = [];
    \Drupal::moduleHandler()->invokeAllWith('localgov_microsites_content_types', function ($hook, $module) use (&$modules) {
      $modules[$module] = \Drupal::service('extension.list.module')->getName($module);
    });
    return $modules;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(GroupInterface $group, string $entity_type_id, string $entity_bundle): bool {
    static $enabled = [];

    if (!isset($enabled[$group->id()])) {
      $this->moduleHandler->invokeAllWith('localgov_microsites_content_types', function ($hook, $module) use (&$enabled, $group) {
        // @todo correct way to check is in field result.
        if (in_array($module, $group->lgms_enabled_modules)) {
          $enabled = NestedArray::mergeDeep($enabled, $hook());
        }
      });
    }

    if (isset($enabled[$entity_type_id]) && (
      in_array($entity_bundle, $enabled[$entity_type_id]) || in_array('*', $enabled[$entity_type_id])
    )) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isModuleType(string $entity_type_id, string $entity_bundle): bool {
    static $types = NULL;
    if (is_null($types)) {
      $types = $this->moduleHandler->invokeAll('localgov_microsites_content_types');
    }
    if (isset($types[$entity_type_id]) && (
      in_array($entity_bundle, $types[$entity_type_id]) || in_array('*', $types[$entity_type_id])
    )) {
      return TRUE;
    }

    return FALSE;
  }

}
