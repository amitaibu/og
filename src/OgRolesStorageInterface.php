<?php

/**
 * @file
 * Contains \Drupal\og\OgRolesStorageInterface.
 */

namespace Drupal\og;

use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgRole;

interface OgRolesStorageInterface {

  /**
   * Grant role to user.
   *
   * @param $account
   *   The account instance.
   * @param $role
   *   The role instance.
   *
   * @return \Drupal\og\Entity\OgUsersRole
   */
  public function assignRole(AccountInterface $account, OgRole $role);

  /**
   * Get the assigned roles to a user.
   *
   * @param $uid
   *   The user ID.
   *
   * @return \Drupal\og\Entity\OgUsersRole[]
   *   Array of assigned roles to a user.
   */
  public function getAssignedRoles($uid);

}
