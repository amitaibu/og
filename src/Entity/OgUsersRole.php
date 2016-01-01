<?php

/**
 * @file
 * Contains Drupal\og\Entity\OgUsersRole.
 */

namespace Drupal\og\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\Entity\User;

/**
 * @ContentEntityType(
 *   id = "og_users_role",
 *   label = @Translation("OG users role"),
 *   module = "og",
 *   base_table = "og_users_roles",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 * )
 */
class OgUsersRole extends ContentEntityBase implements ContentEntityInterface {

  /**
   * @var User
   *
   * The user object.
   */
  protected $uid;

  /**
   * @var OgRole
   *
   * The role entity.
   */
  protected $rid;

  /**
   * @param mixed $rid
   *
   * @return $this
   */
  public function setRid($rid) {
    $this->set('rid', $rid);

    return $this;
  }

  /**
   * @return mixed
   */
  public function getRid() {
    return $this->get('rid')->target_id;
  }

  /**
   * @param mixed $uid
   *
   * @return $this
   */
  public function setUid($uid) {
    $this->set('uid', $uid);

    return $this;
  }

  /**
   * @return mixed
   */
  public function getUid() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = array();

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The unique identifier'))
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setRequired(TRUE)
      ->setDescription(t("The user's role object."))
      ->setSetting('target_type', 'user');

    $fields['rid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('OG role'))
      ->setRequired(TRUE)
      ->setDescription(t('The OG role entity.'))
      ->setSetting('target_type', 'og_role');

    return $fields;
  }
}
