<?php

namespace Drupal\event_api;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class EventProvider {

    public function __construct(
        private readonly EntityTypeManagerInterface $entityTypeManager,
    ) {}

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