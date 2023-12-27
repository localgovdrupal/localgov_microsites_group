<?php

namespace Drupal\localgov_microsites_group;

use Drupal\domain\DomainInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_context_domain\Context\GroupFromDomainContextTrait;


/**
 * Trait to get the domain from group.
 *
 * If the class is capable of injecting services from the container it should
 * inject the 'entity_type.manager' into the entityTypeManager property.
 */
trait DomainFromGroupTrait {

  // Reusing the getEntityTypeManager from GroupFromDomainContextTrait to
  // avoid having to deal with method conflicts as commonly used together.
  use GroupFromDomainContextTrait;

  /**
   * Retrieves the domain config entity for a group.
   */
  public function getDomainFromGroup(GroupInterface $group): ?DomainInterface {
    $result = $this->getEntityTypeManager()->getStorage('domain')->loadByProperties(['third_party_settings.group_context_domain.group_uuid' => $group->uuid()]);
    return reset($result) ?: NULL;
  }

}
