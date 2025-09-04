<?php

declare(strict_types=1);

namespace Drupal\localgov_microsites_group\EventSubscriber;

use Drupal\group_sites\GroupSitesAdminModeInterface;
use Drupal\search_api\Event\IndexingItemsEvent;
use Drupal\search_api\Event\ItemsIndexedEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Switch into Group Sites admin mode when indexing items.
 *
 * There is one index. All items go into it so while indexing per-site access
 * control (including on referenced items) shouldn't kick in.
 * The results are returned on searches with a group site filter.
 */
final class SearchApiSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a SearchApiSubscriber object.
   */
  public function __construct(
    private readonly GroupSitesAdminModeInterface $groupSitesAdminMode,
  ) {}

  /**
   * Preparing items to be indexed.
   */
  public function startIndexing(IndexingItemsEvent $event): void {
    $this->groupSitesAdminMode->setAdminModeOverride(TRUE);
  }

  /**
   * Items have been indexed.
   */
  public function stopIndexing(ItemsIndexedEvent $event): void {
    $this->groupSitesAdminMode->setAdminModeOverride(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiEvents::INDEXING_ITEMS => ['startIndexing'],
      SearchApiEvents::ITEMS_INDEXED => ['stopIndexing'],
    ];
  }

}
