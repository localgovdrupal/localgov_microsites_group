<?php

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\domain\DomainInterface;
use Drupal\localgov_microsites_group\DomainFromGroupTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\localgov_microsites_group\Traits\GroupCreationTrait;
use Drupal\Tests\localgov_microsites_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the group and group content access.
 *
 * @group localgov_microsites_group
 */
class GroupContentTypeAccessTest extends BrowserTestBase {

  use UserCreationTrait;
  use InitializeGroupsTrait;
  use AssertMailTrait;
  use LoginOutTrait;
  use GroupCreationTrait, DomainFromGroupTrait {
    GroupCreationTrait::getEntityTypeManager insteadof DomainFromGroupTrait;
  }

  /**
   * Will be removed when issue #3204455 on Domain Site Settings gets merged.
   *
   * See https://www.drupal.org/project/domain_site_settings/issues/3204455.
   *
   * @var bool
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   * phpcs:disable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'group',
    'domain',
    'localgov_microsites_group',
    'localgov_microsites_blogs',
    'localgov_microsites_events',
    'localgov_microsites_directories',
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
  protected $otherUser;

  /**
   * User administrator of group 1.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->ownerUser = $this->createUser();
    // Can test the difference between global and group permissions once per domain
    // global access is set.
    #$this->adminUser1 = $this->createUser(['create localgov_event content']);
    #$this->adminUser2 = $this->createUser(['create localgov_event content']);
    $this->adminUser1 = $this->createUser();
    $this->adminUser2 = $this->createUser();
    $this->memberUser1 = $this->createUser();
    $this->memberUser2 = $this->createUser();
    $this->otherUser = $this->createUser();
    $this->createMicrositeGroups([
      'uid' => $this->ownerUser->id(),
    ]);
    $this->groups[1]->addMember($this->adminUser1, ['group_roles' => 'microsite-admin']);
    $this->groups[2]->addMember($this->adminUser2, ['group_roles' => 'microsite-admin']);

    $this->createMicrositeGroupsDomains($this->groups);
  }

  /**
   * Test access to manage content.
   */
  public function testGroupEventAccess() {

    $modules = [
      'localgov_microsites_events' => [
        'content_types' => ['localgov_event'],
        'status' => 200,
      ],
      'localgov_microsites_directories' => [
        'content_types' => [
          'localgov_directory',
          'localgov_directories_page',
          'localgov_directories_venue',
          'localgov_directory_promo_page',
        ],
        'status' => 200,
      ],
      'localgov_microsites_blogs' => [
        'content_types' => [
          'localgov_blog_channel',
          'localgov_blog_post',
        ],
        'status' => 200,
      ]
    ];

    $group1 = $this->groups[1];
    $group2 = $this->groups[2];
    $group1_domain = $this->getDomainFromGroup($group1);
    $group2_domain = $this->getDomainFromGroup($group2);
    assert($group1_domain instanceof DomainInterface);
    assert($group2_domain instanceof DomainInterface);

    // Group 1: Admin user.
    $this->micrositeDomainLogin($group1_domain, $this->adminUser1);
    // Group 2: Admin user.
    $this->micrositeDomainLogin($group2_domain, $this->adminUser2);

    foreach ($modules as $module_name => $module_info) {
      $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id() . '/nodes');
      $this->assertSession()->statusCodeEquals(200);

      // Confirm that adminUser1 can create and manage events on group1.
      foreach ($modules as $check_content) {
        foreach ($check_content['content_types'] as $check_content_type) {
          $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id() . '/content/create/group_node%3A' . $check_content_type);
          $this->assertSession()->statusCodeEquals($check_content['status']);
          #$this->drupalGet($group1_domain->getUrl() . '/node/add/' . $check_content_type);
          #$this->assertSession()->statusCodeEquals($check_content['status']);
        }
      }

      // Disable events.
      $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id() . '/domain-settings');
      $page = $this->getSession()->getPage();
      $events_button = $page->findButton($module_name);
      $this->assertEquals('Disable', $events_button->getValue());
      $events_button->click();
      $modules[$module_name]['status'] = $module_info['status'] = 403;

      // Confirm the new permissions.
      foreach ($modules as $check_content) {
        foreach ($check_content['content_types'] as $check_content_type) {
          $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id() . '/content/create/group_node%3A' . $check_content_type);
          $this->assertSession()->statusCodeEquals($check_content['status']);
          #$this->drupalGet($group1_domain->getUrl() . '/node/add/' . $check_content_type);
          #$this->assertSession()->statusCodeEquals($check_content['status']);
        }
      }

      // @todo when we can logout, login as member and check same.

      // Confirm no effects to other domain.
      foreach ($modules as $check_content) {
        foreach ($check_content['content_types'] as $check_content_type) {
          $this->drupalGet($group2_domain->getUrl() . '/group/' . $group2->id() . '/content/create/group_node%3A' . $check_content_type);
          $this->assertSession()->statusCodeEquals(200);
          #$this->drupalGet($group2_domain->getUrl() . '/node/add/' . $check_content_type);
          #$this->assertSession()->statusCodeEquals(200);
        }
      }
    }
  }

}
