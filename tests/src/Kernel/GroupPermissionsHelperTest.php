<?php

namespace Drupal\Tests\localgov_microsites_group\Kernel;

use Drupal\group\Entity\Group;
use Drupal\group_permissions\Entity\GroupPermission;
use Drupal\group_permissions\Entity\GroupPermissionInterface;
use Drupal\localgov_microsites_group\GroupPermissionsHelperInterface;
use Drupal\localgov_microsites_group\RolesHelper;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\user\Entity\User;

/**
 * @covers \Drupal\localgov_microsites_group\GroupPermisisonsHelper
 *
 * @group localgov_microsites_group
 */
class GroupPermissionsHelperTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'localgov_microsites_events',
    'localgov_microsites_group',
    'localgov_paragraphs_layout',
    'domain',
    'domain_group',
    'entity_reference_revisions',
    'field_formatter_class',
    'field_group',
    'file',
    'image',
    'media',
    'media_library',
    'gnode',
    'group_content_menu',
    'group_permissions',
    'layout_discovery',
    'layout_paragraphs',
    'node',
    'paragraphs',
    'replicate',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // You really don't want to install localgov_page in a Kernel test!
    NodeType::create([
      'type' => 'localgov_page',
      'name' => 'Page',
    ])->save();
    NodeType::create([
      'type' => 'example_page',
      'name' => 'Example',
    ])->save();
    NodeType::create([
      'type' => 'localgov_event',
      'name' => 'Event',
    ])->save();

    $this->installEntitySchema('file');
    $this->installEntitySchema('group_content_menu');
    $this->installEntitySchema('group_permission');
    $this->installEntitySchema('user');
    $this->installSchema('node', 'node_access');
    $this->installSchema('file', 'file_usage');

    $this->installConfig([
      'gnode',
      'localgov_paragraphs_layout',
      'localgov_microsites_group',
      'localgov_microsites_events',
    ]);

    localgov_microsites_group_modules_installed(['localgov_microsites_events']);
  }

  /**
   * Test generating default content.
   */
  public function testGetPermissions() {
    $group = Group::create(['type' => 'microsite']);
    $group->save();
    $permissions_helper = $this->container->get('localgov_microsites_group.permissions_helper');
    $permission_entity = $permissions_helper->getGroupPermissions($group);
    assert($permission_entity instanceof GroupPermissionInterface);
    $permissions = $permission_entity->getPermissions();
    $this->assertEquals([
      'view group_node:localgov_event entity',
      'view group_node:localgov_page entity',
    ], $permissions['microsite-' . RolesHelper::GROUP_ANONYMOUS_ROLE]);
    $this->assertTrue($group->hasPermission('view group_node:localgov_page entity', User::getAnonymousUser()));
    $permissions['microsite-anonymous'] = [
      'view group_node:localgov_event entity',
    ];
    $permission_entity->setPermissions($permissions);
    $this->assertEmpty($permission_entity->validate());
    $permission_entity->save();
    $this->assertFalse($group->hasPermission('view group_node:localgov_page entity', User::getAnonymousUser()));
  }

  /**
   * Test enable disable module permissions.
   */
  public function testToggleModulePermissions() {
    $group = Group::create(['type' => 'microsite']);
    $group->save();

    $permissions_helper = $this->container->get('localgov_microsites_group.permissions_helper');
    $this->assertEquals(GroupPermissionsHelperInterface::ENABLED, $permissions_helper->moduleStatus('localgov_microsites_events', $group));
    $this->assertTrue($group->hasPermission('view group_node:localgov_event entity', User::getAnonymousUser()));
    $permissions_helper->moduleDisable('localgov_microsites_events', $group);
    $this->assertEquals(GroupPermissionsHelperInterface::DISABLED, $permissions_helper->moduleStatus('localgov_microsites_events', $group));
    $this->assertFalse($group->hasPermission('view group_node:localgov_event entity', User::getAnonymousUser()));
    $permissions_helper->moduleEnable('localgov_microsites_events', $group);
    $this->assertEquals(GroupPermissionsHelperInterface::ENABLED, $permissions_helper->moduleStatus('localgov_microsites_events', $group));
    $this->assertTrue($group->hasPermission('view group_node:localgov_event entity', User::getAnonymousUser()));
  }

  /**
   * Test altered permissions.
   *
   * This is in a seperate test to testToggleModulePermissions because of the
   * way the group permissions entity is cached in GroupPermissionsManager, and
   * hence GroupPermissionsHelper. Better might be investigating a patch for
   * GroupPermissionsManager that, if the cache is really needed, moves it into
   * a static cache value that can be invalidated from outside if needed to
   * change within a call.
   */
  public function testAlteredGroupPermissions() {
    $group = Group::create(['type' => 'microsite']);
    $group->save();

    $permission_entity = GroupPermission::create([
      'gid' => $group->id(),
    ]);
    $permissions = $permission_entity->getPermissions();
    $permissions['microsite-anonymous'] = ['view group_node:localgov_event entity'];
    $permission_entity->setPermissions($permissions);
    $permission_entity->validate();
    $permission_entity->save();
    $permissions_helper = $this->container->get('localgov_microsites_group.permissions_helper');
    $this->assertEquals(GroupPermissionsHelperInterface::UNKNOWN, $permissions_helper->moduleStatus('localgov_microsites_events', $group));
  }

  /**
   * Test list of all available modules.
   */
  public function testModulesList() {
    $group = Group::create(['type' => 'microsite']);
    $group->save();
    $permissions_helper = $this->container->get('localgov_microsites_group.permissions_helper');
    $this->assertEquals(['localgov_microsites_events' => GroupPermissionsHelperInterface::ENABLED], $permissions_helper->modulesList($group));
  }

}
