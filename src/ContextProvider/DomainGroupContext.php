<?php

namespace Drupal\localgov_microsites_group\ContextProvider;

use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\group_context_domain\Context\GroupFromDomainContext;

/**
 * Sets the current group as a context on domains.
 *
 * @deprecated
 */
class DomainGroupContext extends GroupFromDomainContext {

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId('group', $this->t('Group from domain (deprecated)'));
    $context->getContextDefinition()->setDescription($this->t('Provided originally by Domain Group. Returns the group from the domain record if there is one.'));
    return ['group' => $context];
  }

}
