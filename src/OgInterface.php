<?php

namespace Drupal\og;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface for OG.
 */
interface OgInterface {

  /**
   * Create an organic groups field in a bundle.
   *
   * @param string $plugin_id
   *   The OG field plugin ID, which is also the default field name.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle name.
   * @param array $settings
   *   (Optional) allow overriding the default definitions of the field storage
   *   config and field config.
   *   Allowed values:
   *   - field_storage_config: Array with values to override the field storage
   *     config definitions. Values should comply with
   *     FieldStorageConfig::create().
   *   - field_config: Array with values to override the field config
   *     definitions. Values should comply with FieldConfig::create()
   *   - form_display: Array with values to override the form display
   *     definitions.
   *   - view_display: Array with values to override the view display
   *     definitions.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface
   *   The created or existing field config.
   */
  public function createField($plugin_id, $entity_type, $bundle, array $settings = []);

  /**
   * Returns all group IDs associated with the given user.
   *
   * This is similar to \Drupal\og\Og::getGroupIds() but for users. The reason
   * there is a separate method for user entities is because the storage is
   * handled differently. For group content the relation to the group is stored
   * on a field attached to the content entity, while user memberships are
   * tracked in OgMembership entities.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get groups for.
   * @param array $states
   *   (optional) Array with the state to return. Defaults to active.
   *
   * @return array
   *   An associative array, keyed by group entity type, each item an array of
   *   group entity IDs.
   *
   * @see \Drupal\og\Og::getGroupIds()
   */
  public function getUserGroupIds(AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns all groups associated with the given user.
   *
   * This is similar to \Drupal\og\Og::getGroups() but for users. The reason
   * there is a separate method for user entities is because the storage is
   * handled differently. For group content the relation to the group is stored
   * on a field attached to the content entity, while user memberships are
   * tracked in OgMembership entities.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get groups for.
   * @param array $states
   *   (optional) Array with the states to return. Defaults to active.
   *
   * @return \Drupal\Core\Entity\EntityInterface[][]
   *   An associative array, keyed by group entity type, each item an array of
   *   group entities.
   *
   * @see \Drupal\og\Og::getGroups()
   * @see \Drupal\og\Og::getMemberships()
   */
  public function getUserGroups(AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns the group memberships a user is associated with.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get groups for.
   * @param array $states
   *   (optional) Array with the state to return. Defaults to active.
   *
   * @return \Drupal\og\Entity\OgMembership[]
   *   An array of OgMembership entities, keyed by ID.
   */
  public function getMemberships(AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns the group membership for a given user and group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group to get the membership for.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get the membership for.
   * @param array $states
   *   (optional) Array with the state to return. Defaults to active.
   *
   * @return \Drupal\og\Entity\OgMembership|null
   *   The OgMembership entity, or NULL if the user is not a member of the
   *   group.
   */
  public function getMembership(EntityInterface $group, AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Creates an OG membership.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user object.
   * @param string $membership_type
   *   (optional) The membership type. Defaults to OG_MEMBERSHIP_TYPE_DEFAULT.
   *
   * @return \Drupal\og\Entity\OgMembership
   *   The unsaved membership object.
   */
  public function createMembership(EntityInterface $group, AccountInterface $user, $membership_type = OgMembershipInterface::TYPE_DEFAULT);

  /**
   * Returns all group IDs associated with the given group content entity.
   *
   * Do not use this to retrieve group IDs associated with a user entity. Use
   * Og::getUserGroups() instead.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity for which to return the associated groups.
   * @param string $group_type_id
   *   Filter results to only include group IDs of this entity type.
   * @param string $group_bundle
   *   Filter list to only include group IDs with this bundle.
   *
   * @return array
   *   An associative array, keyed by group entity type, each item an array of
   *   group entity IDs.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a user entity is passed in.
   *
   * @see \Drupal\og\Og::getUserGroups()
   */
  public function getGroupIds(EntityInterface $entity, $group_type_id = NULL, $group_bundle = NULL);

  /**
   * Returns all groups that are associated with the given group content entity.
   *
   * Do not use this to retrieve group memberships for a user entity. Use
   * Og::getUserGroups() instead.
   *
   * The reason there are separate method for group content and user entities is
   * because the storage is handled differently. For group content the relation
   * to the group is stored on a field attached to the content entity, while
   * user memberships are tracked in OgMembership entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity for which to return the groups.
   * @param string $group_type_id
   *   Filter results to only include groups of this entity type.
   * @param string $group_bundle
   *   Filter results to only include groups of this bundle.
   *
   * @return \Drupal\Core\Entity\EntityInterface[][]
   *   An associative array, keyed by group entity type, each item an array of
   *   group entities.
   *
   * @see \Drupal\og\Og::getUserGroups()
   */
  public function getGroups(EntityInterface $entity, $group_type_id = NULL, $group_bundle = NULL);

  /**
   * Returns the number of groups associated with a given group content entity.
   *
   * Do not use this to retrieve the group membership count for a user entity.
   * Use count(Og::GetEntityGroups()) instead.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity for which to count the associated groups.
   * @param string $group_type_id
   *   Only count groups of this entity type.
   * @param string $group_bundle
   *   Only count groups of this bundle.
   *
   * @return int
   *   The number of associated groups.
   */
  public function getGroupCount(EntityInterface $entity, $group_type_id = NULL, $group_bundle = NULL);

  /**
   * Returns all the group content IDs associated with a given group entity.
   *
   * This does not return information about users that are members of the given
   * group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group entity for which to return group content IDs.
   * @param array $entity_types
   *   Optional list of group content entity types for which to return results.
   *   If an empty array is passed, the group content is not filtered. Defaults
   *   to an empty array.
   *
   * @return array
   *   An associative array, keyed by group content entity type, each item an
   *   array of group content entity IDs.
   */
  public function getGroupContentIds(EntityInterface $entity, array $entity_types = []);

  /**
   * Returns whether a user belongs to a group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to test the membership for.
   * @param array $states
   *   (optional) Array with the membership states to check the membership.
   *   Defaults to active memberships.
   *
   * @return bool
   *   TRUE if the entity (e.g. the user or node) belongs to a group with
   *   a certain state.
   */
  public function isMember(EntityInterface $group, AccountInterface $user, $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns whether a user belongs to a group with a pending status.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user entity.
   *
   * @return bool
   *   True if the membership is pending.
   *
   * @see \Drupal\og\Og::isMember
   */
  public function isMemberPending(EntityInterface $group, AccountInterface $user);

  /**
   * Returns whether an entity belongs to a group with a blocked status.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The entity to test the membership for.
   *
   * @return bool
   *   True if the membership is blocked.
   *
   * @see \Drupal\og\Og::isMember
   */
  public function isMemberBlocked(EntityInterface $group, AccountInterface $user);

  /**
   * Check if the given entity type and bundle is a group.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle_id
   *   The bundle name.
   *
   * @return bool
   *   True or false if the given entity is group.
   */
  public function isGroup($entity_type_id, $bundle_id);

  /**
   * Check if the given entity type and bundle is a group content.
   *
   * This is just a convenience wrapper around Og::getAllGroupAudienceFields().
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle_id
   *   The bundle name.
   *
   * @return bool
   *   True or false if the given entity is group content.
   */
  public function isGroupContent($entity_type_id, $bundle_id);

  /**
   * Sets an entity type instance as being an OG group.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle_id
   *   The bundle name.
   */
  public function addGroup($entity_type_id, $bundle_id);

  /**
   * Removes an entity type instance as being an OG group.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle_id
   *   The bundle name.
   *
   * @return bool
   *   True or false if the action succeeded.
   */
  public function removeGroup($entity_type_id, $bundle_id);

  /**
   * Returns the group manager instance.
   *
   * @return \Drupal\og\GroupManager
   *   Returns the group manager.
   */
  public function groupManager();

  /**
   * Get a role by the group's bundle and role name.
   *
   * @param string $entity_type_id
   *   The group entity type ID.
   * @param string $bundle
   *   The group bundle name.
   * @param string $role_name
   *   The role name.
   *
   * @return \Drupal\og\OgRoleInterface|null
   *   The OG role object, or NULL if a matching role was not found.
   */
  public function getRole($entity_type_id, $bundle, $role_name);

  /**
   * Return the og permission handler instance.
   *
   * @return \Drupal\og\OgPermissionHandler
   *   Returns the OG permissions handler.
   */
  public function permissionHandler();

  /**
   * Invalidate cache.
   *
   * @param array $group_ids
   *   Array with group IDs that their cache should be invalidated.
   */
  public function invalidateCache(array $group_ids = array());

  /**
   * Get an OG field base definition.
   *
   * @param string $plugin_id
   *   The plugin ID, which is also the default field name.
   *
   * @throws \Exception
   *
   * @return OgFieldBase|bool
   *   An array with the field storage config and field config definitions, or
   *   FALSE if none found.
   */
  protected static function getFieldBaseDefinition($plugin_id);

  /**
   * Get the selection handler for an audience field attached to entity.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $options
   *   Overriding the default options of the selection handler.
   *
   * @return OgSelection
   *   Returns the OG selection handler.
   *
   * @throws \Exception
   */
  public function getSelectionHandler(FieldDefinitionInterface $field_definition, array $options = []);

  /**
   * Resets the static cache.
   */
  public function reset();
}
