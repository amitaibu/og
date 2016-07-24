<?php

namespace Drupal\Tests\og\Kernel\Access;

use Drupal\Component\Utility\Unicode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;

/**
 * Test permission inside a group.
 *
 * @group og
 */
class OgEntityAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'og',
    'entity_test',
  ];

  /**
   * A user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user1;

  /**
   * A user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user2;

  /**
   * A user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user3;

  /**
   * An admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * A group entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group1;

  /**
   * A group entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group2;

  /**
   * The machine name of the group's bundle.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * The OG role that has the permission we check for.
   *
   * @var OgRole
   */
  protected $ogRoleWithPermission;

  /**
   * The OG role that has the permission we check for.
   *
   * @var OgRole
   */
  protected $ogRoleWithPermission2;

  /**
   * The OG role that doesn't have the permission we check for.
   *
   * @var OgRole
   */
  protected $ogRoleWithoutPermission;

  /**
   * The OG role that doesn't have the permission we check for.
   *
   * @var OgRole
   */
  protected $ogAdminRole;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installSchema('system', 'sequences');

    $this->groupBundle = Unicode::strtolower($this->randomMachineName());

    // Create users, and make sure user ID 1 isn't used.
    User::create(['name' => $this->randomString()]);

    $group_owner = User::create(['name' => $this->randomString()]);
    $group_owner->save();

    // A group member with the correct role.
    $this->user1 = User::create(['name' => $this->randomString()]);
    $this->user1->save();

    // A group member without the correct role.
    $this->user2 = User::create(['name' => $this->randomString()]);
    $this->user2->save();

    // A non-member.
    $this->user3 = User::create(['name' => $this->randomString()]);
    $this->user3->save();

    // Admin user.
    $this->adminUser = User::create(['name' => $this->randomString()]);
    $this->adminUser->save();

    // Define the group content as group.
    Og::groupManager()->addGroup('entity_test', $this->groupBundle);

    // Create a group and associate with user 1.
    $this->group1 = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $group_owner->id(),
    ]);
    $this->group1->save();

    // Create another group to help test per group/per account permission
    // caching.
    $this->group2 = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $group_owner->id(),
    ]);
    $this->group2->save();

    $this->ogRoleWithPermission = OgRole::create();
    $this->ogRoleWithPermission
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group1->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      // Associate an arbitrary permission with the role.
      ->grantPermission('some_perm')
      ->save();

    $this->ogRoleWithPermission2 = OgRole::create();
    $this->ogRoleWithPermission2
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group1->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      // Associate an arbitrary permission with the role.
      ->grantPermission('some_perm_2')
      ->save();

    $this->ogRoleWithoutPermission = OgRole::create();
    $this->ogRoleWithoutPermission
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group1->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      ->grantPermission($this->randomMachineName())
      ->save();

    $this->ogAdminRole = OgRole::create();
    $this->ogAdminRole
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group1->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      ->setIsAdmin(TRUE)
      ->save();

    /** @var OgMembership $membership */
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->user1)
      ->setGroup($this->group1)
      ->addRole($this->ogRoleWithPermission)
      ->save();

    // Also create a membership to the other group. From this we can verify that
    // permissions are not bled between groups.
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->user1)
      ->setGroup($this->group2)
      ->addRole($this->ogRoleWithPermission2)
      ->save();

    /** @var OgMembership $membership */
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->user2)
      ->setGroup($this->group1)
      ->addRole($this->ogRoleWithoutPermission)
      ->save();

    /** @var OgMembership $membership */
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->adminUser)
      ->setGroup($this->group1)
      ->addRole($this->ogAdminRole)
      ->save();
  }

  /**
   * Test access to an arbitrary permission.
   */
  public function testAccess() {
    $og_access = $this->container->get('og.access');

    // A member user.
    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm', $this->user1)->isAllowed());
    // This user should not have access to 'some_perm_2' as that was only
    // assigned to group 2.
    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm_2', $this->user1)->isForbidden());

    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm', $this->user1)->isAllowed());

    // A member user without the correct role.
    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm', $this->user2)->isForbidden());

    // A non-member user.
    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm', $this->user3)->isForbidden());

    // Group admin user should have access regardless.
    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm', $this->adminUser)->isAllowed());
    $this->assertTrue($og_access->userAccess($this->group1, $this->randomMachineName(), $this->adminUser)->isAllowed());

    // Add membership to user 3.
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->user3)
      ->setGroup($this->group1)
      ->addRole($this->ogRoleWithPermission)
      ->save();

    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm', $this->user3)->isAllowed());
  }

}
