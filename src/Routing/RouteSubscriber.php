<?php

namespace Drupal\og\Routing;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Event\OgAdminRoutesEvent;
use Drupal\og\Event\OgAdminRoutesEventInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route provider service.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param EntityTypeManager $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteProvider $route_provider
   *   The route provider service.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(EntityTypeManager $entity_manager, RouteProvider $route_provider, ContainerAwareEventDispatcher $event_dispatcher) {
    $this->entityTypeManager = $entity_manager;
    $this->routeProvider = $route_provider;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    return;
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {

      if (!$og_admin_path = $entity_type->getLinkTemplate('og-admin-routes')) {
        // Entity type doesn't have the link template defined.
        continue;
      }

      $entity_type_id = $entity_type->id();
      $route_name = "entity.$entity_type_id.og_admin_routes";
      $route = new Route($og_admin_path);

      $route
        ->addDefaults([
          '_controller' => '\Drupal\og\Controller\OgAdminRoutesController::overview',
          'entity_type_id' => $entity_type_id,
          '_title' => 'Group management',
        ])
        ->addRequirements([
          // @todo: Allow to specify an OG permission instead.
          '_permission' => 'administer group',
        ])
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ])
        ->setOption('_admin_route', TRUE);

      $collection->add($route_name, $route);

      // Add the plugins routes.
      $this->createRoutesFromEventSubscribers($og_admin_path, $entity_type_id, $collection);

    }

  }

  /**
   * Add all the OG admin plugins to the route collection.
   *
   * @param string $og_admin_path
   *   The OG admin path.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection object.
   */
  protected function createRoutesFromEventSubscribers($og_admin_path, $entity_type_id, RouteCollection $collection) {

    $event = new OgAdminRoutesEvent();
    $this->eventDispatcher->dispatch(OgAdminRoutesEventInterface::EVENT_NAME, $event);

    foreach ($event->getRoutes() as $name => $info) {
      // Add the parent route.
      $parent_route_name = "entity.$entity_type_id.og_admin_routes.$name";
      $parent_path = $og_admin_path . '/' . $info['path'];

      $this->addRoute($collection, $entity_type_id, $parent_route_name, $parent_path, $info);

      // @todo: Add the sub routes.
    }
  }

  /**
   * Add route to collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The collection route.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $route_name
   *   The route name.
   * @param string $path
   *   The route path.
   * @param array $info
   *   Array with the router definitions. Required keys are:
   *   - controller
   *   - title.
   */
  protected function addRoute(RouteCollection $collection, $entity_type_id, $route_name, $path, array $info) {
    $route = new Route($path);
    $route
      ->addDefaults([
        '_controller' => $info['controller'],
        '_title' => $info['title'],
      ])
      ->addRequirements([
        // @todo: Allow to specify an Og permission instead.
        '_permission' => 'administer group',
      ])
      ->setOption('parameters', [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
      ])
      // @todo: We might need to define own admin route, like node module to
      // prevent access denied?
      ->setOption('_admin_route', TRUE);

    $collection->add($route_name, $route);
  }

}
