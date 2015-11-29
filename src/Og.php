<?php

/**
 * @file
 * Contains \Drupal\og\Og.
 */

namespace Drupal\og;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\og\Plugin\EntityReferenceSelection\OgSelection;

/**
 * A static helper class for OG.
 */
class Og {

  /**
   * Static cache for groups per entity.
   *
   * @var array
   */
  protected static $entityGroupCache = [];

  /**
   * Create an organic groups field in a bundle.
   *
   * @param string $plugin_id
   *   The OG field plugin ID, which is also the default field name.
   * @param $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle name.
   * @param array $settings
   *   (Optional) allow overriding the default definitions of the field storage
   *   config and field config.
   *   Allowed values:
   *   - field_storage_config: Array with values to override the field storage
   *     config definitions. Values should comply with FieldStorageConfig::create()
   *   - field_config: Array with values to override the field config
   *     definitions. Values should comply with FieldConfig::create()
   */
  public static function createField($plugin_id, $entity_type, $bundle, array $settings = []) {
    $settings = $settings + [
      'field_storage_config' => [],
      'field_config' => [],
    ];

    $field_name = !empty($settings['field_name']) ? $settings['field_name'] : $plugin_id;

    // Get the field definition and add the entity info to it. By doing so
    // we validate the the field can be attached to the entity. For example,
    // the OG accesss module's field can be attached only to node entities, so
    // any other entity will throw an exception.
    /** @var \Drupal\og\OgFieldBase $og_field */
    $og_field = static::getFieldBaseDefinition($plugin_id)
      ->setFieldName($field_name)
      ->setBundle($bundle)
      ->setEntityType($entity_type);

    if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
      $field_storage_config = NestedArray::mergeDeep($og_field->getFieldStorageConfigBaseDefinition(), $settings['field_storage_config']);
      FieldStorageConfig::create($field_storage_config)->save();
    }


    if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
      $field_config = NestedArray::mergeDeep($og_field->getFieldConfigBaseDefinition(), $settings['field_config']);
      FieldConfig::create($field_config)->save();

      // @todo: Verify this is still needed here.
      static::invalidateCache();
    }

  }

  /**
   * Gets the groups an entity is associated with.
   *
   * @param $entity_type
   *   The entity type.
   * @param $entity_id
   *   The entity ID.
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
  public static function getEntityGroups($entity_type, $entity_id, $states = [OG_STATE_ACTIVE], $field_name = NULL) {
    // Get a string identifier of the states, so we can retrieve it from cache.
    if ($states) {
      sort($states);
      $state_identifier = implode(':', $states);
    }
    else {
      $state_identifier = FALSE;
    }

    $identifier = [
      $entity_type,
      $entity_id,
      $state_identifier,
      $field_name,
    ];

    $identifier = implode(':', $identifier);
    if (isset(static::$entityGroupCache[$identifier])) {
      // Return cached values.
      return static::$entityGroupCache[$identifier];
    }

    static::$entityGroupCache[$identifier] = [];
    $query = \Drupal::entityQuery('og_membership')
      ->condition('entity_type', $entity_type)
      ->condition('etid', $entity_id);

    if ($states) {
      $query->condition('state', $states, 'IN');
    }

    if ($field_name) {
      $query->condition('field_name', $field_name);
    }

    $results = $query->execute();

    /** @var \Drupal\og\Entity\OgMembership[] $memberships */
    $memberships = \Drupal::entityTypeManager()
      ->getStorage('og_membership')
      ->loadMultiple($results);

    /** @var \Drupal\og\Entity\OgMembership $membership */
    foreach ($memberships as $membership) {
      static::$entityGroupCache[$identifier][$membership->getGroupType()][$membership->id()] = $membership->getGroup();
    }

    return static::$entityGroupCache[$identifier];
  }

  /**
   * Check if the given entity is a group.
   *
   * @param string $entity_type_id
   * @param string $bundle
   *
   * @return bool
   *   True or false if the given entity is group.
   */
  public static function isGroup($entity_type_id, $bundle_id) {
    return static::groupManager()->isGroup($entity_type_id, $bundle_id);
  }

  /**
   * Sets an entity type instance as being an OG group.
   *
   * @param string $entity_type_id
   * @param string $bundle_id
   */
  public static function addGroup($entity_type_id, $bundle_id) {
    return static::groupManager()->addGroup($entity_type_id, $bundle_id);
  }

  /**
   * Removes an entity type instance as being an OG group.
   *
   * @param string $entity_type_id
   * @param string $bundle_id
   */
  public static function removeGroup($entity_type_id, $bundle_id) {
    return static::groupManager()->removeGroup($entity_type_id, $bundle_id);
  }

  /**
   * Return TRUE if field is a group audience type.
   *
   * @param $field_config
   *   The field config object.
   *
   * @return bool
   */
  public static function isGroupAudienceField(FieldDefinitionInterface $field_config) {
    return $field_config->getType() === 'og_membership_reference';
  }

  /**
   * Returns the group manager instance.
   *
   * @return \Drupal\og\GroupManager
   */
  public static function groupManager() {
    // @todo store static reference for this?
    return \Drupal::service('og.group.manager');
  }

  /**
   * Return the og permission handler instance.
   *
   * @return \Drupal\og\OgPermissionHandler;
   */
  public static function permissionHandler() {
    return \Drupal::service('og.permissions');
  }

  /**
   * Invalidate cache.
   *
   * @param $group_ids
   *   Array with group IDs that their cache should be invalidated.
   */
  public static function invalidateCache($group_ids = array()) {
    // @todo We should not be using drupal_static() review and remove.
    // Reset static cache.
    $caches = array(
      'og_user_access',
      'og_user_access_alter',
      'og_role_permissions',
      'og_get_user_roles',
      'og_get_permissions',
      'og_get_group_audience_fields',
      'og_get_entity_groups',
      'og_get_membership',
      'og_get_field_og_membership_properties',
      'og_get_user_roles',
    );

    foreach ($caches as $cache) {
      drupal_static_reset($cache);
    }

    // @todo Consider using a reset() method.
    static::$entityGroupCache = [];

    // Invalidate the entity property cache.
    \Drupal::entityManager()->clearCachedDefinitions();
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Let other OG modules know we invalidate cache.
    \Drupal::moduleHandler()->invokeAll('og_invalidate_cache', $group_ids);
  }

  /**
   * Gets the storage manage for the OG membership entity.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  public static function membershipStorage() {
    return \Drupal::entityManager()->getStorage('og_membership');
  }

  /**
   * Gets the default constructor parameters for OG membership.
   */
  public static function membershipDefault() {
    return ['type' => 'og_membership_type_default'];
  }


  /**
   * Get an OG field base definition.
   *
   * @param string $plugin_id
   *   The plugin ID, which is also the default field name.
   *
   * @throws \Exception
   * @return OgFieldBase|bool
   *   An array with the field storage config and field config definitions, or
   *   FALSE if none found.
   */
  protected static function getFieldBaseDefinition($plugin_id) {
    /** @var OgFieldsPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.og.fields');
    if (!$field_config = $plugin_manager->getDefinition($plugin_id)) {

      $params = ['@plugin' => $plugin_id];
      throw new \Exception(new FormattableMarkup('The Organic Groups field with plugin ID @plugin is not a valid plugin.', $params));
    }

    return $plugin_manager->createInstance($plugin_id);
  }

  /**
   * Get the selection handler for an audience field attached to entity.
   *
   * @param $entity
   *   The entity type.
   * @param $bundle
   *   The bundle name.
   * @param $field_name
   *   The field name.
   * @param array $options
   *   Overriding the default options of the selection handler.
   *
   * @return OgSelection
   * @throws \Exception
   */
  public static function getSelectionHandler($entity, $bundle, $field_name, array $options = []) {
    $field_definition = FieldConfig::loadByName($entity, $bundle, $field_name);

    if (!Og::isGroupAudienceField($field_definition)) {
      throw new \Exception(new FormattableMarkup('The field @name is not an audience field.', ['@name' => $field_name]));
    }

    $options += [
      'target_type' => $field_definition->getFieldStorageDefinition()->getSetting('target_type'),
      'field' => $field_definition,
      'handler' => $field_definition->getSetting('handler'),
      'handler_settings' => [],
    ];

    // Deep merge the handler settings.
    $options['handler_settings'] = NestedArray::mergeDeep($field_definition->getSetting('handler_settings'), $options['handler_settings']);

    return \Drupal::service('plugin.manager.entity_reference_selection')->createInstance('og:default', $options);
  }

  /**
   * Create a query against a specific permission realm: role, role permission
   * or user role.
   *
   * @param $realm
   *   The realm. i.e. role
   * @param $main_property
   *   The name of the main property to query against. i.e. name.
   * @param $property_value
   *   The value of the main property.
   * @param $fields
   *   Additional fields to the query.
   *
   * @return array
   *   List of entity IDs.
   */
  public static function permissionQueryConstructor($realm, $main_property, $property_value, $fields) {
    $query = \Drupal::entityQuery($realm)
      ->condition($main_property, $property_value, '=');

    foreach ($fields as $field => $info) {
      if (is_array($info)) {
        $value = $info['value'];
        $operator = $info['operator'];
      }
      else {
        $value = $info;
        $operator = '=';
      }

      $query->condition($field, $value, $operator);
    }

    return $query->execute();

  }

  /**
   * load object of a specific permission realm: role, role permission or user
   * role.
   *
   * @param $realm
   *   The realm. i.e. role
   * @param $main_property
   *   The name of the main property to query against. i.e. name.
   * @param $property_value
   *   The value of the main property.
   * @param $fields
   *   Additional fields to the query.
   *
   * @return ContentEntityBase|ContentEntityBase[]
   *   List of entities.
   */
  public static function permissionObjectLoader($realm, $main_property, $property_value, $fields) {
    $rids = self::permissionQueryConstructor($realm, $main_property, $property_value, $fields);

    if (empty($rids)) {
      return NULL;
    }

    $storage = \Drupal::entityTypeManager()->getStorage($realm);

    return count($rids) == 1 ? $storage->load(reset($rids)) : $storage->loadMultiple($rids);
  }

}
