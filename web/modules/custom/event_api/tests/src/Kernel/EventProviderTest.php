<?php

namespace Drupal\Tests\event_api\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the EventProvider service.
 */
#[Group('event_api')]
#[RunTestsInSeparateProcesses]
class EventProviderTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'datetime',
    'event_api',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);

    // Create a content type for testing.
    NodeType::create([
      'type' => 'event',
      'name' => 'Événement',
    ])->save();

    // Create a date field for the event content type.
    FieldStorageConfig::create([
      'field_name' => 'field_date',
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => [
        'datetime_type' => 'date',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_date',
      'entity_type' => 'node',
      'bundle' => 'event',
      'label' => 'Date',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_lieu',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_lieu',
      'entity_type' => 'node',
      'bundle' => 'event',
      'label' => 'Lieu',
    ])->save();

    $this->setUpCurrentUser([], ['access content']);
  }

  /**
   * Creates an event node.
   *
   * @param string $title
   *   The event title.
   * @param string $date
   *   The event date (Y-m-d).
   * @param string $lieu
   *   The event location.
   * @param bool $published
   *   Whether the event is published.
   */
  private function createEvent(string $title, string $date, string $lieu, bool $published = TRUE): void {
    Node::create([
      'type' => 'event',
      'title' => $title,
      'field_date' => $date,
      'field_lieu' => $lieu,
      'status' => $published ? 1 : 0,
    ])->save();
  }

  /**
   * Tests that only upcoming events are returned.
   */
  public function testReturnsOnlyUpcomingEvents(): void {
    $this->createEvent('Passé', '2000-01-01', 'Paris');
    $this->createEvent('Futur 1', '2099-01-01', 'Lyon');
    $this->createEvent('Futur 2', '2099-06-01', 'Marseille');

    $items = $this->container->get('event_api.event_provider')->getUpcoming();

    $this->assertCount(2, $items);
    $this->assertSame('Futur 1', $items[0]['title']);
    $this->assertSame('2099-01-01', $items[0]['date']);
    $this->assertSame('Lyon', $items[0]['location']);
    $this->assertSame('Futur 2', $items[1]['title']);
    $this->assertSame('2099-06-01', $items[1]['date']);
    $this->assertSame('Marseille', $items[1]['location']);
  }

  /**
   * Tests that unpublished events are excluded.
   */
  public function testExcludesUnpublishedEvents(): void {
    $this->createEvent('Publié', '2099-01-01', 'Lyon');
    $this->createEvent('Non publié', '2099-06-01', 'Marseille', FALSE);

    $items = $this->container->get('event_api.event_provider')->getUpcoming();

    $this->assertCount(1, $items);
    $this->assertSame('Publié', $items[0]['title']);
  }

  /**
   * Tests that the limit is applied and events are sorted by date.
   */
  public function testAppliesLimitAndSortsByDate(): void {
    $this->createEvent('Futur 1', '2099-01-01', 'Lyon');
    $this->createEvent('Futur 2', '2099-06-01', 'Marseille');
    $this->createEvent('Futur 3', '2099-03-01', 'Nice');

    $items = $this->container->get('event_api.event_provider')->getUpcoming(2);

    $this->assertSame(['Futur 1', 'Futur 3'], array_column($items, 'title'));
  }

}
