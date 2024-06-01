<?php

namespace Drupal\Tests\localgov_microsites_blogs\Functional;

use Drupal\group\Entity\GroupInterface;
use Drupal\localgov_microsites_group\DomainFromGroupTrait;
use Drupal\node\NodeInterface;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\localgov_microsites_group\Traits\GroupCreationTrait;
use Drupal\Tests\localgov_microsites_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests channel content in a group.
 *
 * @group localgov_microsites_group
 */
class MicrositeBlogsContentTest extends BrowserTestBase {

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
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_microsites_blogs',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createMicrositeGroups([], 2);
    $this->createMicrositeGroupsDomains($this->groups);
    $this->domain1 = $this->getDomainFromGroup($this->groups[1]);
    $this->domain2 = $this->getDomainFromGroup($this->groups[2]);

    // Create some channel content.
    $this->blog_channel1 = $this->createBlogChannel($this->groups[1]);
    $this->post1 = $this->createBlogPosts($this->blog_channel1, $this->groups[1], 2);
    $this->blog_channel2 = $this->createBlogChannel($this->groups[2]);
    $this->post2 = $this->createBlogPosts($this->blog_channel2, $this->groups[2], 2);
  }

  /**
   * Test content appears on the correct site.
   */
  public function testMicrositeblogContent() {

    // Check content appears on the correct sites.
    $this->drupalGet($this->domain1->getUrl() . $this->blog_channel1->toUrl()->toString());
    $this->assertSession()->pageTextContains($this->post1[0]->label());
    $this->assertSession()->pageTextContains($this->post1[1]->label());
    $this->assertSession()->pageTextNotContains($this->post2[0]->label());
    $this->assertSession()->pageTextNotContains($this->post2[1]->label());

    $this->drupalGet($this->domain2->getUrl() . $this->blog_channel2->toUrl()->toString());
    $this->assertSession()->pageTextContains($this->post2[0]->label());
    $this->assertSession()->pageTextContains($this->post2[1]->label());
    $this->assertSession()->pageTextNotContains($this->post1[0]->label());
    $this->assertSession()->pageTextNotContains($this->post1[1]->label());
  }

  /**
   * Create blog channel in group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group to create blog_channel in.
   *
   * @return \Drupal\node\NodeInterface
   *   The blog_channel.
   */
  protected function createBlogChannel(GroupInterface $group) {

    $channel = $this->createNode([
      'type' => 'localgov_blog_channel',
      'title' => $this->randomMachineName(12),
      'status' => NodeInterface::PUBLISHED,
    ]);
    $channel->save();
    $group->addRelationship($channel, 'group_node:localgov_blog_channel');

    return $channel;
  }

  /**
   * Create count blog posts in blog channel and group.
   *
   * @param \Drupal\node\NodeInterface $channel
   *   Blog channel to create posts in.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group to create post in.
   * @param int $count
   *   Number of blog post to create.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Array of blog posts.
   */
  protected function createBlogPosts(NodeInterface $channel, GroupInterface $group, int $count) {
    $posts = [];

    for ($i = 0; $i < $count; $i++) {
      $post = $this->createNode([
        'type' => 'localgov_blog_post',
        'title' => $this->randomMachineName(12),
        'localgov_blog_channel' => [
          'target_id' => $channel->id(),
        ],
        'status' => NodeInterface::PUBLISHED,
      ]);
      $post->save();
      $group->addRelationship($post, 'group_node:localgov_blog_post');
      $posts[] = $post;
    }

    return $posts;
  }

}
