<?php

namespace Drupal\Tests\localgov_microsites_events\Functional;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\domain_group\Traits\GroupCreationTrait;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests access to event view and search.
 *
 * @group localgov_microsites_events
 */
class MicrositeEventViewAccesTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use GroupCreationTrait;
  use InitializeGroupsTrait;
  use NodeCreationTrait;

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
    'localgov_microsites_events',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

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

    // Create some content.
    $this->page1 = $this->createNode([
      'type' => 'localgov_event',
      'title' => $this->randomMachineName(12),
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->page1->save();
    $this->group1->addContent($this->page1, 'group_node:localgov_event');
    $this->createContentType(['type' => 'page']);
    $this->page2 = $this->createNode([
      'type' => 'page',
      'title' => $this->randomMachineName(12),
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->page2->save();

    // Index events.
    $index = Index::load('localgov_events');
    $index->indexItems();
  }

  /**
   * Test events view.
   */
  public function testMicrositeEventsViewAccess() {

    $this->drupalGet($this->domain1->getUrl() .  $this->page1->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($this->domain1->getUrl() . '/events');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($this->domain1->getUrl() . '/events/search');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet($this->domain2->getUrl() .  $this->page2->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($this->domain2->getUrl() . '/events');
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet($this->domain2->getUrl() . '/events/search');
    $this->assertSession()->statusCodeEquals(404);
  }
}
