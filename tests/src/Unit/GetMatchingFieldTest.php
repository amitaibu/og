<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\og\OgGroupAudienceHelper;

/**
 * Tests the OgGroupAudienceHelper::getMatchingField method.
 *
 * @group og
 *
 * @coversDefaultClass \Drupal\og\OgGroupAudienceHelper
 */
class GetMatchingFieldTest extends UnitTestCase {


  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityType;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The bundle ID.
   *
   * @var string
   */
  protected $bundleId;

  /**
   * The group content entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entity;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $field_name = 'test_field';

    $this->entityTypeId = $this->randomMachineName();
    $this->bundleId = $this->randomMachineName();

    $this->entityType = $this->prophesize(EntityTypeInterface::class);
    $this
      ->entityType
      ->isSubclassOf(FieldableEntityInterface::class)
      ->willReturn(TRUE);

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this
      ->entityTypeManager
      ->getDefinition($this->entityTypeId)
      ->willReturn($this->entityType);

    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);

    $this->entity = $this->prophesize(ContentEntityInterface::class);
    $this
      ->entity
      ->getEntityTypeId()
      ->willReturn($this->entityTypeId);

    $this
      ->entity
      ->bundle()
      ->willReturn($this->bundleId);

    // $field_storage_definition_prophecy = $this->prophesize(FieldStorageDefinitionInterface::class);
    //    $field_storage_definition_prophecy
    //      ->getCardinality()
    //      ->willReturn(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    //      ->shouldBeCalled();
    //
    //    $field_definition_prophecy = $this->prophesize(FieldDefinitionInterface::class);
    //    $field_definition_prophecy
    //      ->getFieldStorageDefinition()
    //      ->willReturn($field_storage_definition_prophecy->reveal())
    //      ->shouldBeCalled();
    //
    //    $field_definition_prophecy
    //      ->getType()
    //      ->willReturn('og_membership_reference')
    //      ->shouldBeCalled();
    //
    //    $field_definition_prophecy
    //      ->getSetting('target_type')
    //      ->willReturn($this->entityTypeId)
    //      ->shouldBeCalled();
    //
    //    $this
    //      ->entity
    //      ->getFieldDefinition($field_name)
    //      ->willReturn($field_definition_prophecy->reveal());
    //
    //    $this
    //      ->entity
    //      ->bundle()
    //      ->shouldBeCalled();
    //
    //    $this
    //      ->entity
    //      ->getEntityTypeId()
    //      ->shouldBeCalled();
    //
    //    // If the cardinality is unlimited getting a count of the field items is
    //    // never expected, so just check it's not called.
    //    $this
    //      ->entity
    //      ->get($field_name)
    //      ->shouldNotBeCalled();
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager->reveal());
    $container->set('entity_field.manager', $this->entityFieldManager->reveal());

    \Drupal::setContainer($container);

  }

  /**
   * Tests no existing audience fields.
   *
   * @covers ::getMatchingField
   */
  public function testNoFields() {
    $this
      ->entityFieldManager
      ->getFieldDefinitions($this->entityTypeId, $this->bundleId)
      // Return empty field definitions.
      ->willReturn([]);

    $this->assertNull(OgGroupAudienceHelper::getMatchingField($this->entity->reveal(), $this->entityTypeId, $this->bundleId));
  }

}
