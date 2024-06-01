<?php

namespace Drupal\Tests\localgov_microsites_group_webform\Functional;

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

  protected $domains;
  protected $webforms;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createMicrositeGroups([], 2);
    $this->createMicrositeGroupsDomains($this->groups);
    $this->domains[1] = $this->getDomainFromGroup($this->groups[1]);
    $this->domains[2] = $this->getDomainFromGroup($this->groups[2]);

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
    $this->assertSession()->pageTextContains($this->webforms[2]->localgov_submission_confirm->value);


  }

}
