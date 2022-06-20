<?php

namespace Drupal\localgov_microsites_group_content\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Provides a content enabler for directory facets types.
 *
 * @GroupContentEnabler(
 *   id = "group_localgov_directories_facets_type",
 *   label = @Translation("Directory Facets Type"),
 *   description = @Translation("Adds directory facets types to groups."),
 *   entity_type_id = "localgov_directories_facets_type",
 * )
 */
class GroupLocalgovDirectoriesFacetsType extends GroupContentEnablerBase {

}
