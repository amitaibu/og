<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\GetMatchingFieldTest.
 */

namespace Drupal\Tests\og\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
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
   * @covers ::getMatchingField
   */
  public function testGetMatchingField() {
    $field_name = 'test_field';

    $field_storage_definition_prophecy = $this->prophesize(FieldStorageDefinitionInterface::class);
    $field_storage_definition_prophecy->getCardinality()
      ->willReturn(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->shouldBeCalled();

    $field_definition_prophecy = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition_prophecy->getFieldStorageDefinition()
      ->willReturn($field_storage_definition_prophecy->reveal())
      ->shouldBeCalled();

    $field_definition_prophecy->getType()
      ->willReturn('og_membership_reference')
      ->shouldBeCalled();

    $field_definition_prophecy->getSetting('target_type')
      ->willReturn('entity_test')
      ->shouldBeCalled();


    $entity_prophecy = $this->prophesize(ContentEntityInterface::class);

    $entity_prophecy->getFieldDefinition($field_name)
      ->willReturn($field_definition_prophecy->reveal());

    $entity_prophecy->bundle()
      ->shouldBeCalled();
    $entity_prophecy->getEntityTypeId()
      ->shouldBeCalled();

    // If the cardinality is unlimited getting a count of the field items is
    // never expected, so just check it's not called.
    $entity_prophecy->get($field_name)
      ->shouldNotBeCalled();

    $group_type_id = 'entity_test';
    $group_bundle = 'test1';

    // @todo: This is probably still very wrong.
    $field_definitions = array(
      'id' => BaseFieldDefinition::create('integer'),
      'revision_id' => BaseFieldDefinition::create('integer'),
    );

    $entity_manager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->any())
      ->method('getFieldDefinitions')
      ->with($group_type_id, $group_bundle)
      ->will($this->returnValue($field_definitions));

    $container = new ContainerBuilder();
    $container->set('entity.manager', $entity_manager);
    \Drupal::setContainer($container);

    // @todo: For now, until I get the tests to work we'll do a simple assert.
    // After that we'll add a proper dataProvider.

    $this->assertSame(OgGroupAudienceHelper::getMatchingField($entity_prophecy->reveal(), $group_type_id, $group_bundle), $field_name);
  }

}
