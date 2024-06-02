<?php

namespace Drupal\Tests\localgov_microsites_group_webform\Functional;

use Drupal\Core\Url;
use Drupal\Tests\localgov_microsites_group\Functional\LoginOutTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\localgov_microsites_group\DomainFromGroupTrait;
use Drupal\node\NodeInterface;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\localgov_microsites_group\Traits\GroupCreationTrait;
use Drupal\Tests\localgov_microsites_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests webforms attached to a node in a group.
 *
 * @group localgov_microsites_group
 */
class MicrositeWebformAccessTest extends BrowserTestBase {

  use InitializeGroupsTrait;
  use NodeCreationTrait;
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
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   * phpcs:disable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_microsites_group_webform',
  ];

  protected $domains = [];
  protected $webforms = [];
  protected $adminUser = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createMicrositeGroups(['uid' => 1], 2);
    $this->createMicrositeGroupsDomains($this->groups);
    $this->domains[1] = $this->getDomainFromGroup($this->groups[1]);
    $this->domains[2] = $this->getDomainFromGroup($this->groups[2]);

    $this->adminUser[1] = $this->createUser();
    $this->adminUser[2] = $this->createUser();
    $this->groups[1]->addMember($this->adminUser[1], ['group_roles' => 'microsite-admin']);
    $this->groups[2]->addMember($this->adminUser[2], ['group_roles' => 'microsite-admin']);

    $this->webforms[1] = $this->createNode([
      'type' => 'localgov_webform',
      'title' => $this->randomMachineName(12),
      'localgov_submission_confirm' => $this->randomMachineName(),
      'localgov_submission_email' => 'abc@example.com',
      'localgov_webform' => [
        'target_id' => 'microsite_contact',
        'default_data' => '',
        'status' => 'open',
        'open' => '',
        'close' => '',
      ],
      'status' => NodeInterface::PUBLISHED,
      'uid' => $this->adminUser[1]->id(),
    ]);
    $this->webforms[1]->save();
    $this->webforms[2] = $this->createNode([
      'type' => 'localgov_webform',
      'title' => $this->randomMachineName(12),
      'localgov_submission_confirm' => $this->randomMachineName(),
      'localgov_submission_email' => 'abc@example.com',
      'localgov_webform' => [
        'target_id' => 'microsite_contact',
        'default_data' => '',
        'status' => 'open',
        'open' => '',
        'close' => '',
      ],
      'status' => NodeInterface::PUBLISHED,
      'uid' => $this->adminUser[2]->id(),
    ]);
    $this->webforms[2]->save();
    $this->groups[1]->addRelationship($this->webforms[1], 'group_node:localgov_webform');
    $this->groups[2]->addRelationship($this->webforms[2], 'group_node:localgov_webform');
  }

  /**
   * Test access and submission.
   */
  public function testMicrositeNodeWebform() {
    $submissions = [];

    // Check form 1 only on domain 1 and make a submission.
    $this->drupalGet($this->domains[1]->getUrl() . $this->webforms[1]->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->webforms[1]->label());
    $submissions[1] = [
      'full_name' => $this->randomString(),
      'email_address' => $this->randomMachineName() . '@example.com',
      'message' => $this->randomString(),
    ];
    $this->submitForm($submissions[1], 'Submit');
    $this->assertSession()->pageTextContains($this->webforms[1]->localgov_submission_confirm->value);
    $this->drupalGet($this->domains[1]->getUrl() . $this->webforms[2]->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);

    // Check form 2 only on domain 2 and make a submission.
    $this->drupalGet($this->domains[2]->getUrl() . $this->webforms[1]->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($this->domains[2]->getUrl() . $this->webforms[2]->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->webforms[2]->label());
    $submissions[2] = [
      'full_name' => $this->randomString(),
      'email_address' => $this->randomMachineName() . '@example.com',
      'message' => $this->randomString(),
    ];
    $this->submitForm($submissions[2], 'Submit');
    // Check anon does not have access to the submission.
    $this->assertSession()->pageTextContains($this->webforms[2]->localgov_submission_confirm->value);
    $this->drupalGet($this->domains[2]->getUrl() . Url::fromRoute('entity.node.webform.results_submissions', [
      'node' => $this->webforms[2]->id(),
    ])->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($this->domains[2]->getUrl() . Url::fromRoute('entity.node.webform.user.submission', [
      'node' => $this->webforms[2]->id(),
      'webform_submission' => 2,
    ])->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($this->domains[2]->getUrl() . Url::fromRoute('entity.node.webform.user.submission.edit', [
      'node' => $this->webforms[2]->id(),
      'webform_submission' => 2,
    ])->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($this->domains[2]->getUrl() . Url::fromRoute('entity.node.webform_submission.canonical', [
      'node' => $this->webforms[2]->id(),
      'webform_submission' => 2,
    ])->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($this->domains[2]->getUrl() . Url::fromRoute('entity.node.webform_submission.edit_form', [
      'node' => $this->webforms[2]->id(),
      'webform_submission' => 2,
    ])->toString());
    $this->assertSession()->statusCodeEquals(403);

    // Check admin 1 has access to domain 1 submission.
    $this->micrositeDomainLogin($this->domains[1], $this->adminUser[1]);
    $this->drupalGet($this->domains[1]->getUrl() . Url::fromRoute('entity.node.webform.results_submissions', [
      'node' => $this->webforms[1]->id(),
    ])->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($this->domains[1]->getUrl() . Url::fromRoute('entity.node.webform.user.submission', [
      'node' => $this->webforms[1]->id(),
      'webform_submission' => 1,
    ])->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($this->domains[1]->getUrl() . Url::fromRoute('entity.node.webform.user.submission.edit', [
      'node' => $this->webforms[1]->id(),
      'webform_submission' => 1,
    ])->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($this->domains[1]->getUrl() . Url::fromRoute('entity.node.webform_submission.canonical', [
      'node' => $this->webforms[1]->id(),
      'webform_submission' => 1,
    ])->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($this->domains[1]->getUrl() . Url::fromRoute('entity.node.webform_submission.edit_form', [
      'node' => $this->webforms[1]->id(),
      'webform_submission' => 1,
    ])->toString());
    $this->assertSession()->statusCodeEquals(200);

    // Check admin 1 does not have access to domain 2 submission.
    $this->drupalGet($this->domains[2]->getUrl() . Url::fromRoute('entity.node.webform.results_submissions', [
      'node' => $this->webforms[2]->id(),
    ])->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($this->domains[2]->getUrl() . Url::fromRoute('entity.node.webform.user.submission', [
      'node' => $this->webforms[2]->id(),
      'webform_submission' => 2,
    ])->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($this->domains[2]->getUrl() . Url::fromRoute('entity.node.webform.user.submission.edit', [
      'node' => $this->webforms[2]->id(),
      'webform_submission' => 2,
    ])->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($this->domains[2]->getUrl() . Url::fromRoute('entity.node.webform_submission.canonical', [
      'node' => $this->webforms[2]->id(),
      'webform_submission' => 2,
    ])->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($this->domains[2]->getUrl() . Url::fromRoute('entity.node.webform_submission.edit_form', [
      'node' => $this->webforms[2]->id(),
      'webform_submission' => 2,
    ])->toString());
    $this->assertSession()->statusCodeEquals(403);
  }

}
