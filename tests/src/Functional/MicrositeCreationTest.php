<?php

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\Core\Url;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the creation of microsites.
 *
 * Note: For this test to pass, Drupal should be able to resolve the
 * group-a1.localhost domain.  Site also needs to run on the standard HTTP port.
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
    $site_hostname = 'group-a1.' . $this->baseHostname;
    $site_name = $this->randomString();
    $this->drupalGet(Url::fromRoute('localgov_microsites_group.new_domain_group_form', [
      'group_type' => 'microsite',
    ]));
    $this->submitForm([
      'label[0][value]' => $site_name,
    ], 'edit-submit');
    $this->submitForm([
      'hostname' => $site_hostname,
    ], 'edit-submit');
    $domain = \Drupal::entityTypeManager()->getStorage('domain')->loadByHostname($site_hostname);
    $this->assertNotNull($domain);
    $group_ids = \Drupal::entityQuery('group')
      ->condition('label', $site_name, '=')
      ->execute();
    $this->assertNotEmpty($group_ids);
    $group = \Drupal::entityTypeManager()->getStorage('group')->load(reset($group_ids));
    $this->assertSame($site_name, $group->label());
    $this->drupalGet($domain->getUrl());
    $this->assertSession()->pageTextContains($site_name);
  }

}
