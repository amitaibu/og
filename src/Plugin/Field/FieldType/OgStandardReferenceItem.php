<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem.
 */

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Og;

/**
 * Class OgStandardReferenceItem.
 *
 * @FieldType(
 *   id = "og_standard_reference",
 *   label = @Translation("OG membership reference"),
 *   description = @Translation("An entity field containing an OG membership reference for user based entity."),
 *   category = @Translation("Reference"),
 *   no_ui = TRUE,
 *   default_widget = "og_complex",
 *   default_formatter = "entity_reference_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 *   constraints = {"ValidOgMembershipReference" = {}}
 * )
 */
class OgStandardReferenceItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = parent::defaultFieldSettings();
    $settings['access_override'] = FALSE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::fieldSettingsForm($form, $form_state);

    // Field access settings.
    $form['access_override'] = [
      '#title' => $this->t('Allow entity access to control field access'),
      '#description' => $this->t('By default, the <em>administer group</em> permission is required to directly edit this field. Selecting this option will allow access to anybody with access to edit the entity.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('access_override'),
    ];

    return $form;
  }

  /**
   * Overrides parent::getSettableOptions().
   *
   * Return the list of allowed groups to reference the content to.
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    $field_definition = $this->getFieldDefinition();
    $field_mode = !empty($field_definition->otherGroup) ? 'admin' : 'default';

    if (!$options = Og::getSelectionHandler($this->getFieldDefinition(), ['handler_settings' => ['field_mode' => $field_mode]])->getReferenceableEntities()) {
      return array();
    }

    // Rebuild the array by changing the bundle key into the bundle label.
    $target_type = $field_definition->getSetting('target_type');
    $bundles = \Drupal::entityManager()->getBundleInfo($target_type);

    $return = array();
    foreach ($options as $bundle => $entity_ids) {
      // The label does not need sanitizing since it is used as an optgroup
      // which is only supported by select elements and auto-escaped.
      $bundle_label = (string) $bundles[$bundle]['label'];
      $return[$bundle_label] = $entity_ids;
    }

    return count($return) == 1 ? reset($return) : $return;
  }

}
