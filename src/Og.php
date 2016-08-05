<?php

namespace Drupal\og;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;

/**
 * A helper class for OG.
 */
class Og implements OgInterface{

  /**
   * Static cache for heavy queries.
   *
   * @var array
   */
  protected $cache = [];


  /**
   * The group manager service.
   *
   * @var \Drupal\og\GroupManager
   */
  protected $groupManager;

  /**
   * The OG permission handler service.
   *
   * @var \Drupal\og\OgPermissionHandler
   */
  protected $ogPermissionHandler;

  /**
   * The OG fields plugin manager service.
   *
   * @var \Drupal\og\OgFieldsPluginManager
   */
  protected $ogField;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityField;

  /**
   * The selection plugin manager service.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager
   */
  protected $entityReferenceSelection;


  /**
   * Constructs an Og service.
   *
   * @param \Drupal\og\GroupManager $group_manager
   *   The group manager service.
   * @param \Drupal\og\OgPermissionHandler $og_permission_handler
   *   The OG permission handler service.
   * @param \Drupal\og\OgFieldsPluginManager $og_field
   *   The OG fields plugin manager service.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager $entity_reference_selection
   *   The selection plugin manager service.
   */
  public function __construct(GroupManager $group_manager, OgPermissionHandler $og_permission_handler, OgFieldsPluginManager $og_field, EntityFieldManager $entity_field, SelectionPluginManager $entity_reference_selection) {
    $this->groupManager = $group_manager;
    $this->ogPermissionHandler = $og_permission_handler;
    $this->ogField = $og_field;
    $this->entityField = $entity_field;
    $this->entityReferenceSelection = $entity_reference_selection;
  }

  /**
   * @{@inheritdoc}
   */
  public function createField($plugin_id, $entity_type, $bundle, array $settings = []) {
    $settings = $settings + [
      'field_storage_config' => [],
      'field_config' => [],
      'form_display' => [],
      'view_display' => [],
    ];

    $field_name = !empty($settings['field_name']) ? $settings['field_name'] : $plugin_id;

    // Get the field definition and add the entity info to it. By doing so
    // we validate the the field can be attached to the entity. For example,
    // the OG access module's field can be attached only to node entities, so
    // any other entity will throw an exception.
    /** @var \Drupal\og\OgFieldBase $og_field */
    $og_field = self::getFieldBaseDefinition($plugin_id)
      ->setFieldName($field_name)
      ->setBundle($bundle)
      ->setEntityType($entity_type);

    if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
      $field_storage_config = NestedArray::mergeDeep($og_field->getFieldStorageBaseDefinition(), $settings['field_storage_config']);
      FieldStorageConfig::create($field_storage_config)->save();
    }

    if (!$field_definition = FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
      $field_config = NestedArray::mergeDeep($og_field->getFieldBaseDefinition(), $settings['field_config']);

      $field_definition = FieldConfig::create($field_config);
      $field_definition->save();

      // @todo: Verify this is still needed here.
      self::invalidateCache();
    }

    // Make the field visible in the default form display.
    /** @var EntityFormDisplayInterface $form_display */
    $form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load("$entity_type.$bundle.default");

    // If not found, create a fresh form display object. This is by design,
    // configuration entries are only created when an entity form display is
    // explicitly configured and saved.
    if (!$form_display) {
      $form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    $form_display_definition = $og_field->getFormDisplayDefinition($settings['form_display']);

    $form_display->setComponent($plugin_id, $form_display_definition);
    $form_display->save();

    // Set the view display for the "default" view display.
    $view_display_definition = $og_field->getViewDisplayDefinition($settings['view_display']);

    /** @var EntityDisplayInterface $view_display */
    $view_display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load("$entity_type.$bundle.default");

    if (!$view_display) {
      $view_display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    $view_display->setComponent($plugin_id, $view_display_definition);
    $view_display->save();

    // Refresh the group manager data, we have added a group type.
    self::groupManager()->resetGroupRelationMap();

    return $field_definition;
  }

  /**
   * @{@inheritdoc}
   */
  public function getUserGroupIds(AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]) {
    $group_ids = [];

    /** @var \Drupal\og\Entity\OgMembership[] $memberships */
    $memberships = self::getMemberships($user, $states);
    foreach ($memberships as $membership) {
      $group_ids[$membership->getGroupEntityType()][] = $membership->getGroupId();
    }

    return $group_ids;
  }

  /**
   * @{@inheritdoc}
   */
  public function getUserGroups(AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]) {
    $groups = [];

    foreach (self::getUserGroupIds($user, $states) as $entity_type => $entity_ids) {
      $groups[$entity_type] = \Drupal::entityTypeManager()->getStorage($entity_type)->loadMultiple($entity_ids);
    }

    return $groups;
  }

  /**
   * @{@inheritdoc}
   */
  public function getMemberships(AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]) {
    // Get a string identifier of the states, so we can retrieve it from cache.
    sort($states);
    $states_identifier = implode('|', array_unique($states));

    $identifier = [
      __METHOD__,
      $user->id(),
      $states_identifier,
    ];
    $identifier = implode(':', $identifier);

    // Return cached result if it exists.
    if (isset(self::$cache[$identifier])) {
      return self::$cache[$identifier];
    }

    $query = \Drupal::entityQuery('og_membership')
      ->condition('uid', $user->id());

    if ($states) {
      $query->condition('state', $states, 'IN');
    }

    $results = $query->execute();

    /** @var \Drupal\og\Entity\OgMembership[] $memberships */
    self::$cache[$identifier] = \Drupal::entityTypeManager()
      ->getStorage('og_membership')
      ->loadMultiple($results);

    return self::$cache[$identifier];
  }

  /**
   * @{@inheritdoc}
   */
  public function getMembership(EntityInterface $group, AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]) {
    foreach (self::getMemberships($user, $states) as $membership) {
      if ($membership->getGroupEntityType() === $group->getEntityTypeId() && $membership->getGroupId() === $group->id()) {
        return $membership;
      }
    }
  }

  /**
   * @{@inheritdoc}
   */
  public function createMembership(EntityInterface $group, AccountInterface $user, $membership_type = OgMembershipInterface::TYPE_DEFAULT) {
    /** @var OgMembershipInterface $membership */
    $membership = OgMembership::create(['type' => $membership_type]);
    $membership
      ->setUser($user)
      ->setGroup($group);

    return $membership;
  }

  /**
   * @{@inheritdoc}
   */
  public function getGroupIds(EntityInterface $entity, $group_type_id = NULL, $group_bundle = NULL) {
    // This does not work for user entities.
    if ($entity->getEntityTypeId() === 'user') {
      throw new \InvalidArgumentException('\Drupal\og\Og::getGroupIds() cannot be used for user entities. Use \Drupal\og\Og::getUserGroups() instead.');
    }

    $identifier = [
      __METHOD__,
      $entity->id(),
      $group_type_id,
      $group_bundle,
    ];

    $identifier = implode(':', $identifier);

    if (isset(self::$cache[$identifier])) {
      // Return cached values.
      return self::$cache[$identifier];
    }

    $group_ids = [];

    $fields = OgGroupAudienceHelper::getAllGroupAudienceFields($entity->getEntityTypeId(), $entity->bundle(), $group_type_id, $group_bundle);
    foreach ($fields as $field) {
      $target_type = $field->getFieldStorageDefinition()->getSetting('target_type');

      // Optionally filter by group type.
      if (!empty($group_type_id) && $group_type_id !== $target_type) {
        continue;
      }

      // Compile a list of group target IDs.
      $target_ids = array_map(function ($value) {
        return $value['target_id'];
      }, $entity->get($field->getName())->getValue());

      if (empty($target_ids)) {
        continue;
      }

      // Query the database to get the actual list of groups. The target IDs may
      // contain groups that no longer exist. Entity reference doesn't clean up
      // orphaned target IDs.
      $entity_type = \Drupal::entityTypeManager()->getDefinition($target_type);
      $query = \Drupal::entityQuery($target_type)
        ->condition($entity_type->getKey('id'), $target_ids, 'IN');

      // Optionally filter by group bundle.
      if (!empty($group_bundle)) {
        $query->condition($entity_type->getKey('bundle'), $group_bundle);
      }

      $group_ids = NestedArray::mergeDeep($group_ids, [$target_type => $query->execute()]);
    }

    self::$cache[$identifier] = $group_ids;

    return $group_ids;
  }

  /**
   * @{@inheritdoc}
   */
  public function getGroups(EntityInterface $entity, $group_type_id = NULL, $group_bundle = NULL) {
    $groups = [];

    foreach (self::getGroupIds($entity, $group_type_id, $group_bundle) as $entity_type => $entity_ids) {
      $groups[$entity_type] = \Drupal::entityTypeManager()->getStorage($entity_type)->loadMultiple($entity_ids);
    }

    return $groups;
  }

  /**
   * @{@inheritdoc}
   */
  public function getGroupCount(EntityInterface $entity, $group_type_id = NULL, $group_bundle = NULL) {
    return array_reduce(self::getGroupIds($entity, $group_type_id, $group_bundle), function ($carry, $item) {
      return $carry + count($item);
    }, 0);
  }

  /**
   * @{@inheritdoc}
   */
  public function getGroupContentIds(EntityInterface $entity, array $entity_types = []) {
    $group_content = [];

    // Retrieve the fields which reference our entity type and bundle.
    $query = \Drupal::entityQuery('field_storage_config')
      ->condition('type', OgGroupAudienceHelper::GROUP_REFERENCE);

    // Optionally filter group content entity types.
    if ($entity_types) {
      $query->condition('entity_type', $entity_types, 'IN');
    }

    /** @var \Drupal\field\FieldStorageConfigInterface[] $fields */
    $fields = array_filter(FieldStorageConfig::loadMultiple($query->execute()), function (FieldStorageConfigInterface $field) use ($entity) {
      $type_matches = $field->getSetting('target_type') === $entity->getEntityTypeId();
      // If the list of target bundles is empty, it targets all bundles.
      $bundle_matches = empty($field->getSetting('target_bundles')) || in_array($entity->bundle(), $field->getSetting('target_bundles'));
      return $type_matches && $bundle_matches;
    });

    // Compile the group content.
    foreach ($fields as $field) {
      $group_content_entity_type = $field->getTargetEntityTypeId();

      // Group the group content per entity type.
      if (!isset($group_content[$group_content_entity_type])) {
        $group_content[$group_content_entity_type] = [];
      }

      // Query all group content that references the group through this field.
      $results = \Drupal::entityQuery($group_content_entity_type)
        ->condition($field->getName() . '.target_id', $entity->id())
        ->execute();

      $group_content[$group_content_entity_type] = array_merge($group_content[$group_content_entity_type], $results);
    }

    return $group_content;
  }

  /**
   * @{@inheritdoc}
   */
  public function isMember(EntityInterface $group, AccountInterface $user, $states = [OgMembershipInterface::STATE_ACTIVE]) {
    $group_ids = self::getUserGroupIds($user, $states);
    $entity_type_id = $group->getEntityTypeId();
    return !empty($group_ids[$entity_type_id]) && in_array($group->id(), $group_ids[$entity_type_id]);
  }

  /**
   * @{@inheritdoc}
   */
  public function isMemberPending(EntityInterface $group, AccountInterface $user) {
    return self::isMember($group, $user, [OgMembershipInterface::STATE_PENDING]);
  }

  /**
   * @{@inheritdoc}
   */
  public function isMemberBlocked(EntityInterface $group, AccountInterface $user) {
    return self::isMember($group, $user, [OgMembershipInterface::STATE_BLOCKED]);
  }

  /**
   * @{@inheritdoc}
   */
  public function isGroup($entity_type_id, $bundle_id) {
    return self::groupManager()->isGroup($entity_type_id, $bundle_id);
  }

  /**
   * @{@inheritdoc}
   */
  public function isGroupContent($entity_type_id, $bundle_id) {
    return (bool) OgGroupAudienceHelper::getAllGroupAudienceFields($entity_type_id, $bundle_id);
  }

  /**
   * @{@inheritdoc}
   */
  public function addGroup($entity_type_id, $bundle_id) {
    self::groupManager()->addGroup($entity_type_id, $bundle_id);
  }

  /**
   * @{@inheritdoc}
   */
  public function removeGroup($entity_type_id, $bundle_id) {
    return self::groupManager()->removeGroup($entity_type_id, $bundle_id);
  }

  /**
   * @{@inheritdoc}
   */
  public function groupManager() {
    // @todo store static reference for this?
    return \Drupal::service('og.group.manager');
  }

  /**
   * @{@inheritdoc}
   */
  public function getRole($entity_type_id, $bundle, $role_name) {
    return OgRole::load($entity_type_id . '-' . $bundle . '-' . $role_name);
  }

  /**
   * @{@inheritdoc}
   */
  public function permissionHandler() {
    return \Drupal::service('og.permissions');
  }

  /**
   * @{@inheritdoc}
   */
  public function invalidateCache(array $group_ids = array()) {
    // @todo We should not be using drupal_static() review and remove.
    // Reset static cache.
    $caches = array(
      'og_user_access',
      'og_user_access_alter',
      'og_role_permissions',
      'og_get_user_roles',
      'og_get_permissions',
      'og_get_entity_groups',
      'og_get_membership',
      'og_get_field_og_membership_properties',
      'og_get_user_roles',
    );

    foreach ($caches as $cache) {
      drupal_static_reset($cache);
    }

    // @todo Consider using a reset() method.
    self::$cache = [];

    // Invalidate the entity property cache.
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Let other OG modules know we invalidate cache.
    \Drupal::moduleHandler()->invokeAll('og_invalidate_cache', $group_ids);
  }

  /**
   * @{@inheritdoc}
   */
  public function getFieldBaseDefinition($plugin_id) {
    /** @var OgFieldsPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.og.fields');
    if (!$field_config = $plugin_manager->getDefinition($plugin_id)) {
      throw new \Exception("The Organic Groups field with plugin ID $plugin_id is not a valid plugin.");
    }

    return $plugin_manager->createInstance($plugin_id);
  }

  /**
   * @{@inheritdoc}
   */
  public function getSelectionHandler(FieldDefinitionInterface $field_definition, array $options = []) {
    if (!OgGroupAudienceHelper::isGroupAudienceField($field_definition)) {
      $field_name = $field_definition->getName();
      throw new \Exception("The field $field_name is not an audience field.");
    }

    $options = NestedArray::mergeDeep([
      'target_type' => $field_definition->getFieldStorageDefinition()->getSetting('target_type'),
      'handler' => $field_definition->getSetting('handler'),
      'handler_settings' => [
        'field_mode' => 'default',
      ],
    ], $options);

    // Deep merge the handler settings.
    $options['handler_settings'] = NestedArray::mergeDeep($field_definition->getSetting('handler_settings'), $options['handler_settings']);

    return \Drupal::service('plugin.manager.entity_reference_selection')->createInstance('og:default', $options);
  }

  /**
   * @{@inheritdoc}
   */
  public function reset() {
    self::$cache = [];
  }

}
