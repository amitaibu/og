<?php

/**
 * @file
 * Contains /Drupal/og/OgRolesHandler
 */

namespace Drupal\og;

use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgRole;

class OgRolesHandler {

  /**
   * Grant role to user.
   *
   * @param $account
   *   The account instance.
   * @param $role
   *   The role instance.
   *
   * @return bool
   */
  public function grantRole(AccountInterface $account, OgRole $role) {

  }

  public function revokeRole() {

  }

}
