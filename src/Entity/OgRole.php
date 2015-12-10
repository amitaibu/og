<?php

/**
 * @file
 * Contains Drupal\og\Entity\OgRole.
 */
namespace Drupal\og\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\og\Og;
use Drupal\system\Tests\Common\PageRenderTest;
use Drupal\user\Entity\User;

/**
 * @ConfigEntityType(
 *   id = "og_role",
 *   label = @Translation("OG role"),
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "group_type" = "groupType",
 *     "group_bundle" = "groupBundle",
 *     "uid" = "uid",
 *     "permissions" = "permissions"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "group_type",
 *     "group_bundle",
 *     "uid",
 *     "permissions"
 *   }
 * )
 */
class OgRole extends ConfigEntityBase {

  /**
   * @var integer
   *
   * The identifier of the role.
   */
  protected $id;

  /**
   * @var string
   *
   * The label of the role.
   */
  protected $label;

  /**
   * @var integer
   *
   * The group ID.
   */
  protected $groupID;

  /**
   * @var string
   *
   * The group type. i.e: node, user
   */
  protected $groupType;

  /**
   * @var string
   *
   * The group bundle. i.e: article, page
   */
  protected $groupBundle;

  /**
   * @var integer
   *
   * The user ID which the role assign to.
   */
  protected $uid;

  /**
   * @var array
   *
   * List of permissions.
   */
  protected $permissions;

  /**
   * @return int
   */
  public function getId() {
    return $this->get('id');
  }

  /**
   * @param int $id
   *
   * @return OgRole
   */
  public function setId($id) {
    $this->id = $id;
    $this->set('id', $id);
    return $this;
  }

  /**
   * @return string
   */
  public function getLabel() {
    return $this->get('label');
  }

  /**
   * @param string $label
   *
   * @return OgRole
   */
  public function setLabel($label) {
    $this->label = $label;
    $this->set('label', $label);
    return $this;
  }

  /**
   * @return int
   */
  public function getGroupID() {
    return $this->get('group_id');
  }

  /**
   * @param int $groupID
   *
   * @return OgRole
   */
  public function setGroupID($groupID) {
    $this->groupID = $groupID;
    $this->set('group_id', $groupID);
    return $this;
  }

  /**
   * @return string
   */
  public function getGroupType() {
    return $this->get('group_type');
  }

  /**
   * @param string $groupType
   *
   * @return OgRole
   */
  public function setGroupType($groupType) {
    $this->groupType = $groupType;
    $this->set('group_type', $groupType);
    return $this;
  }

  /**
   * @return string
   */
  public function getGroupBundle() {
    return $this->get('group_bundle');
  }

  /**
   * @param string $groupBundle
   *
   * @return OgRole
   */
  public function setGroupBundle($groupBundle) {
    $this->groupBundle = $groupBundle;
    $this->set('group_bundle', $groupBundle);
    return $this;
  }

  /**
   * @return int
   */
  public function getUid() {
    return $this->get('uid');
  }

  /**
   * @param int $uid
   *
   * @return OgRole
   */
  public function setUid($uid) {
    $this->uid = $uid;
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * @return array
   */
  public function getPermissions() {
    return $this->get('permissions');
  }

  /**
   * @param array $permissions
   *
   * @return OgRole
   */
  public function setPermissions($permissions) {
    $this->permissions = $permissions;
    $this->set('permissions', $permissions);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $errors = [];

    // Check the permission exists.
    foreach ($this->getPermissions() as $permission) {
      if (!in_array($permission, Og::permissionHandler()->getPermissions())) {
        $errors[] = new FormattableMarkup('The permissions @permission does not exists.', ['@permission' => $permission]);
      }
    }

    // Verify the given group type is a group.
    if (!Og::groupManager()->isGroup($this->getGroupType(), $this->getGroupBundle())) {
      $errors[] = new FormattableMarkup('@entity_type:@bundle does not defined as a group.', [
        '@entity_type' => $this->getGroupType(),
        '@bundle' => $this->getGroupBundle(),
      ]);
    }

    // Check if the user exists.
    if (!User::load($this->getUid())) {
      $errors[] = new FormattableMarkup('A user with the uid @uid does not exists.', ['@uid' => $this->getUid()]);
    }

    parent::preSave($storage);
  }

}
