<?php

namespace Drupal\localgov_microsites_group;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * GroupDefaultContent service.
 */
class GroupDefaultContent implements GroupDefaultContentInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a GroupDefaultContent object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Generate default content for group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for which to generate the content.
   */
  public function generate(GroupInterface $group) {
    $node = Node::create([
      'title' => 'Welcome to your new site',
      'status' => NodeInterface::PUBLISHED,
      'type' => 'localgov_page',
    ]);

    $node->setOwnerId($group->getOwnerId());
    $node->save();

    $group->addContent($node, 'group_node:localgov_page');

    return [
      'node' => [
        'localgov_page' => [$node],
      ]
    ];
  }

}
