<?php

namespace Drupal\localgov_microsites_group\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\domain_group\DomainGroupHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Process inbound path to ensure the group is added to it for group content.
 */
class MicrositesGroupPathProcessorIn implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {

    $group_id = \Drupal::service('class_resolver')
      ->getInstanceFromDefinition(DomainGroupHelper::class)
      ->getActiveDomainGroup();
    if (
      !is_null($group_id) &&
      !str_starts_with($path, '/group')
    ) {
      $path = '/group/' . $group_id . $path;
    }

    return $path;
  }

}
