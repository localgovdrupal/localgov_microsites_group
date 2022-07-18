<?php

namespace Drupal\Tests\localgov_microsites_group_term_ui\Functional;

use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\domain_group\Traits\GroupCreationTrait;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\group\Functional\GroupBrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests managing group terms.
 *
 * @group localgov_microsites_group_term_ui
 */
class ManageGroupTermsTest extends BrowserTestBase {

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
    'block',
    'localgov_microsites_group_term_ui_test'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');

    // Create a group.
    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->group = $entity_type_manager->getStorage('group')->create([
      'type' => 'default',
      'label' => $this->randomMachineName(),
    ]);
    $this->group->enforceIsNew();
    $this->group->save();
  }

  /**
   * Test UI to manage terms as group content.
   */
  public function testGroupTermUi() {

    $this->drupalLogin($this->drupalCreateUser([], 'user1', TRUE));
    $this->drupalGet($this->group->toUrl()->toString() . '/edit');
    //print_r($this->getSession()->getPage()->getHtml());
    $this->assertSession()->pageTextContains('Taxonomies');
    $this->clickLink('Taxonomies');
    $this->assertSession()->pageTextContains('Tags');


  }

}
