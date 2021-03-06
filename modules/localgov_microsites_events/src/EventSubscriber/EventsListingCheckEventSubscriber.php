<?php

namespace Drupal\localgov_microsites_events\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\domain_group\DomainGroupResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to hide events listing view for sites with no events.
 */
class EventsListingCheckEventSubscriber implements EventSubscriberInterface {

  /**
   * The domain  group resolver.
   *
   * @var \Drupal\domain_group\DomainGroupResolver
   */
  protected $domainGroupResolver;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Returns an EventsListingCheckEventSubscriber instance.
   *
   * @param \Drupal\domain_group\DomainGroupResolver $domain_group_resolver
   *   The domain group resolver.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(DomainGroupResolver $domain_group_resolver, EntityTypeManagerInterface $entity_type_manager) {
    $this->domainGroupResolver = $domain_group_resolver;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[KernelEvents::REQUEST][] = ['checkEventsListingAccess'];
    return $events;
  }

  /**
   * Check if the events listing page should be displayed.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function checkEventsListingAccess(RequestEvent $event) {

    // Check we're on an events listing page.
    if (
      $event->getRequest()->getPathInfo() != '/events' &&
      $event->getRequest()->getPathInfo() != '/events/search'
    ) {
      return;
    }

    // Don't process events with HTTP exceptions.
    if (!is_null($event->getRequest()->get('exception'))) {
      return;
    }

    // Check we're in a group.
    $group_id = $this->domainGroupResolver->getActiveDomainGroupId();
    if (!$group_id) {
      return;
    }
    $group = $this->entityTypeManager->getStorage('group')->load($group_id);
    if (!$group) {
      return;
    }

    // If no events throw a 404.
    $events = $group->getContent('group_node:localgov_event');
    if (empty($events)) {
      throw new NotFoundHttpException();
    }
  }

}
