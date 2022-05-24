<?php

namespace Drupal\localgov_microsites_group;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\replicate\Replicator;

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
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The entity replicator service.
   *
   * @var \Drupal\replicate\Replicator
   */
  protected $replicator;

  /**
   * Constructs a GroupDefaultContent oindex:.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Drupal\replicate\Replicator $replicator
   *   The entity replicator service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config, Replicator $replicator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config;
    $this->replicator = $replicator;
  }

  /**
   * Generate default content for group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for which to generate the content.
   */
  public function generate(GroupInterface $group) {
    // @todo set config value to the content imported, just guessing 1
    // for now.
    $nid = $this->config->get('localgov_microsites_group.settings')->get('default_group_node') ?: 1;
    if ($default = Node::load($nid)) {
      $node = $this->replicator->replicateEntity($default);
    }
    else {
      // @todo check content type hasn't been uninstalled.
      // Or just remove this as if they want default content it will be set.
      $node = Node::create([
        'title' => 'Welcome to your new site',
        'status' => NodeInterface::PUBLISHED,
        'type' => 'localgov_page',
      ]);

      $node->setOwnerId($group->getOwnerId());
      $node->save();
    }

    $group->addContent($node, 'group_node:localgov_page');

    return [
      'node' => [
        'localgov_page' => [$node],
      ]
    ];
  }

}
