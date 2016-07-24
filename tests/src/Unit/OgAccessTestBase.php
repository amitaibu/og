<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\og\GroupManager;
use Drupal\og\OgAccess;
use Drupal\og\OgMembershipInterface;
use Drupal\user\EntityOwnerInterface;
use Prophecy\Argument;

/**
 * Base class for tests of the OgAccess class.
 */
class OgAccessTestBase extends UnitTestCase {

  /**
   * The mocked config handler.
   *
   * @var \Drupal\Core\Config\Config|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $config;

  /**
   * A mocked test user.
   *
   * @var \Drupal\user\UserInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $user;

  /**
   * The entity type ID of the test group.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The bundle ID of the test group.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The mocked test group.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $group;

  /**
   * The mocked group manager.
   *
   * @var \Drupal\og\GroupManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupManager;

  /**
   * The OgAccess class, this is the system under test.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->entityTypeId = $this->randomMachineName();
    $this->bundle = $this->randomMachineName();

    $this->groupManager = $this->prophesize(GroupManager::class);
    $this->groupManager->isGroup($this->entityTypeId, $this->bundle)->willReturn(TRUE);

    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens(Argument::any())->willReturn(TRUE);

    // It is expected that any access check will retrieve the settings, because
    // it contains an option to give full access to to the group manager.
    $this->config = $this->addCache($this->prophesize(Config::class));
    $this->config->get('group_manager_full_access')->willReturn(FALSE);

    // Whether or not the user has access to a certain operation depends in part
    // on the 'group_manager_full_access' setting which is stored in config.
    // Since the access is cached, this means that from the point of view from
    // the caching system this access varies by the 'og.settings' config object
    // that contains this setting. It is hence expected that the cacheability
    // metadata is retrieved from the config object so it can be attached to the
    // access result object.
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('og.settings')->willReturn($this->config);

    $this->config->getCacheContexts()->willReturn([]);
    $this->config->getCacheTags()->willReturn([]);
    $this->config->getCacheMaxAge()->willReturn(0);

    // Create a mocked test user.
    $this->user = $this->prophesize(AccountInterface::class);
    $this->user->isAuthenticated()->willReturn(TRUE);
    $this->user->id()->willReturn(2);
    $this->user->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION)->willReturn(FALSE);

    $container = new ContainerBuilder();
    $container->set('og.group.manager', $this->groupManager->reveal());
    $container->set('cache_contexts_manager', $cache_contexts_manager->reveal());
    $container->set('config.factory', $config_factory->reveal());
    $container->set('module_handler', $this->prophesize(ModuleHandlerInterface::class)->reveal());
    // This is for caching purposes only.
    $container->set('current_user', $this->user->reveal());
    \Drupal::setContainer($container);

    $this->group = $this->groupEntity()->reveal();
    $group_type_id = $this->group->getEntityTypeId();

    $entity_id = 20;

    // Mock all dependencies for the system under test.
    $account_proxy = $this->prophesize(AccountProxyInterface::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);

    // Instantiate the system under test.
    $this->ogAccess = new OgAccess($config_factory->reveal(), $account_proxy->reveal(), $module_handler->reveal());

    // Set the Og::cache property values, to skip calculations.
    $values = [];

    $r = new \ReflectionClass('Drupal\og\Og');
    $reflection_property = $r->getProperty('cache');
    $reflection_property->setAccessible(TRUE);

    // Mock the results of Og::getGroupIds().
    $identifier = [
      'Drupal\og\Og::getGroupIds',
      $entity_id,
      NULL,
      NULL,
    ];

    $identifier = implode(':', $identifier);

    $group_ids = [$group_type_id => [$this->group->id()]];
    $values[$identifier] = $group_ids;

    // Mock the results of Og::getMemberships().
    $identifier = [
      'Drupal\og\Og::getMemberships',
      2,
      OgMembershipInterface::STATE_ACTIVE,
      // The field name.
      NULL,
    ];
    $identifier = implode(':', $identifier);

    // The cache is supposed to be holding the OG memberships, however it is not
    // used in the tests, so we just set an empty array.
    $values[$identifier] = [];

    $reflection_property->setValue($values);

    // Set the allowed permissions cache. This simulates that the access results
    // have been retrieved from the database in an earlier pass. This saves us
    // from having to mock all the database interaction.
    $r = new \ReflectionClass('Drupal\og\OgAccess');
    $reflection_property = $r->getProperty('permissionsCache');
    $reflection_property->setAccessible(TRUE);

    $values = [];
    foreach (['pre_alter', 'post_alter'] as $key) {
      $values[$group_type_id][$this->group->id()][2][$key] = ['permissions' => ['update group'], 'is_admin' => FALSE];
    }

    $reflection_property->setValue($this->ogAccess, $values);
  }

  /**
   * Returns a mocked test group.
   *
   * @param bool $is_owner
   *   Whether or not this test group should be owned by the test user which is
   *   used in the test.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ObjectProphecy
   *   The test group.
   */
  protected function groupEntity($is_owner = FALSE) {
    $group_entity = $this->prophesize(EntityInterface::class);
    if ($is_owner) {
      $group_entity->willImplement(EntityOwnerInterface::class);
      // Our test user is hardcoded to have UID 2.
      $group_entity->getOwnerId()->willReturn(2);
    }
    $group_entity->getEntityTypeId()->willReturn($this->entityTypeId);
    $group_entity->bundle()->willReturn($this->bundle);
    $group_entity->id()->willReturn($this->randomMachineName());

    return $this->addCache($group_entity);
  }

  /**
   * Mocks the cache methods.
   */
  protected function addCache($prophecy) {
    $prophecy->getCacheContexts()->willReturn([]);
    $prophecy->getCacheTags()->willReturn([]);
    $prophecy->getCacheMaxAge()->willReturn(0);
    return $prophecy;
  }

  /**
   * Provides permissions to use in access tests.
   *
   * @return array
   *   An array of test permissions.
   */
  public function permissionsProvider() {
    return [
      // In the unit tests we don't really care about the permission name - it
      // can be an arbitrary string; except for
      // OgAccessTest::testUserAccessAdminPermission test which checks for
      // "administer group" permission.
      ['update group'],
      ['administer group'],
    ];
  }

}
