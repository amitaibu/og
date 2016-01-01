<?php

/**
 * @file
 * Contains /Drupal/og/OgRolesHandler
 */

namespace Drupal\og;

use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgRole;
use Drupal\og\Entity\OgUsersRole;

class OgRolesStorage {

  /**
   * {@inheritdoc}
   */
  public function assignRole(AccountInterface $account, OgRole $role) {
    $users_role = OgUsersRole::create();
    $users_role
      ->setUid($account)
      ->setRid($role)
      ->save();

    return $users_role;
  }

  public function revokeRole() {

  }

  /**
   * {@inheritdoc}
   */
  public function getAssignedRoles($uid) {
    $query = \Drupal::entityQuery('og_users_role');
    $results = $query
      ->condition('uid', $uid)
      ->execute();

    if (empty($results)) {
      return [];
    }

    return \Drupal::entityTypeManager()->getStorage('og_users_role')->loadMultiple($results);
  }

}
