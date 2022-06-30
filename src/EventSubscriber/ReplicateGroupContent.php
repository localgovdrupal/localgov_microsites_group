<?php

namespace Drupal\localgov_microsites_group\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\replicate\Events\ReplicateEntityFieldEvent;
use Drupal\replicate\Events\ReplicatorEvents;
use Drupal\replicate\Replicator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Replicator Event subscriber.
 */
class ReplicateGroupContent implements EventSubscriberInterface {

  /**
   * The entity replicator service.
   *
   * @var \Drupal\replicate\Replicator
   */
  protected $replicator;

  /**
   * Constructs a ReplicatorGroupContent event subscriber.
   *
   * @param \Drupal\replicate\Replicator $replicator
   *   The entity replicator service.
   */
  public function __construct(Replicator $replicator) {
    $this->replicator = $replicator;
  }

  /**
   * Replicate the entities in any paragraphs when replicating any entity.
   *
   * @todo question should we _only_ do this for our group content entities,
   * useing a value on the entity to notifiy if it should happen or not?
   *
   * @param \Drupal\replicate\Events\ReplicateEntityFieldEvent $event
   *   The event we're working on.
   */
  public function onParagraphClone(ReplicateEntityFieldEvent $event) {
    $field_item_list = $event->getFieldItemList();

    foreach ($field_item_list as $delta => $field_item) {
      if ($field_item->entity instanceof EntityInterface) {
        $field_item_list->set($delta, $this->replicator->replicateEntity($field_item->entity));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ReplicatorEvents::replicateEntityField('paragraph')][] = 'onParagraphClone';
    return $events;
  }

}
