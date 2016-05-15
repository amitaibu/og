<?php

/**
 * @file
 * Contains \Drupal\og\OgGroupAudienceHelper.
 */

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\og\Plugin\Field\FieldWidget\OgComplex;
use Drupal\Component\Utility\Html;

/**
 * OG audience field helper methods.
 */
class OgGroupAudienceHelper {

  /**
   * The default OG audience field name.
   */
  const DEFAULT_FIELD = 'og_group_ref';


  /**
   * Return TRUE if field is a group audience type.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition object.
   *
   * @return bool
   *   TRUE if the field is a group audience type, FALSE otherwise.
   */
  public static function isGroupAudienceField(FieldDefinitionInterface $field_definition) {
    return in_array($field_definition->getType(), ['og_standard_reference', 'og_membership_reference']);
  }


  /**
   * Return TRUE if a field can be used and has not reached maximum values.
   *d
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to check the field cardinality for.
   * @param string $field_name
   *   The field name to check the cardinality of.
   *
   * @return bool
   *
   * @throws \Drupal\Core\Field\FieldException
   */
  public static function checkFieldCardinality(ContentEntityInterface $entity, $field_name) {
    $field_definition = $entity->getFieldDefinition($field_name);

    $entity_type_id = $entity->getEntityTypeId();
    $bundle_id = $entity->bundle();

    if (!$field_definition) {
      throw new FieldException("No field with the name $field_name found for $bundle_id $entity_type_id entity.");
    }

    if (!static::isGroupAudienceField($field_definition)) {
      throw new FieldException("$field_name field on $bundle_id $entity_type_id entity is not an audience field.");
    }

    $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();

    if ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      return TRUE;
    }

    return $entity->get($field_name)->count() < $cardinality;
  }

  /**
   * Returns the first group audience field that matches the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The group content to find a matching group audience field for.
   * @param string $group_type
   *   The group type that should be referenced by the group audience field.
   * @param string $group_bundle
   *   The group bundle that should be referenced by the group audience field.
   * @param bool $check_access
   *   (optional) Set this to FALSE to not check if the current user has access
   *   to the field. Defaults to TRUE.
   *
   * @return string|NULL
   *   The name of the group audience field, or NULL if no matching field was
   *   found.
   */
  public static function getMatchingField(ContentEntityInterface $entity, $group_type, $group_bundle, $check_access = TRUE) {
    $fields = static::getAllGroupAudienceFields($entity->getEntityTypeId(), $entity->bundle());

    // Bail out if there are no group audience fields.
    if (!$fields) {
      return NULL;
    }

    foreach ($fields as $field_name => $field) {
      $handler_settings = $field->getSetting('handler_settings');

      if ($field->getSetting('target_type') !== $group_type) {
        // Group type doesn't match.
        continue;
      }

      if (!empty($handler_settings['target_bundles']) && !in_array($group_bundle, $handler_settings['target_bundles'])) {
        // Bundle doesn't match.
        continue;
      }

      if (!static::checkFieldCardinality($entity, $field_name)) {
        // The field cardinality has reached its maximum
        continue;
      }

      if ($check_access && !$entity->get($field_name)->access('view')) {
        // The user doesn't have access to the field.
        continue;
      }

      return $field_name;
    }

    return NULL;
  }

  /**
   * Get list of available widgets.
   *
   * @return array
   *   List of available entity reference widgets.
   */
  public static function getAvailableWidgets() {
    $widget_manager = \Drupal::getContainer()->get('plugin.manager.field.widget');
    $definitions = $widget_manager->getDefinitions();

    $widgets = [];
    foreach ($definitions as $id => $definition) {

      if (!in_array('entity_reference', $definition['field_types'])) {
        continue;
      }

      $widgets[] = $id;
    }

    return $widgets;
  }

  /**
   * Set the field mode widget.
   *
   * @param $entity_id
   *   The entity id.
   * @param $bundle
   *   The bundle.
   * @param $field_name
   *   The field name.
   * @param array $modes
   *   The field modes. Available keys: default, admin.
   *
   * @return int
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   */
  public static function setWidgets($entity_id, $bundle, $field_name, array $modes) {
    $field = FieldConfig::loadByName($entity_id, $bundle, $field_name);
    $handler = $field->getSetting('handler_settings');
    $handler['widgets'] = $modes;
    $field->setSetting('handler_settings', $handler);
    return $field->save();
  }

  /**
   * get the field mode widget.
   *
   * @param $entity_id
   *   The entity id.
   * @param $bundle
   *   The bundle.
   * @param $field_name
   *   The field name.
   * @param null $mode
   *   The field mode - admin or default.
   *
   * @return array.
   *   The field modes.
   */
  public static function getWidgets($entity_id, $bundle, $field_name, $mode = NULL) {
    $field = FieldConfig::loadByName($entity_id, $bundle, $field_name);
    $handler = $field->getSetting('handler_settings');
    return $mode ? $handler['widgets'][$mode] : $handler['handler_settings']['widgets'];
  }

  /**
   * @param FieldDefinitionInterface $field
   *   The field definition.
   * @param $widget_id
   *   An entity reference widget plugin id i.e: options_select, options_buttons.
   * @param string $field_name
   *   The field name. Default to self::DEFAULT_FIELD.
   * @param array $configuration
   *   Configuration which will be passed to the widget instance.
   *
   * @return WidgetBase The form API widget element.
   * The form API widget element.
   */
  public static function renderWidget(FieldDefinitionInterface $field, $widget_id, $field_name = self::DEFAULT_FIELD, array $configuration = []) {
    $config = FieldConfig::load($field->getTargetEntityTypeId() . '.' . $field->getTargetBundle() . '.' . $field_name);

    $default_configuration = $configuration + [
        'type' => 'og_complex',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'third_party_settings' => [],
        'field_definition' => $config,
      ];

    return \Drupal::getContainer()
      ->get('plugin.manager.field.widget')
      ->createInstance($widget_id, $default_configuration);

  }

  /**
   * Return all the group audience fields of a certain bundle.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string  $bundle
   *   The bundle name to be checked.
   * @param string $group_type_id
   *   Filter list to only include fields referencing a specific group type.
   * @param string $group_bundle
   *   Filter list to only include fields referencing a specific group bundle.
   *   Fields that do not specify any bundle restrictions at all are also
   *   included.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of field definitions, keyed by field name; Or an empty array if
   *   none found.
   */
  public static function getAllGroupAudienceFields($entity_type_id, $bundle, $group_type_id = NULL, $group_bundle = NULL) {
    $return = [];
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);

    if (!$entity_type->isSubclassOf(FieldableEntityInterface::class)) {
      // This entity type is not fieldable.
      return [];
    }
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);

    foreach ($field_definitions as $field_definition) {
      if (!static::isGroupAudienceField($field_definition)) {
        // Not a group audience field.
        continue;
      }

      $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');

      if (isset($group_type_id) && $target_type != $group_type_id) {
        // Field doesn't reference this group type.
        continue;
      }

      $handler_settings = $field_definition->getSetting('handler_settings');

      if (isset($group_bundle) && !empty($handler_settings['target_bundles']) && !in_array($group_bundle, $handler_settings['target_bundles'])) {
        continue;
      }

      $field_name = $field_definition->getName();
      $return[$field_name] = $field_definition;
    }

    return $return;
  }

}
