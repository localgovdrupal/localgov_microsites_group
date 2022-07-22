<?php

namespace Drupal\Tests\localgov_microsites_group_term_ui\Functional;

use Drupal\Tests\BrowserTestBase;

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
    'localgov_microsites_group_term_ui_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_actions_block');
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
    $this->assertSession()->pageTextContains('Taxonomies');
    $this->clickLink('Taxonomies');
    $this->assertSession()->pageTextContains('Tags');
    $this->clickLink('Tags');
    $this->assertSession()->pageTextContains('No terms available.');
    $this->clickLink('Add term');
    $term_name = $this->randomString();
    $this->submitForm([
      'name[0][value]' => $term_name,
    ], 'Save and go to list');
    $this->assertSession()->pageTextContains('Created new term ' . $term_name);
    $this->clickLink('Edit', 1);
    $new_name = $this->randomString();
    $this->submitForm([
      'name[0][value]' => $new_name,
    ], 'Save');
    $this->assertSession()->pageTextContains('Updated term ' . $new_name);
    $this->clickLink('Delete');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('Deleted term ' . $new_name);
    $this->assertSession()->pageTextContains('No terms available.');
  }

}
