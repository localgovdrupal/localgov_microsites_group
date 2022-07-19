<?php

namespace Drupal\localgov_microsites_group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * LocalGov Microsites Group event subscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.group_content.canonical')) {
      $defaults = $route->getDefaults();
      unset($defaults['_entity_view']);
      $defaults['_controller'] = 'localgov_microsites_group_redirect_group_content';
      $route->setDefaults($defaults);
    }
  }

}
