<?php

/**
 * @file
 * Contains \Drupal\og\GroupRepository.
 */

namespace Drupal\og;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Field\FieldDefinitionInterface;

class GroupRepository {

  /**
   * @var array
   */
  protected $entityGroupCache = [];

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(EntityFieldManagerInterface $entity_field_manager, QueryFactory $query_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityFieldManager = $entity_field_manager;
    $this->queryFactory = $query_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  public function getAllGroupAudienceFields($entity_type_id, $bundle, $group_type_id = NULL, $group_bundle = NULL) {
    $filter = function (FieldDefinitionInterface $field_definition) use ($group_type_id, $group_bundle) {
      if (!Og::isGroupAudienceField($field_definition)) {
        // Not a group audience field.
        return FALSE;
      }
      $target_type = $field_definition->getFieldStorageDefinition()
        ->getSetting('target_type');

      if (isset($group_type_id) && $target_type != $group_type_id) {
        // Field doesn't reference this group type.
        return FALSE;
      }

      $handler_settings = $field_definition->getSetting('handler_settings');
      if (isset($group_bundle) && !empty($handler_settings['target_bundles']) && !in_array($group_bundle, $handler_settings['target_bundles'])) {
        return FALSE;
      }
      return TRUE;
    };
    return array_filter($this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle), $filter);
  }

  /**
  /**
   * Gets the groups an entity is associated with.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get groups for.
   * @param $states
   *   (optional) Array with the state to return. Defaults to active.
   * @param $field_name
   *   (optional) The field name associated with the group.
   *
   * @return array
   *  An array with the group's entity type as the key, and array - keyed by
   *  the OG membership ID and the group ID as the value. If nothing found,
   *  then an empty array.
   */
  public function getEntityGroups(EntityInterface $entity, array $states = [OG_STATE_ACTIVE], $field_name = NULL) {
    $entity_type_id = $entity->getEntityTypeId();
    $entity_id = $entity->id();

    // Get a string identifier of the states, so we can retrieve it from cache.
    if ($states) {
      sort($states);
      $state_identifier = implode(':', $states);
    }
    else {
      $state_identifier = FALSE;
    }

    $identifier = [
      $entity_type_id,
      $entity_id,
      $state_identifier,
      $field_name,
    ];

    $identifier = implode(':', $identifier);
    if (isset($this->entityGroupCache[$identifier])) {
      // Return cached values.
      return $this->entityGroupCache[$identifier];
    }

    $this->entityGroupCache[$identifier] = [];
    $query = $this->queryFactory->get('og_membership')
      ->condition('entity_type', $entity_type_id)
      ->condition('etid', $entity_id);

    if ($states) {
      $query->condition('state', $states, 'IN');
    }

    if ($field_name) {
      $query->condition('field_name', $field_name);
    }

    $results = $query->execute();

    /** @var \Drupal\og\Entity\OgMembership[] $memberships */
    $memberships = $this->entityTypeManager
      ->getStorage('og_membership')
      ->loadMultiple($results);

    /** @var \Drupal\og\Entity\OgMembership $membership */
    foreach ($memberships as $membership) {
      $this->entityGroupCache[$identifier][$membership->getGroupType()][$membership->id()] = $membership->getGroup();
    }
    return $this->entityGroupCache[$identifier];
  }

  public function resetCache() {
    $this->entityGroupCache = [];
  }
}
