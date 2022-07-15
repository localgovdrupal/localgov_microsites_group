<?php

namespace Drupal\localgov_microsites_group;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\replicate\Replicator;

/**
 * Generate default group content.
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
   * Configuration:
   * localgov_microsites_group.settings.default_group_node can contain the node
   * id to clone to place as default content into the group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for which to generate the content.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The new or cloned node added as default content.
   */
  public function generate(GroupInterface $group): ?NodeInterface {
    $node = NULL;
    $nid = $this->config->get('localgov_microsites_group.settings')->get('default_group_node');
    if ($nid && $default = $this->entityTypeManager->getStorage('node')->load($nid)) {
      $plugin_id = 'group_node:' . $default->bundle();
      if ($group->getGroupType()->hasContentPlugin($plugin_id)) {
        $node = $this->replicator->replicateEntity($default);
      }
    }
    elseif ($group->getGroupType()->hasContentPlugin('group_node:localgov_page')) {
      $plugin_id = 'group_node:localgov_page';
      $node = Node::create([
        'title' => 'Welcome to your new site',
        'status' => NodeInterface::PUBLISHED,
        'type' => 'localgov_page',
      ]);

      $node->setOwnerId($group->getOwnerId());
      $node->save();
    }

    if ($node instanceof NodeInterface) {
      $group->addContent($node, $plugin_id);
      return $node;
    }

    return NULL;
  }

}
