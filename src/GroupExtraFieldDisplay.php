<?php

namespace Drupal\localgov_microsites_group;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupInterface;
use Drupal\views\Views;

/**
 * Add extra fields to display.
 */
class GroupExtraFieldDisplay {

  use StringTranslationTrait;

  /**
   * Gets the "extra fields" for a bundle.
   *
   * @see hook_entity_extra_field_info()
   */
  public function entityExtraFieldInfo() {
    $fields = [];

    // @toto loop group types for all microsite types.
    $fields['group']['microsite']['display']['microsite_content'] = [
      'label' => $this->t('Microsite latest content'),
      'description' => $this->t("Most recently updated content with link to content tab."),
      'weight' => -20,
      'visible' => TRUE,
    ];

    return $fields;
  }

  /**
   * Adds view with arguments to view render array if required.
   *
   * @see localgov_directories_node_view()
   */
  public function groupView(array &$build, GroupInterface $group, EntityViewDisplayInterface $display, $view_mode) {
    if ($display->getComponent('microsite_content')) {
      $build['microsite_content'] = $this->getViewEmbed($group);
    }
  }

  /**
   * Retrieves view, and sets render array.
   */
  protected function getViewEmbed(GroupInterface $group) {
    $view = Views::getView('group_nodes');
    if (!$view || !$view->access('microsite_overview')) {
      return;
    }
    $render = [
      '#type' => 'view',
      '#name' => 'group_nodes',
      '#display_id' => 'microsite_overview',
      '#arguments' => [$group->id()],
    ];

    return $render;
  }

}
