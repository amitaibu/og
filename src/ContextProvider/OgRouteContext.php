<?php

namespace Drupal\og\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\og\GroupManager;
use Drupal\og\OgGroupAudienceHelper;

class OgRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\og\GroupManager
   */
  protected $groupManager;

  /**
   * Constructs a new NodeRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(RouteMatchInterface $route_match, GroupManager $group_manager) {
    $this->routeMatch = $route_match;
    $this->groupManager = $group_manager;
  }

  /**
   * @inheritDoc
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = new ContextDefinition('entity:node', NULL, FALSE);
    $value = NULL;
    if (($route_object = $this->routeMatch->getRouteObject()) && ($route_contexts = $route_object->getOption('parameters')) && isset($route_contexts['node'])) {
      if (($node = $this->routeMatch->getParameter('node')) && $node instanceof NodeInterface) {
        if ($this->groupManager->isGroup('node', $node->bundle())) {
          $value = $node;
        }
        foreach (array_keys(OgGroupAudienceHelper::getAllGroupAudienceFields('node', $node->bundle(), 'node')) as $field_name) {
          if ($node->hasField($field_name) && !$node->$field_name->isEmpty()) {
            $value = $node->$field_name->entity;
            break;
          }
        }
      }
    }
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);
    $result['node'] = $context;

    return $result;
  }

  /**
   * @inheritDoc
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity:node', $this->t('(Containing) group from URL')));
    return ['node' => $context];
  }

}
