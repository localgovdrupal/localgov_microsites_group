<?php

namespace Drupal\Tests\localgov_microsites_group_term_ui\Functional;

use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The default group.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');

    // Create a group.
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->group = $this->entityTypeManager->getStorage('group')->create([
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

  /**
   * Test permissions for managing terms.
   */
  public function testGroupTermPermissions() {

    // Create a term.
    $term = Term::create([
      'name' => $this->randomString(),
      'vid' => 'tags',
    ]);
    $term->save();
    $this->group->addContent($term, 'group_term:tags');

    // Login as a group admin.
    /** @var \Drupal\group\Entity\GroupType $group */
    $group_type = $this->entityTypeManager
      ->getStorage('group_type')
      ->load('default');
    $user = $this->drupalCreateUser([]);
    $this->group->addMember($user);
    $this->drupalLogin($user);

    // Check access to taxonomy management page.
    $taxonomy_url = Url::fromRoute('localgov_microsites_group_term_ui.taxononmy.list',
      [
        'group' => $this->group->id(),
      ]);
    $this->drupalGet($taxonomy_url);
    $this->assertSession()->statusCodeEquals(403);
    $member_role = $group_type->getMemberRole();
    $member_role->grantPermission('access group_term overview')->save();
    $this->drupalGet($taxonomy_url);
    $this->assertSession()->statusCodeEquals(200);

    // Check term management.
    $term_url = Url::fromRoute('view.lgms_group_taxonomy_terms.page',
      [
        'group' => $this->group->id(),
        'vid' => 'tags',
      ]);
    $this->drupalGet($term_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($term->label());
    $this->assertSession()->pageTextNotContains('Add term');
    $this->assertSession()->pageTextNotContains('Edit');
    $this->assertSession()->pageTextNotContains('Delete');

    // Check add permission.
    $term_add_url = Url::fromRoute('localgov_microsites_group_term_ui.taxononmy.add',
      [
        'group' => $this->group->id(),
        'vid' => 'tags',
      ]);
    $this->drupalGet($term_add_url);
    $this->assertSession()->statusCodeEquals(403);
    $member_role->grantPermission('create group_term:tags entity')->save();
    // Group pages have caching issues when changing permissions.
    $this->container->get('cache.render')->invalidateAll();
    $this->drupalGet($term_url);
    $this->assertSession()->pageTextContains('Add term');
    $this->drupalGet($term_add_url);
    $this->assertSession()->statusCodeEquals(200);

    // Check update permission.
    $term_edit_url = Url::fromRoute('entity.taxonomy_term.edit_form',
      [
        'taxonomy_term' => $term->id(),
      ]);
    $this->drupalGet($term_edit_url);
    $this->assertSession()->statusCodeEquals(403);
    $member_role->grantPermission('update any group_term:tags entity')->save();
    $this->container->get('cache.render')->invalidateAll();
    $this->drupalGet($term_url);
    $this->assertSession()->pageTextContains('Edit');
    $this->drupalGet($term_edit_url);
    $this->assertSession()->statusCodeEquals(200);

    // Check delete permission.
    $term_delete_url = Url::fromRoute('entity.taxonomy_term.delete_form',
      [
        'taxonomy_term' => $term->id(),
      ]);
    $this->drupalGet($term_delete_url);
    $this->assertSession()->statusCodeEquals(403);
    $member_role->grantPermission('delete any group_term:tags entity')->save();
    $this->container->get('cache.render')->invalidateAll();
    $this->drupalGet($term_url);
    $this->assertSession()->pageTextContains('Delete');
    $this->drupalGet($term_delete_url);
    $this->assertSession()->statusCodeEquals(200);
  }

}
