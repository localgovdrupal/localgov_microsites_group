<?php

namespace Drupal\localgov_microsites_group_content\Routing;

use Symfony\Component\Routing\Route;

/**
 * Provides routes for Localgov Microsites group content.
 */
class GroupContentRouteProvider {

  /**
   * Provides routes for Localgov Microsites group content.
   */
  public function getRoutes() {
    $routes = [];

    $routes['entity.group_content.group_localgov_directories_facet_type.add'] = new Route('group/{group}/directory-facet-type/type/add');
    $routes['entity.group_content.group_localgov_directories_facet_type.add']
      ->setDefaults([
        '_title' => 'Add new directory facet type',
        '_controller' => '\Drupal\localgov_microsites_group_content\Controller\GroupDirectoryFacetTypeController::add',
        'create_mode' => TRUE,
      ]);

//    $routes['entity.group_content.group_localgov_directories_facets.add'] = new Route('group/{group}/directory-facets/facet/{localgov_directories_facet_type}/add');
//    $routes['entity.group_content.group_localgov_directories_facets.add']
//      ->setDefaults([
//        '_title' => 'Add new directory facet',
//        '_controller' => '\Drupal\localgov_microsites_group_content\Controller\GroupDirectoryFacetsController::add',
//        'create_mode' => TRUE,
//      ]);

    return $routes;
  }
}
