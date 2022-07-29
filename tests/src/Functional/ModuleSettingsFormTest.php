<?php

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\group\Entity\Group;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\localgov_microsites_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the content enable/disable on domain settings form.
 *
 * @group localgov_microsites_group
 */
class ModuleSettingsFormTest extends BrowserTestBase {

  use UserCreationTrait;
  use InitializeGroupsTrait;

  /**
   * Domain config can't define a schema for all config the way its implemented.
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
    'domain_site_settings',
    'domain_group',
    'localgov_microsites_group',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Regular authenticated User for tests.
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
    $this->adminUser = $this->createUser();
    $this->memberUser = $this->createUser();
    $this->createMicrositeGroups([
      'uid' => $this->ownerUser->id(),
    ]);
    $this->groups[0]->addMember($this->adminUser, ['group_roles' => 'microsite-admin']);
    $this->groups[0]->addMember($this->memberUser);
    $this->createMicrositeGroupsDomains($this->groups);
  }

  /**
   * Test group domain settings form.
   */
  public function testDomainGroupForm() {
    $group = $this->groups[0];
    // Going to domain group settings form.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('group/' . $group->id() . '/domain-settings');
    $this->assertSession()->pageTextContains($group->label() . ' - Domain Settings');
    $this->assertSession()->pageTextContains('There are no modules with permissions enabled yet.');

    \Drupal::service('module_installer')->install(['localgov_microsites_directories']);
    \Drupal::service('module_installer')->install(['localgov_microsites_events']);

    $this->drupalGet('group/' . $group->id() . '/domain-settings');
    $this->assertSession()->pageTextContains($group->label() . ' - Domain Settings');
    $page = $this->getSession()->getPage();
    $page->hasCheckedField('modules[localgov_microsites_events][enabled]');
    $page->hasCheckedField('modules[localgov_microsites_directories][enabled]');
    $page->uncheckField('modules[localgov_microsites_directories][enabled]');
    $page->pressButton('Submit');

    $page->hasCheckedField('modules[localgov_microsites_events][enabled]');
    $page->hasUncheckedField('modules[localgov_microsites_directories][enabled]');
    $page->checkField('modules[localgov_microsites_directories][enabled]');
    $page->pressButton('Submit');

    $page->hasCheckedField('modules[localgov_microsites_events][enabled]');
    $page->hasCheckedField('modules[localgov_microsites_directories][enabled]');

    $this->drupalLogin($this->memberUser);
    $this->drupalGet('group/' . $group->id() . '/domain-settings');
    $this->assertSession()->statusCodeEquals(403);
  }

}
