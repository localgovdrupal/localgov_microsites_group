<?php

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\Core\Url;
use Drupal\domain\DomainInterface;
use Drupal\localgov_microsites_group\DomainFromGroupTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\localgov_microsites_group\Traits\GroupCreationTrait;
use Drupal\Tests\localgov_microsites_group\Traits\InitializeGroupsTrait;

/**
 * Tests logging into microsite and control site.
 *
 * @group localgov_microsites_group
 */
class LoginTest extends BrowserTestBase {

  use InitializeGroupsTrait;
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
    'group',
    'domain',
    'localgov_microsites_group',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Regular authenticated User for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    // Create test user.
    $this->testUser = $this->drupalCreateUser([
      'access group overview',
    ]);

    // Setup the group types and test groups from the InitializeGroupsTrait.
    $this->createMicrositeGroups(['uid' => $this->testUser->id()]);
    $this->createMicrositeGroupsDomains($this->groups);
  }

  /**
   * Test login redirects.
   */
  public function testLoginRedirect() {
    $this->drupalLogin($this->testUser);
    $this->assertSession()->addressEquals(Url::fromRoute('system.admin'));
    // Standard tests have a shared cookie. So we would already be logged in.
    // Need to logout.
    $this->drupalLogout();

    // Can't use drupalLogin as we want to do it on the form with the microsite
    // domain.
    // @todo move this into a trait, probably on domain_group.
    $ga1_domain = $this->getDomainFromGroup($this->groups[1]);
    assert($ga1_domain instanceof DomainInterface);
    $this->drupalGet($ga1_domain->getUrl() . Url::fromRoute('user.login')->toString());
    $this->submitForm([
      'name' => $this->testUser->getAccountName(),
      'pass' => $this->testUser->passRaw,
    ], 'Log in');
    $this->assertSession()->addressEquals(Url::fromRoute('entity.group.canonical', ['group' => 1]));
  }

  /**
   * Isolated test to check login / out trait.
   */
  public function testLoginOutTrait() {
    // Create test user.
    $testUser2 = $this->drupalCreateUser([
      'access group overview',
    ]);

    $domain1 = $this->getDomainFromGroup($this->groups[1]);
    $domain2 = $this->getDomainFromGroup($this->groups[2]);
    $domain3 = $this->getDomainFromGroup($this->groups[3]);

    $this->micrositeDomainLogin($domain1, $this->testUser);
    $this->micrositeDomainLogin($domain2, $this->testUser);
    $this->micrositeDomainLogin($domain3, $testUser2);

    $user_page = Url::fromRoute('user.page')->toString();
    $this->drupalGet($domain1->getUrl() . $user_page);
    $this->assertSession()->addressEquals('/user/' . $this->testUser->id());
    $this->drupalGet($domain2->getUrl() . $user_page);
    $this->assertSession()->addressEquals('/user/' . $this->testUser->id());
    $this->drupalGet($domain3->getUrl() . $user_page);
    $this->assertSession()->addressEquals('/user/' . $testUser2->id());

    $this->micrositeDomainLogout($domain1);
    $this->assertTrue($this->micrositeDomainIsLoggedIn($domain2, $this->testUser));
  }

}
