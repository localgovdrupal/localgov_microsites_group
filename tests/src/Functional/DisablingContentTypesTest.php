<?php

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\localgov_microsites_group\Traits\GroupCreationTrait;
use Drupal\Tests\localgov_microsites_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\domain\DomainInterface;
use Drupal\localgov_microsites_group\DomainFromGroupTrait;

class DisablingContentTypesTest extends BrowserTestBase {

  use UserCreationTrait;
  use InitializeGroupsTrait;
  use AssertMailTrait;
  use GroupCreationTrait, DomainFromGroupTrait {
    GroupCreationTrait::getEntityTypeManager insteadof DomainFromGroupTrait;
  }

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
   protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'gnode',
    'group',
    'localgov_events',
    'localgov_microsites_group',
  ];

  private AccountInterface $ownerUser;

  private AccountInterface $editorUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createContentType(['type' => 'localgov_event']);

    $this->ownerUser = $this->createUser(['use group_sites admin mode']);
    $this->editorUser = $this->createUser(['use group_sites admin mode']);

    $this->createMicrositeGroups(
      settings: [
        'uid' => $this->ownerUser->id(),
      ],
      count: 1,
    );
    $this->groups[1]->addMember($this->editorUser, ['group_roles' => 'microsite-admin']);

    $this->createMicrositeGroupsDomains($this->groups);
  }

  public function test_a(): void {
    $group = $this->groups[1];

    $domain = $this->getDomainFromGroup($group);
    assert($domain instanceof DomainInterface);

    $this->drupalGet($domain->getUrl() . Url::fromRoute('user.login')->toString());

    $this->submitForm([
      'name' => $this->editorUser->getAccountName(),
      'pass' => $this->editorUser->passRaw,
    ], 'Log in');

    $path = vsprintf('%s/group/%d/content/create/group_node:localgov_event', [
      $domain->getUrl(),
      $group->id(),
    ]);

    // Ensure the page is initially accessible.
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);

    $group->set('lgms_modules_disabled', [
      ['localgov_microsites_events'],
    ]);
    $group->save();

    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(404);
  }

}

