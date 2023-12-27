<?php

namespace Drupal\localgov_microsites_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\group\Context\GroupRouteContextTrait;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_context_domain\Context\GroupFromDomainContextTrait;

/**
 * Find Group for Domain.
 *
 * @deprecated
 */
class DomainGroupResolver implements DomainGroupResolverInterface {

  use GroupRouteContextTrait;
  use DomainFromGroupTrait;
  use GroupFromDomainContextTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The domain negotiator service.
   *
   * @var \Drupal\Domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * DomainGroupHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type manager.
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   Domain negotatior service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   Current route match service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DomainNegotiatorInterface $domain_negotiator, RouteMatchInterface $current_route_match, EntityRepositoryInterface $entity_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->domainNegotiator = $domain_negotiator;
    $this->currentRouteMatch = $current_route_match;
    $this->entityRepository = $entity_repository;
  }

  /**
   * Get Group ID for active domain if there is one.
   *
   * @return int|null
   *   Group ID, or NULL if there is no active domain group.
   */
  public function getActiveDomainGroupId(): ?int {
    if ($group = $this->getGroupFromDomain()) {
      return $group->id();
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityGroupDomains(EntityInterface $entity): array {
    $domains = [];
    $groups = [];

    if ($entity instanceof GroupRelationshipInterface) {
      $group_relationship_array = [$entity];
    }
    elseif ($entity instanceof GroupInterface) {
      $groups = [$entity];
    }
    elseif (!$entity->isNew()) {
      $group_relationship_array = $this->entityTypeManager->getStorage('group_relationship')->loadByEntity($entity);
    }

    if (!empty($group_relationship_array)) {
      foreach ($group_relationship_array as $group_relationship) {
        $groups[] = $group_relationship->getGroup();
      }
    }

    if (!empty($groups)) {
      foreach ($groups as $group) {
        if ($group) {
          $domain = $this->getDomainFromGroup($group);
          if ($domain) {
            $domains[$domain->id()] = $domain;
          }
        }
      }
    }

    return $domains;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentRouteGroupDomain(): ?DomainInterface {
    $domain = NULL;
    $group = $this->getGroupFromRoute();
    if ($group) {
      $domain = $this->getDomainFromGroup($group);
    }
    return $domain;
  }

}
