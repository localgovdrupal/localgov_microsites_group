<?php

namespace Drupal\Tests\localgov_microsites_directories\Functional;

use Drupal\group\Entity\GroupInterface;
use Drupal\localgov_microsites_group\DomainFromGroupTrait;
use Drupal\node\NodeInterface;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\localgov_microsites_group\Traits\GroupCreationTrait;
use Drupal\Tests\localgov_microsites_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests directory content in a group.
 *
 * @group localgov_microsites_group
 */
class MicrositeDirectoryContentTest extends BrowserTestBase {

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
    'localgov_directories_db',
    'localgov_microsites_directories',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createMicrositeGroups([], 2);
    $this->createMicrositeGroupsDomains($this->groups);
    $this->domain1 = $this->getDomainFromGroup($this->groups[0]);
    $this->domain2 = $this->getDomainFromGroup($this->groups[1]);

    // Create some directory content.
    $this->channel1 = $this->createDirectoryChannel($this->groups[0]);
    $this->pages1 = $this->createDirectoryPages($this->channel1, $this->groups[0], 2);
    $this->channel2 = $this->createDirectoryChannel($this->groups[1]);
    $this->pages2 = $this->createDirectoryPages($this->channel2, $this->groups[1], 2);

    // Index directory content.
    $index = Index::load('localgov_directories_index_default');
    $index->indexItems();
  }

  /**
   * Test content appears on the correct site.
   */
  public function testMicrositeDirectoryContent() {

    // Check content appears on the correct sites.
    $this->drupalGet($this->domain1->getUrl() . $this->channel1->toUrl()->toString());
    $this->assertSession()->pageTextContains($this->pages1[0]->label());
    $this->assertSession()->pageTextContains($this->pages1[1]->label());
    $this->assertSession()->pageTextNotContains($this->pages2[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages2[1]->label());

    $this->drupalGet($this->domain2->getUrl() . $this->channel2->toUrl()->toString());
    $this->assertSession()->pageTextContains($this->pages2[0]->label());
    $this->assertSession()->pageTextContains($this->pages2[1]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[1]->label());
  }

  /**
   * Test directories search.
   */
  public function testMicrositeDirectorySearch() {

    // Search site 1.
    $options = [
      'query' => [
        'search_api_fulltext' => $this->pages1[0]->label(),
      ],
    ];
    $this->drupalGet($this->domain1->getUrl() . $this->channel1->toUrl()->toString(), $options);
    $this->assertSession()->pageTextContains($this->pages1[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[1]->label());
    $this->assertSession()->pageTextNotContains($this->pages2[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages2[1]->label());

    // Search site 2.
    $options = [
      'query' => [
        'search_api_fulltext' => $this->pages2[0]->label(),
      ],
    ];
    $this->drupalGet($this->domain2->getUrl() . $this->channel2->toUrl()->toString(), $options);
    $this->assertSession()->pageTextContains($this->pages2[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages2[1]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[1]->label());
  }

  /**
   * Create directory channel in group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group to create directory in.
   *
   * @return \Drupal\node\NodeInterface
   *   The directory channel.
   */
  protected function createDirectoryChannel(GroupInterface $group) {

    $directory = $this->createNode([
      'type' => 'localgov_directory',
      'title' => $this->randomMachineName(12),
      'localgov_directory_channel_types' => [
        'target_id' => 'localgov_directories_page',
      ],
      'localgov_directory_facets_enable' => [],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $directory->save();
    $group->addRelationship($directory, 'group_node:localgov_directory');

    return $directory;
  }

  /**
   * Create count directory pages in channel and group.
   *
   * @param \Drupal\node\NodeInterface $channel
   *   Directory channel to create pages in.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group to create pages in.
   * @param int $count
   *   Number of directory pages to create.
   *
   * @return array[\Drupal\node\NodeInterface]
   *   Array of directory pages.
   */
  protected function createDirectoryPages(NodeInterface $channel, GroupInterface $group, int $count) {
    $pages = [];

    for ($i = 0; $i < $count; $i++) {
      $page = $this->createNode([
        'type' => 'localgov_directories_page',
        'title' => $this->randomMachineName(12),
        'localgov_directory_channels' => [
          'target_id' => $channel->id(),
        ],
        'status' => NodeInterface::PUBLISHED,
      ]);
      $page->save();
      $group->addRelationship($page, 'group_node:localgov_directories_page');
      $pages[] = $page;
    }

    return $pages;
  }

}
