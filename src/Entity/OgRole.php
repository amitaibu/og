<?php

/**
 * Contain the OG role entity definition. This will be a content entity.
 */
namespace Drupal\og\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\og\Og;

/**
 * @ContentEntityType(
 *   id = "og_role",
 *   label = @Translation("OG role"),
 *   module = "og",
 *   base_table = "og_role",
 *   entity_keys = {
 *     "id" = "rid",
 *     "label" = "name"
 *   },
 * )
 */
class OgRole extends ContentEntityBase {

  /**
   * @param mixed $gid
   *
   * @return $this
   */
  public function setGid($gid) {
    $this->set('gid', $gid);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getGid() {
    return $this->get('gid')->value;
  }

  /**
   * @param mixed $groupBundle
   *
   * @return $this
   */
  public function setGroupBundle($groupBundle) {
    $this->set('group_bundle', $groupBundle);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getGroupBundle() {
    return $this->get('groupBundle')->value;
  }

  /**
   * @param mixed $groupType
   *
   * @return $this
   */
  public function setGroupType($groupType) {
    $this->set('group_type', $groupType);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getGroupType() {
    return $this->get('group_type');
  }

  /**
   * @param mixed $name
   *
   * @return $this
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getName() {
    return $this->get('name')->value;
  }

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
    return $this->get('rid')->value;
  }

  /**
   * @var Integer
   *
   * The identifier.
   */
  protected $rid;

  /**
   * @var Integer
   *
   * The group ID.
   */
  protected $gid;

  /**
   * @var String
   *
   * The group group's entity type.
   */
  protected $groupType;

  /**
   * @var String
   *
   * The group's bundle name.
   */
  protected $groupBundle;

  /**
   * @var String
   *
   * Unique role name per group.
   */
  protected $name;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = array();

    $fields['rid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Role ID'))
      ->setDescription(t('Primary Key: Unique role ID.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Unique role name per group.'));

    // todo: set gid, group_type and group_bundle as null.
//    <aspilicious_home>     $fields['pass'] = BaseFieldDefinition::create('password')
//      <aspilicious_home>       ->setLabel(t('Password'))
//    <aspilicious_home>       ->setDescription(t('The password of this user (hashed).'))
//    <aspilicious_home>       ->addConstraint('ProtectedUserField');

    $fields['gid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Group ID'))
      ->setDescription(t("The group's unique ID."))
      ->setRequired(FALSE);

    $fields['group_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Group type'))
      ->setDescription(t("The group's entity type."))
      ->setRequired(FALSE);

    $fields['group_bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Group bundle'))
      ->setDescription(t("The group's bundle name."))
      ->setRequired(FALSE);

    return $fields;
  }

  /**
   * Set group info from object.
   *
   * @param ContentEntityInterface $entity
   *   The entity object.
   *
   * @return $this
   */
  public function setGroupEntity(ContentEntityInterface $entity) {
    $this
      ->setGid($entity->id())
      ->setGroupType($entity->getEntityTypeId())
      ->setGroupBundle($entity->bundle());

    return $this;
  }

  /**
   * Query role IDS.
   *
   * @param $name
   *   The name of the role.
   * @param Array $fields
   *   Additional fields to the query.
   *
   * @return array
   *   List of role IDs.
   */
  public static function queryRoleIds($name, $fields = []) {
    return Og::permissionQueryConstructor('og_role', 'name', $name, $fields);
  }

  /**
   * Loading OG role by name.
   *
   * @param $name
   *   The name of the role.
   * @param Array $fields
   *   Additional fields to the query.
   *
   * @return OgRole|OgRole[]
   *   List of role IDs.
   */
  public static function loadByName($name, $fields = []) {
    return Og::permissionObjectLoader('og_role', 'name', $name, $fields);
  }

}
