<?php

namespace Drupal\event_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\event_api\EventProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns the upcoming events as JSON.
 */
class EventController extends ControllerBase {

  /**
   * Constructs an EventController object.
   *
   * @param \Drupal\event_api\EventProvider $eventProvider
   *   The event provider service.
   */
  public function __construct(
    private readonly EventProvider $eventProvider,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('event_api.event_provider'));
  }

  /**
   * Lists the upcoming events.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function list(Request $request): JsonResponse {
    $limit = min(max($request->query->getInt('limit', 10), 1), 50);
    $response = new JsonResponse(['data' => $this->eventProvider->getUpcoming($limit)]);
    $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);

    return $response;
  }

}
