<?php

namespace Drupal\localgov_microsites_events\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\domain_group\DomainGroupResolver;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\redirect\RedirectChecker;
use Drupal\redirect\RedirectRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;

/**
 * Event subscriber to hide events listing view for sites with no events.
 */
class EventsListingCheckEventSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\domain_group\DomainGroupResolver
   */
  protected $domainGroupResolver;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber object.
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
   * Check if the events listing page should be displayed,
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function checkEventsListingAccess(RequestEvent $event) {

    // Don't process events with HTTP exceptions.
    if ($event->getRequest()->get('exception') != NULL) {
      return;
    }

    // Check we're on an events listing page.
    if (
      !$event->getRequest()->getRequestUri() == '/events' &&
      !$event->getRequest()->getRequestUri() == '/events/search'
    ) {
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
