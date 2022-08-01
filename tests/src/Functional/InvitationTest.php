<?php

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\domain_group\Traits\GroupCreationTrait;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;

/**
 * Tests Invitations.
 *
 * @group localgov_microsites_group
 */
class InvitationTest extends BrowserTestBase {

  use GroupCreationTrait;
  use InitializeGroupsTrait;
  use NodeCreationTrait;

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
   * Test breadcrumbs in the Standard profile.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'localgov_base';

  /**
   * A user with permission to bypass content access checks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'localgov_microsites_group',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'bypass group access',
      'access group overview',
      'administer group',
    ]);
    // $this->nodeStorage =
    // $this->container->get('entity_type.manager')->getStorage('node');
    // Set base hostname.
    $this->setBaseHostname();

    // Create some microsites.
    $this->group1 = $this->createGroup([
      'label' => 'group-a1',
      'type' => 'microsite',
    ]);
    $this->group2 = $this->createGroup([
      'label' => 'group-a2',
      'type' => 'microsite',
    ]);
    $this->allTestGroups = [
      $this->group1,
      $this->group2,
    ];
    $this->initializeTestGroupsDomains();
    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    $this->domain1 = $domain_storage->load('group_' . $this->group1->id());
    $this->domain2 = $domain_storage->load('group_' . $this->group2->id());
  }

  /**
   * Verifies Group invite has been installed in the microsite.
   */
  public function testInvites() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/group/content/manage/microsite-group_invitation');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('This form allows you to configure the Group Invitation plugin for the Microsite group type.');
  }

}
