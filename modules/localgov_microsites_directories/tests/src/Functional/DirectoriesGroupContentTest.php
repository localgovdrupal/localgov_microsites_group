<?php

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\Core\Url;
use Drupal\domain\DomainInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\domain_group\Traits\GroupCreationTrait;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests directory content in a group.
 *
 * @group localgov_microsites_group
 */
class DirectoriesGroupContentTest extends BrowserTestBase {

  use GroupCreationTrait;
  use InitializeGroupsTrait;
  use NodeCreationTrait;

  /**
   * Will be removed when issue #3204455 on Domain Site Settings gets merged.
   * See https://www.drupal.org/project/domain_site_settings/issues/3204455.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'localgov_microsites_directories',
  ];

  /**
   * Regular authenticated User for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create test user.
    $this->testUser = $this->drupalCreateUser([
      'access group overview',
    ]);

    // Set base hostname.
    $this->setBaseHostname();

    // Create some microsites.
    $this->group1 = $this->createGroup([
      'label' => 'group-a1',
      'type' => 'microsite'
    ]);
    $this->group2 = $this->createGroup([
      'label' => 'group-a2',
      'type' => 'microsite'
    ]);
    $this->allTestGroups = [
      $this->group1,
      $this->group2,
    ];
    $this->initializeTestGroupsDomains();
    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    $this->domain1 = $domain_storage->load('group_' . $this->group1->id());
    $this->domain2 = $domain_storage->load('group_' . $this->group2->id());

    // Create some directory content.
    $this->channel1 = $this->createDirectoryChannel($this->group1);
    $this->pages1 = $this->createDirectoryPages($this->channel1, $this->group1, 2);
    $this->channel2 = $this->createDirectoryChannel($this->group2);
    $this->pages2 = $this->createDirectoryPages($this->channel2, $this->group2, 2);

    // Index directory content.
    $index = Index::load('localgov_directories_index_default');
    $index->indexItems();
  }

  /**
   * Test content appears on the correct sites.
   */
  public function testDirectoriesContent() {

    // Check content appears on the correct sites.
    $this->drupalGet($this->domain1->getUrl() . $this->channel1->toUrl()
        ->toString());
    $this->assertSession()->pageTextContains($this->pages1[0]->label());
    $this->assertSession()->pageTextContains($this->pages1[1]->label());
    $this->assertSession()->pageTextNotContains($this->pages2[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages2[1]->label());

    $this->drupalGet($this->domain2->getUrl() . $this->channel2->toUrl()
        ->toString());
    $this->assertSession()->pageTextContains($this->pages2[0]->label());
    $this->assertSession()->pageTextContains($this->pages2[1]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[1]->label());
  }

  /**
   * Test directories search.
   */
  public function testDirectoriesSearch() {

    $this->drupalPlaceBlock('localgov_directories_channel_search_block');

    // Check searches only return correct content.
    $this->drupalGet($this->domain1->getUrl() . $this->channel1->toUrl()->toString());
    print_r($this->getSession()->getPage()->getHtml());
    $this->submitForm([
      'search_api_fulltext' => $this->pages1[0]->label(),
    ], 'Apply');
    $this->assertSession()->pageTextContains($this->pages1[0]->label());
    $this->assertSession()->pageTextnotContains($this->pages1[1]->label());
    $this->assertSession()->pageTextNotContains($this->pages2[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages2[1]->label());

    $this->drupalGet($this->domain2->getUrl() . $this->channel2->toUrl()->toString());
    $this->submitForm([
      'search_api_fulltext' => $this->pages2[0]->label(),
    ], 'Apply');
    $this->assertSession()->pageTextContains($this->pages2[0]->label());
    $this->assertSession()->pageTextnotContains($this->pages2[1]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[1]->label());
  }

  /**
   * Create directory channel in group.
   *
   * @param $group \Drupal\group\Entity\GroupInterface
   *   Group to create directory in.
   *
   * @return \Drupal\node\NodeInterface
   *   The directory channel.
   */
  protected function createDirectoryChannel($group) {

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
    $group->addContent($directory, 'group_node:localgov_directory');

    return $directory;
  }

  /**
   * Create count directory pages in channel and group.
   *
   * @param $channel \Drupal\node\NodeInterface
   *   Directory channel to create pages in.
   * @param $group \Drupal\group\Entity\GroupInterface
   *   Group to create pages in.
   * @param $count integer
   *   Number of directory pages to create.
   *
   * @return array[\Drupal\node\NodeInterface]
   *   Array of directory pages.
   */
  protected function createDirectoryPages($channel, $group, $count) {
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
      $group->addContent($page, 'group_node:localgov_directories_page');
      $pages[] = $page;
    }

    return $pages;
  }




}
