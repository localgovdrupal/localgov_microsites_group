<?php

namespace Drupal\Tests\localgov_microsites_events\Functional;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\localgov_microsites_group\DomainFromGroupTrait;
use Drupal\node\NodeInterface;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\domain_group\Traits\GroupCreationTrait;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests events content in a group.
 *
 * @group localgov_microsites_events
 */
class MicrositeEventContentTest extends BrowserTestBase {

  use GroupCreationTrait;
  use InitializeGroupsTrait;
  use NodeCreationTrait;
  use DomainFromGroupTrait;

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
    $this->domain1 = $this->getDomainFromGroup($this->group1);
    $this->domain2 = $this->getDomainFromGroup($this->group2);

    // Create some content.
    $this->pages1 = $this->createEvents($this->group1, 2);
    $this->pages2 = $this->createEvents($this->group2, 2);

    // Index events.
    $index = Index::load('localgov_events');
    $index->indexItems();
  }

  /**
   * Test content appears on the correct site.
   */
  public function testMicrositeEventsContent() {

    $this->drupalGet($this->domain1->getUrl() . $this->pages1[0]->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->pages1[0]->label());
    $this->drupalGet($this->domain1->getUrl() . $this->pages1[1]->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->pages1[1]->label());

    $this->drupalGet($this->domain1->getUrl() . $this->pages2[0]->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextNotContains($this->pages2[0]->label());
    $this->drupalGet($this->domain1->getUrl() . $this->pages2[1]->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextNotContains($this->pages2[1]->label());

    $this->drupalGet($this->domain2->getUrl() . $this->pages2[0]->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->pages2[0]->label());
    $this->drupalGet($this->domain2->getUrl() . $this->pages2[1]->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->pages2[1]->label());

    $this->drupalGet($this->domain2->getUrl() . $this->pages1[0]->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextNotContains($this->pages1[0]->label());
    $this->drupalGet($this->domain2->getUrl() . $this->pages1[1]->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextNotContains($this->pages1[1]->label());
  }

  /**
   * Test events view.
   */
  public function testMicrositeEventsView() {

    // Check content appears on the correct sites.
    $this->drupalGet($this->domain1->getUrl() . '/events');
    $this->assertSession()->pageTextContains($this->pages1[0]->label());
    $this->assertSession()->pageTextContains($this->pages1[1]->label());
    $this->assertSession()->pageTextNotContains($this->pages2[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages2[1]->label());

    $this->drupalGet($this->domain2->getUrl() . '/events');
    $this->assertSession()->pageTextContains($this->pages2[0]->label());
    $this->assertSession()->pageTextContains($this->pages2[1]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[1]->label());
  }

  /**
   * Test events search.
   */
  public function testMicrositeEventsSearch() {

    // Search site 1.
    $options = [
      'query' => [
        'search' => $this->pages1[0]->label(),
      ],
    ];
    $this->drupalGet($this->domain1->getUrl() . '/events/search', $options);
    $this->assertSession()->pageTextContains($this->pages1[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[1]->label());
    $this->assertSession()->pageTextNotContains($this->pages2[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages2[1]->label());

    // Search site 2.
    $options = [
      'query' => [
        'search' => $this->pages2[0]->label(),
      ],
    ];
    $this->drupalGet($this->domain2->getUrl() . '/events/search', $options);
    $this->assertSession()->pageTextContains($this->pages2[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages2[1]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[1]->label());
  }

  /**
   * Create count events in group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group to create pages in.
   * @param int $count
   *   Number of events to create.
   *
   * @return array[\Drupal\node\NodeInterface]
   *   Array of events.
   */
  protected function createEvents(GroupInterface $group, int $count) {
    $pages = [];

    $now = time();
    for ($i = 0; $i < $count; $i++) {
      $page = $this->createNode([
        'type' => 'localgov_event',
        'title' => $this->randomMachineName(12),
        'localgov_event_date' => [
          'value' => gmdate(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $now + $i * 3600),
          'end_value' => gmdate(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $now + $i * 7200),
          'rrule' => NULL,
          'timezone' => 'Europe/London',
        ],
        'status' => NodeInterface::PUBLISHED,
      ]);
      $page->save();
      $group->addRelationship($page, 'group_node:localgov_event');
      $pages[] = $page;
    }

    return $pages;
  }

}
