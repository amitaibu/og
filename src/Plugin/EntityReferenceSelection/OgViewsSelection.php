<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\EntityReferenceSelection\OgDefaultSelection.
 */

namespace Drupal\og\Plugin\EntityReferenceSelection;

use Drupal\views\Plugin\EntityReferenceSelection\ViewsSelection;
use Drupal\og\OgSelectionTrait;

/**
 * Provide a proxy OG selection handler for
 * \Drupal\views\Plugin\EntityReferenceSelection\ViewsSelection.
 *
 * Note that the id is correctly defined as "og:views" and not the other way
 * around, as seen in most other default selection handler (e.g. "default:node")
 * as OG's selection handler is a wrapper around those entity specific views
 * ones. That is, the same selection handler will be returned no matter what is
 * the target type of the reference field. Internally, it will call the original
 * selection handler, and use it for building the queries.
 *
 * @EntityReferenceSelection(
 *   id = "og:views",
 *   label = @Translation("OG selection"),
 *   group = "og",
 *   weight = 1
 * )
 */
class OgViewsSelection extends ViewsSelection {

  use OgSelectionTrait;

  /**
   * {@inheritdoc}
   */
  protected function initializeView($match = NULL, $match_operator = 'CONTAINS', $limit = 0, $ids = NULL) {
    $return = $this->getSelectionHandler()->initializeView($match, $match_operator, $limit, $ids);
    $this->view = $this->getSelectionHandler()->view;
    if ($return) {
      $display = $this->view->getDisplay();

      // @todo: Find a way to ensure only group entities are returned.

      $user_groups = $this->getUserGroups();
      $group_ids = [];
      foreach ($user_groups as $delta => $group) {
        $group_ids[] = $group->id();
      }

      if ($user_groups) {
        if ($this->configuration['handler_settings']['field_mode'] == 'admin') {
          // @todo: Ideally find a way to exclude certain IDs at a query level.
        }
        else {
          // Determine which groups should be selectable.
          if ($group_ids) {
            $entity_reference_options = $display->getOption('entity_reference_options');
            if (isset($entity_reference_options['ids'])) {
              $entity_reference_options['ids'] = array_intersect($entity_reference_options['ids'], $group_ids);
            }
            else {
              $entity_reference_options['ids'] = $group_ids;
            }
            $display->setOption('entity_reference_options', $entity_reference_options);
          }
          else {
            // User doesn't have permission to select any group simply return
            // FALSE.
            return FALSE;
          }
        }
      }
    }
    return $return;
  }

}
