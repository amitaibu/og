<?php

/**
 * @file
 * Contains \Drupal\og\OgSelectionTrait.
 */

namespace Drupal\og;

use Drupal\user\Entity\User;

/**
 * Trait for common methods for OG Selection handlers.
 */
trait OgSelectionTrait {

  /**
   * The original selection handler for the field.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
   */
  protected $selectionHandler;

  /**
   * Get the selection handler of the field.
   *
   * @return \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
   */
  public function getSelectionHandler() {
    if (!isset($this->selectionHandler)) {
      $options = [
        'target_type' => $this->configuration['target_type'],
        'handler' => $this->configuration['handler_settings']['original_handler'],
        'handler_settings' => $this->configuration['handler_settings'],
      ];
      $this->selectionHandler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
    }
    return $this->selectionHandler;
  }

  /**
   * Get hold of the groups this user is part of.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected function getUserGroups() {
    $other_groups = Og::getEntityGroups(User::load($this->currentUser->id()));
    return isset($other_groups[$this->configuration['target_type']]) ? $other_groups[$this->configuration['target_type']] : [];
  }

}
