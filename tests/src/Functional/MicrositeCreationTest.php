<?php

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupType;
use Drupal\Tests\domain_group\Traits\GroupCreationTrait;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the creation of microsites.
 *
 * @group localgov_microsites_group
 */
class MicrositeCreationTest extends BrowserTestBase {

  use InitializeGroupsTrait;

  /**
   * Will be removed when issue #3204455 on Domain Site Settings gets merged.
   *
   * See https://www.drupal.org/project/domain_site_settings/issues/3204455.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   * phpcs:disable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'localgov_microsites';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_microsites_group',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $default_domain = \Drupal::entityTypeManager()->getStorage('domain')->loadDefaultDomain();
    $this->baseHostname = $default_domain->getHostname();
  }

  /**
   * Test site creation.
   */
  public function testMicrositeCreationForm() {

    // Login as Microsite Controller.
    $user = $this->drupalCreateUser();
    $user->addRole('microsites_controller');
    $user->save();
    $this->drupalLogin($user);

    // Create a site.
    $site1_hostname = 'group-a1.' . $this->baseHostname;
    $site1_name = $this->randomString();
    $this->drupalGet(Url::fromRoute('localgov_microsites_group.new_domain_group_form', [
      'group_type' => 'microsite',
    ]));
    $this->submitForm([
      'label[0][value]' => $site1_name,
    ], 'edit-submit');
    $this->submitForm([
      'hostname' => $site1_hostname,
    ], 'edit-submit');
    $this->assertNotNull(\Drupal::entityTypeManager()->getStorage('domain')->loadByHostname($site1_hostname));
    $group_ids = \Drupal::entityQuery('group')
      ->condition('label', $site1_name, '=')
      ->execute();
    $this->assertNotEmpty($group_ids);
    $group = \Drupal::entityTypeManager()->getStorage('group')->load(reset($group_ids));
    $this->assertSame($site1_name, $group->label());
    $this->drupalGet($site1_hostname);
    $this->assertSession()->pageTextContains($site1_name);
  }
}
