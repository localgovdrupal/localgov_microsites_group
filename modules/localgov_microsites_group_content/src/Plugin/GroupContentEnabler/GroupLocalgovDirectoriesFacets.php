<?php

namespace Drupal\localgov_microsites_group_content\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Provides a content enabler for directory facets types.
 *
 * @GroupContentEnabler(
 *   id = "group_localgov_directories_facets",
 *   label = @Translation("Directory Facets"),
 *   description = @Translation("Adds directory facets to groups."),
 *   entity_type_id = "localgov_directories_facets",
 * )
 */
class GroupLocalgovDirectoriesFacets extends GroupContentEnablerBase {

}
