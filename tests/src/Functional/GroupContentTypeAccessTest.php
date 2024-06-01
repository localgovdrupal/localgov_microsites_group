<?php

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\domain\DomainInterface;
use Drupal\localgov_microsites_group\DomainFromGroupTrait;
use Drupal\node\NodeInterface;
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
    'localgov_microsites_directories',
    'localgov_microsites_events',
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
    // Can test the difference between global and group permissions once per
    // domain global access is set.
    $this->controller = $this->createUser();
    $this->controller->addRole('microsites_controller');
    $this->controller->save();
    $this->ownerUser = $this->createUser();
    $this->adminUser1 = $this->createUser();
    $this->adminUser1->addRole('microsites_trusted_editor');
    $this->adminUser1->save();
    $this->adminUser2 = $this->createUser();
    $this->memberUser1 = $this->createUser();
    $this->memberUser1->addRole('microsites_trusted_editor');
    $this->memberUser1->save();
    $this->memberUser2 = $this->createUser();
    $this->otherUser = $this->createUser();
    $this->createMicrositeGroups([
      'uid' => $this->ownerUser->id(),
    ]);
    $this->groups[1]->addMember($this->adminUser1, ['group_roles' => 'microsite-admin']);
    $this->groups[1]->addMember($this->memberUser1);
    $this->groups[2]->addMember($this->adminUser1, ['group_roles' => 'microsite-admin']);
    $this->groups[2]->addMember($this->adminUser2, ['group_roles' => 'microsite-admin']);
    $this->groups[2]->addMember($this->memberUser2);

    $this->createMicrositeGroupsDomains($this->groups);
  }

  /**
   * Test access to disabled module content types across users and domains.
   *
   * Check with different users, and different domains, confirming caching
   * and checking any unintential cross domain effects.
   */
  public function testUsersDomainsAdminContentTypeAccess() {

    // Setup
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
      ],
    ];
    $group1 = $this->groups[1];
    $group2 = $this->groups[2];
    $group1_domain = $this->getDomainFromGroup($group1);
    $group2_domain = $this->getDomainFromGroup($group2);
    assert($group1_domain instanceof DomainInterface);
    assert($group2_domain instanceof DomainInterface);

    // Check admin paths.
    // Group 2: Admin user.
    $this->micrositeDomainLogin($group2_domain, $this->adminUser1);
    foreach ($modules as $module_name => $module_info) {
      // Group 1: Admin user.
      $this->micrositeDomainLogin($group1_domain, $this->adminUser1);

      $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id() . '/nodes');
      $this->assertSession()->statusCodeEquals(200);

      // Confirm that adminUser1 can create and manage events on group1.
      foreach ($modules as $check_content) {
        foreach ($check_content['content_types'] as $check_content_type) {
          $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id() . '/content/create/group_node%3A' . $check_content_type);
          $this->assertSession()->statusCodeEquals($check_content['status']);
          // @phpstan-ignore-next-line
          #$this->drupalGet($group1_domain->getUrl() . '/node/add/' . $check_content_type);
          // @phpstan-ignore-next-line
          #$this->assertSession()->statusCodeEquals($check_content['status']);
        }
      }

      // Disable current module.
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
          // @phpstan-ignore-next-line
          #$this->drupalGet($group1_domain->getUrl() . '/node/add/' . $check_content_type);
          // @phpstan-ignore-next-line
          #$this->assertSession()->statusCodeEquals($check_content['status']);
        }
      }

      // Check with the same admin user no effect on Group 2.
      foreach ($modules as $check_content) {
        foreach ($check_content['content_types'] as $check_content_type) {
          $this->drupalGet($group2_domain->getUrl() . '/group/' . $group2->id() . '/content/create/group_node%3A' . $check_content_type);
          $this->assertSession()->statusCodeEquals(200);
          // @phpstan-ignore-next-line
          #$this->drupalGet($group2_domain->getUrl() . '/node/add/' . $check_content_type);
          // @phpstan-ignore-next-line
          #$this->assertSession()->statusCodeEquals(200);
        }
      }

      $this->micrositeDomainLogout($group1_domain);
      $this->micrositeDomainLogin($group1_domain, $this->memberUser1);
      // Confirm the new permissions.
      foreach ($modules as $check_content) {
        foreach ($check_content['content_types'] as $check_content_type) {
          $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id() . '/content/create/group_node%3A' . $check_content_type);
          $this->assertSession()->statusCodeEquals($check_content['status']);
          // @phpstan-ignore-next-line
          #$this->drupalGet($group1_domain->getUrl() . '/node/add/' . $check_content_type);
          // @phpstan-ignore-next-line
          #$this->assertSession()->statusCodeEquals($check_content['status']);
        }
      }
      $this->micrositeDomainLogout($group1_domain);
    }
  }

  /**
   * Test access to content in disabled content types.
   */
  public function testContentContentTypeAccess() {
    $group1 = $this->groups[1];
    $group1_domain = $this->getDomainFromGroup($group1);
    assert($group1_domain instanceof DomainInterface);

    // Check a directory and an event post.
    $this->micrositeDomainLogin($group1_domain, $this->adminUser1);
    $directory = $this->createNode([
      'type' => 'localgov_directory',
      'title' => $this->randomMachineName(12),
      'localgov_directory_channel_types' => [
        'target_id' => 'localgov_directories_page',
      ],
      'localgov_directory_facets_enable' => [],
      'status' => NodeInterface::PUBLISHED,
      'uid' => $this->adminUser1->id(),
    ]);
    $directory->save();
    $group1->addRelationship($directory, 'group_node:localgov_directory');
    $event = $this->createNode([
      'type' => 'localgov_event',
      'title' => $this->randomMachineName(12),
      'localgov_event_date' => [
        'value' => gmdate(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, time() + 3600),
        'end_value' => gmdate(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, time() + 7200),
        'rrule' => NULL,
        'timezone' => 'Europe/London',
      ],
      'status' => NodeInterface::PUBLISHED,
      'uid' => $this->adminUser1->id(),
    ]);
    $event->save();
    $group1->addRelationship($event, 'group_node:localgov_event');

    $this->container
      ->get('localgov_microsites_group.content_type_helper')
      ->moduleDisable('localgov_microsites_events', $group1);
    $this->container
      ->get('localgov_microsites_group.content_type_helper')
      ->moduleDisable('localgov_microsites_directories', $group1);

    // No access.
    // Group 1: Admin user.
    $this->drupalGet($group1_domain->getUrl() . $directory->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($group1_domain->getUrl() . $directory->toUrl('edit-form')->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($group1_domain->getUrl() . $event->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($group1_domain->getUrl() . $event->toUrl('edit-form')->toString());
    $this->assertSession()->statusCodeEquals(403);
    // Anon
    $this->micrositeDomainLogout($group1_domain);
    $this->drupalGet($group1_domain->getUrl() . $directory->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($group1_domain->getUrl() . $event->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);

    // Enable one for access.
    $this->container
      ->get('localgov_microsites_group.content_type_helper')
      ->moduleEnable('localgov_microsites_directories', $group1);

    // Access to directories not events.
    $this->drupalGet($group1_domain->getUrl() . $directory->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($group1_domain->getUrl() . $event->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    // Group 1: Admin user.
    $this->micrositeDomainLogin($group1_domain, $this->adminUser1);
    $this->drupalGet($group1_domain->getUrl() . $directory->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($group1_domain->getUrl() . $directory->toUrl('edit-form')->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($group1_domain->getUrl() . $event->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($group1_domain->getUrl() . $event->toUrl('edit-form')->toString());
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Single test to check content types for all modules.
   */
  public function testAllModules() {
    $group1 = $this->groups[1];
    $group1_domain = $this->getDomainFromGroup($group1);
    assert($group1_domain instanceof DomainInterface);

    // Enable modules not already enabled for other tests.
    \Drupal::service('module_installer')->install([
      'localgov_microsites_group_term_ui',
      'localgov_microsites_group_webform',
      'localgov_microsites_news',
    ]);

    // Create some group content.

    // All modules start disabled.
    // Shared paths will be enabled by the first module, so not tested against
    // the second.
    $modules = [
      'localgov_microsites_blogs' => [
        'paths' => [
          '/content/create/group_node%3Alocalgov_blog_post',
          '/content/create/group_node%3Alocalgov_blog_channel',
          '/taxonomy/localgov_topic/add',
          // https://github.com/localgovdrupal/localgov_microsites_group/issues/472
        ],
        'status' => 403,
      ],
      'localgov_microsites_directories' => [
        'paths' => [
          '/content/create/group_node%3Alocalgov_directory',
          '/content/create/group_node%3Alocalgov_directories_page',
          '/content/create/group_node%3Alocalgov_directories_venue',
          '/content/create/group_node%3Alocalgov_directory_promo_page',
          '/directory-facets',
          '/directory-facets/type/add',
        ],
        'status' => 403,
      ],
      'localgov_microsites_events' => [
        'paths' => [
          '/content/create/group_node%3Alocalgov_event',
          // The listings pages (without /add) are available even when there is
          // no access.
          // https://github.com/localgovdrupal/localgov_microsites_group/issues/475
          '/taxonomy/localgov_event_price/add',
          '/taxonomy/localgov_event_category/add',
          '/taxonomy/localgov_event_locality/add',
        ],
        'status' => 403,
      ],
      'localgov_microsites_group_webform' => [
        'paths' => [
          '/content/create/group_node%3Alocalgov_webform',
          // More webform testing following
          // https://github.com/localgovdrupal/localgov_microsites_group/issues/473
        ],
        'status' => 403,
      ],
      'localgov_microsites_news' => [
        'paths' => [
          '/content/create/group_node%3Alocalgov_newsroom',
          '/content/create/group_node%3Alocalgov_news_article',
          // Topic enabled by blogs first.
        ],
        'status' => 403,
      ],
    ];

    // Run as admin user.
    $this->micrositeDomainLogin($group1_domain, $this->adminUser1);
    // Start with all disabled.
    foreach ($modules as $module_name => $module_info) {
      // Disable current module.
      $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id() . '/domain-settings');
      $page = $this->getSession()->getPage();
      $events_button = $page->findButton($module_name);
      $this->assertEquals('Disable', $events_button->getValue());
      $events_button->click();
      $modules[$module_name]['status'] = 403;
    }

    $next_module = reset($modules);
    while (TRUE) {
      // Check present permissions.
      foreach ($modules as $check) {
        foreach ($check['paths'] as $path) {
          $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id() . $path);
          $this->assertSession()->statusCodeEquals($check['status']);
        }
      }

      // Enable next module.
      if (!$next_module) {
        break;
      }
      $module_name = key($modules);
      $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id() . '/domain-settings');
      $page = $this->getSession()->getPage();
      $events_button = $page->findButton($module_name);
      $this->assertEquals('Enable', $events_button->getValue());
      $events_button->click();
      $modules[$module_name]['status'] = 200;

      $next_module = next($modules);
    }
  }

}
