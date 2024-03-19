<?php

namespace Drupal\Tests\localgov_microsites_group\Kernel;

use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\localgov_microsites_group\Form\DomainGroupSettingsForm;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * \Drupal\localgov_microsites_group\Form\DomainGroupSettingsForm::access
 *
 * @group localgov_microsites_group
 */
class SettingsFormAccessTest extends GroupKernelTestBase {

  /**
   * The group type we will use to test access on.
   *
   * @var \Drupal\group\Entity\GroupType
   */
  protected $groupType;

  /**
   * The group we will use to test access on.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'domain',
    'localgov_microsites_group',
    'path_alias',
    'replicate',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['user']);
    $this->groupType = $this->createGroupType(['id' => 'foo', 'creator_membership' => FALSE]);
    $this->group = $this->createGroup(['type' => 'foo']);
  }

  public function testFormAccess() {
    $form = new DomainGroupSettingsForm($this->container->get('plugin.manager.domain_group_settings'));

    // Non-member.
    $this->assertFalse($form->access($this->group, $this->getCurrentUser())->isAllowed());

    // Member.
    $this->group->addMember($this->getCurrentUser());
    $this->assertFalse($form->access($this->group, $this->getCurrentUser())->isAllowed());

    // Permission to one plugin.
    $this->createGroupRole([
      'group_type' => $this->groupType->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => [
        'administer group domain settings',
      ],
    ]);

    $this->assertTrue($form->access($this->group, $this->getCurrentUser())->isAllowed());

    // Admin.
    $admin = $this->createUser([], ['bypass domain group permissions']);
    $this->assertTrue($form->access($this->group, $admin)->isAllowed());
  }

}
