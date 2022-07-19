<?php

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\Tests\domain_group\Traits\GroupCreationTrait;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the group and group content access.
 *
 * @group domain_group
 */
class GroupInvitationAccessTest extends BrowserTestBase {

  use GroupCreationTrait;
  use InitializeGroupsTrait;

  /**
   * Will be removed when issue #3204455 on Domain Site Settings gets merged.
   *
   * See https://www.drupal.org/project/domain_site_settings/issues/3204455.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'block',
    'group',
    'gnode',
    'domain',
    'domain_site_settings',
    'domain_group',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'localgov_base';

  /**
   * Regular authenticated User for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * User administrator of group 1.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupAdmin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create test user.
    $this->testUser = $this->drupalCreateUser([
      'access content',
      'access group overview',
    ]);

    $this->groupAdmin = $this->drupalCreateUser([
      'access content',
      'access group overview',
    ]);

    // Setup the group types and test groups from the InitializeGroupsTrait.
    $this->initializeTestGroups(['uid' => $this->groupAdmin->id()]);
    $this->initializeTestGroupsDomains();
    $this->initializeTestGroupContent();

    // Allow anonymous to view groups of type A.
    $this->groupTypeA->getAnonymousRole()->grantPermissions([
      'view group',
    ])->save();

    // Allow outsider to view group content article of type A.
    $this->groupTypeA->getOutsiderRole()->grantPermissions([
      'view group',
      'view group_node:article entity',
    ])->save();

    // Allow member to view, edit, delete group content article of type A.
    $this->groupTypeA->getMemberRole()->grantPermissions([
      'view group',
      'access content overview',
      'administer group',
      'administer members',
    ])->save();

  }

  /**
   * Test content access when unique group access is enabled.
   */
  public function testInvitationPermissions() {
    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    $ga1_domain = $domain_storage->load('group_' . $this->groupA1->id());
    // $ga2_domain = $domain_storage->load('group_' . $this->groupA2->id());
    $this->drupalLogin($this->groupAdmin);
    $this->drupalGet($ga1_domain->getPath());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($ga1_domain->getPath() . '/group/' . $this->groupA1->id()) . '/invitations';
    $this->assertSession()->statusCodeEquals(200);
  }

}
