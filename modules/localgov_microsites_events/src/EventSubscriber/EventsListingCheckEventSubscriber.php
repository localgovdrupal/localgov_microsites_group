<?php

namespace Drupal\localgov_microsites_events\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\localgov_microsites_group\GroupPermissionsHelperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to hide events listing view if events aren't enabled.
 */
class EventsListingCheckEventSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group permissions helper.
   *
   * @var \Drupal\localgov_microsites_group\GroupPermissionsHelperInterface
   */
  protected $groupPermissionsHelper;

  /**
   * Returns an EventsListingCheckEventSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\localgov_microsites_group\GroupPermissionsHelperInterface $permissions_helper
   *   The group permissions helper.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupPermissionsHelperInterface $permissions_helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupPermissionsHelper = $permissions_helper;
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
    // @TODO
    //
    // Get from context (see GroupSitesAccessPolicy):
    // $context_id = $this->configFactory->get('group_sites.settings')->get('context_provider');
    // $contexts = $this->contextRepository->getRuntimeContexts([$context_id]);
    $group_id = NULL;
    \Drupal::messenger('Implementation missing EventsListingCheckEventSubscriber');
    if (!$group_id) {
      return;
    }
    $group = $this->entityTypeManager->getStorage('group')->load($group_id);
    if (!$group) {
      return;
    }

    // If events aren't enabled return a 404.
    if ($this->groupPermissionsHelper->moduleStatus('localgov_microsites_events', $group) != $this->groupPermissionsHelper::ENABLED) {
      throw new NotFoundHttpException();
    }
  }

}
