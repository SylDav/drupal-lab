<?php

namespace Drupal\event_api;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides the upcoming events.
 */
class EventProvider {

  /**
   * Constructs an EventProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Gets the upcoming published events, sorted by date.
   *
   * @param int $limit
   *   The maximum number of events to return.
   *
   * @return array
   *   A list of events, each with an id, title, date and location.
   */
  public function getUpcoming(int $limit = 10): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'event')
      ->condition('status', 1)
      ->condition('field_date', date('Y-m-d'), '>=')
      ->sort('field_date', 'ASC')
      ->range(0, $limit)
      ->execute();

    $items = [];
    foreach ($storage->loadMultiple($ids) as $node) {
      $items[] = [
        'id' => (int) $node->id(),
        'title' => $node->label(),
        'date' => $node->get('field_date')->value,
        'location' => $node->get('field_lieu')->value,
      ];
    }
    return $items;
  }

}
