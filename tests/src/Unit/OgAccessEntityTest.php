<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\OgAccessEntity.
 */

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\og\GroupRepository;
use Drupal\og\OgAccess;

/**
 * @group og
 * @coversDefaultClass \Drupal\og\OgAccess
 */
class OgAccessEntityTest extends OgAccessTestBase  {

  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    if (!defined('OG_STATE_ACTIVE')) {
      define('OG_STATE_ACTIVE', 1);
    }

    $entity_type_id = $this->randomMachineName();
    $bundle = $this->randomMachineName();
    $entity_id = mt_rand(20, 30);

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->getListCacheTags()->willReturn([]);
    $entity_type->id()->willReturn($entity_type_id);

    $this->entity = $this->prophesize(EntityInterface::class);
    $this->entity->id()->willReturn($entity_id);
    $this->entity->bundle()->willReturn($bundle);
    $this->entity->isNew()->willReturn(FALSE);
    $this->entity->getEntityType()->willReturn($entity_type->reveal());
    $this->entity->getEntityTypeId()->willReturn($entity_type_id);

    $this->groupManager->isGroup($entity_type_id, $bundle)->willReturn(FALSE);

    $group_repository = $this->prophesize(GroupRepository::class);
    $group_repository->getEntityGroups($this->entity, [OG_STATE_ACTIVE], NULL)->willReturn([[$this->groupEntity()->reveal()]]);
    $group_repository->getAllGroupAudienceFields($entity_type_id, $bundle, NULL, NULL)->willReturn(['some fields we did not mock']);
    \Drupal::getContainer()->set('og.group_repository', $group_repository->reveal());

    $r = new \ReflectionClass('Drupal\og\Og');
    $reflection_property = $r->getProperty('entityGroupCache');
    $reflection_property->setAccessible(TRUE);
    $reflection_property->setValue(["$entity_type_id:$entity_id:1:" => [[$this->groupEntity()->reveal()]]]);
  }

  /**
   * @coversDefaultmethod ::userAccessEntity
   * @dataProvider operationProvider
   */
  public function testDefaultForbidden($operation) {
    $group_entity = $this->groupEntity();
    $group_entity->isNew()->willReturn(FALSE);
    $user_access = OgAccess::userAccessEntity($operation, $this->entity->reveal(), $this->user->reveal());
    $this->assertTrue($user_access->isForbidden());
  }

  /**
   * @coversDefaultmethod ::userAccessEntity
   * @dataProvider operationProvider
   */
  public function testEntityNew($operation) {
    $group_entity = $this->groupEntity();
    $group_entity->isNew()->willReturn(TRUE);
    $user_access = OgAccess::userAccessEntity($operation, $group_entity->reveal(), $this->user->reveal());
    $this->assertTrue($user_access->isNeutral());
  }

  /**
   * @coversDefaultmethod ::userAccessEntity
   * @dataProvider operationProvider
   */
  public function testGetEntityGroups($operation) {
    $this->user->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION)->willReturn(TRUE);
    $user_entity_access = OgAccess::userAccessEntity($operation, $this->entity->reveal(), $this->user->reveal());
    $this->assertTrue($user_entity_access->isAllowed());
  }

}
