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
   * Get the selection handler of the field.
   *
   * @return \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
   */
  public function getSelectionHandler() {
    $options = [
      'target_type' => $this->configuration['target_type'],
      'handler' => $this->configuration['handler'],
      'handler_settings' => $this->configuration['handler_settings'],
    ];
    return \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
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
