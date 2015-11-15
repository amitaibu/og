<?php

/**
 * @file
 * Contains Drupal\Tests\og\Kernel\Entity\SelectionHandlerTest.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Og;
use Drupal\user\Entity\User;

/**
 * Tests entity reference selection plugins.
 *
 * @group og
 */
class SelectionHandlerTest extends KernelTestBase {

  /**
   * The selection handler.
   *
   * @var \Drupal\og\Plugin\EntityReferenceSelection\OgSelection.
   */
  protected $selectionHandler;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user', 'field', 'entity_reference', 'node', 'og'];

  /**
   * @var User
   */
  protected $user1;

  /**
   * @var User
   */
  protected $user2;

  /**
   * @var string
   *
   * The machine name of the group node type.
   */
  protected $groupBundle;

  /**
   * @var string
   *
   * The machine name of the group content node type.
   */
  protected $groupContentBundle;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add membership and config schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('system', 'sequences');

    // Setting up variables.
    $this->groupBundle = Unicode::strtolower($this->randomMachineName());
    $this->groupContentBundle = Unicode::strtolower($this->randomMachineName());

    // Create a group.
    NodeType::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
    ])->save();

    // Create a group content type.
    NodeType::create([
      'type' => $this->groupContentBundle,
      'name' => $this->randomString(),
    ])->save();

    // Define the group content as group.
    Og::groupManager()->addGroup('node', $this->groupBundle);

    // Add og audience field to group content.
    Og::CreateField(OG_AUDIENCE_FIELD, 'node', $this->groupContentBundle);

    // Get the storage of the field.
    $this->selectionHandler = Og::getSelectionHandler('node', $this->groupContentBundle, OG_AUDIENCE_FIELD);

    // Create two users.
    $this->user1 = User::create(['name' => $this->randomString()]);
    $this->user1->save();

    $this->user2 = User::create(['name' => $this->randomString()]);
    $this->user2->save();
  }

  /**
   * Testing the OG manager selection handler.
   *
   * We need to verify that the manager selection handler will use the default
   * selection manager of the entity which the audience field referencing to.
   *
   * i.e: When the field referencing to node, we need verify we got the default
   * node selection handler.
   */
  public function testSelectionHandler() {
    $this->assertEquals(get_class($this->selectionHandler->getSelectionHandler()), 'Drupal\node\Plugin\EntityReferenceSelection\NodeSelection');
    $this->assertEquals($this->selectionHandler->getConfiguration('handler'), 'default:node');
    $this->assertEquals($this->selectionHandler->getConfiguration('target_type'), 'node');
  }

  /**
   * Testing OG selection handler results.
   *
   * We need to verify that each user get the groups he own in the normal widget
   * and the other users group's in the other groups widget and vice versa.
   */
  public function testSelectionHandlerResults() {
    $user1_groups = $this->createGroups(2, $this->user1);
    $user2_groups = $this->createGroups(2, $this->user2);

    // Checking that the user get the groups he mange.
    $groups = $this->selectionHandler->setAccount($this->user1)->getReferenceableEntities();
    $this->assertEquals($user1_groups, array_keys($groups[$this->groupBundle]));

    $groups = $this->selectionHandler->setAccount($this->user2)->getReferenceableEntities();
    $this->assertEquals($user2_groups, array_keys($groups[$this->groupBundle]));

    // Check the other groups.

    $this->selectionHandler = Og::getSelectionHandler('node', $this->groupContentBundle, OG_AUDIENCE_FIELD, ['handler_settings' => ['field_mode' => 'admin']]);

    $groups = $this->selectionHandler->setAccount($this->user1)->getReferenceableEntities();
    $this->assertEquals($user2_groups, array_keys($groups[$this->groupBundle]));

    $groups = $this->selectionHandler->setAccount($this->user2)->getReferenceableEntities();
    $this->assertEquals($user1_groups, array_keys($groups[$this->groupBundle]));
  }

  /**
   * Creating groups for a given user.
   *
   * @param $amount
   *   The number of groups to create.
   * @param User $user
   *   The user object which own the groups.
   * @param Bool $return_ids
   *   Determine if the method will return the IDs or the group objects.
   *
   * @return ContentEntityBase[]
   */
  private function createGroups($amount, User $user, $return_ids = TRUE) {
    $groups = [];

    for ($i = 0; $i <= $amount; $i++) {
      $group = Node::create([
        'title' => $this->randomString(),
        'uid' => $user->id(),
        'type' => $this->groupBundle,
      ]);
      $group->save();

      $groups[] = $return_ids == 'label' ? $group->id() : $group;
    }

    return $groups;
  }

}
