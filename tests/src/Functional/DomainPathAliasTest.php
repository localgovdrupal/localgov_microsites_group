<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\localgov_microsites_group\Traits\GroupCreationTrait;
use Drupal\Tests\localgov_microsites_group\Traits\InitializeGroupsTrait;
use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\localgov_microsites_group\DomainFromGroupTrait;

/**
 * Tests setting domain path aliases.
 *
 * @group localgov_microsites_group
 */
class DomainPathAliasTest extends BrowserTestBase {

  use InitializeGroupsTrait;
  use GroupCreationTrait, DomainFromGroupTrait {
    GroupCreationTrait::getEntityTypeManager insteadof DomainFromGroupTrait;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'localgov_microsites_group',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User administrator of group.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a content type with group plugin.
    $this->drupalCreateContentType([
      'type' => 'test_page',
      'name' => 'Test page',
    ]);
    $group_type = $this->getEntityTypeManager()->getStorage('group_type')
      ->load('microsite');
    assert($group_type instanceof GroupType);
    $storage = $this->getEntityTypeManager()->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->save($storage->createFromPlugin($group_type, 'group_node:test_page'));

    // Add pathauto pattern.
    $uuid = \Drupal::service('uuid')->generate();
    $this->getEntityTypeManager()->getStorage('pathauto_pattern')->create([
      'id' => 'test_page',
      'type' => 'canonical_entities:node',
      'pattern' => '[node:title]',
      'selection_criteria' => [
        $uuid => [
          'id' => 'entity_bundle:node',
          'negate' => FALSE,
          'uuid' => $uuid,
          'context_mapping' => [
            'node' => 'node',
          ],
          'bundles' => [
            'test_page' => 'test_page',
          ],
        ],
      ],
    ])->save();

    // Add permissions to microsite admin for nodes of content type.
    $group_role_storage = $this->entityTypeManager->getStorage('group_role');
    $group_role = $group_role_storage->load('microsite-admin');
    $group_role_storage->save($group_role->grantPermissions([
      'create group_node:test_page entity',
      'update any group_node:test_page entity',
      'view group_node:test_page entity',
    ]));

    // Create groups, domains and an admin user.
    $this->adminUser = $this->createUser(['use group_sites admin mode']);
    $this->adminUser->addRole('microsites_trusted_editor');
    $this->createMicrositeGroups([
      'uid' => $this->adminUser->id(),
    ], 2);
    $this->groups[1]->addMember($this->adminUser, ['group_roles' => 'microsite-admin']);
    $this->groups[2]->addMember($this->adminUser, ['group_roles' => 'microsite-admin']);
    $this->createMicrositeGroupsDomains($this->groups);
  }

  /**
   * Test creating and editing domain path aliases.
   */
  public function testDomainPathAlias() {
    $group = $this->groups[1];
    $domain = $this->getDomainFromGroup($group);
    $this->drupalGet($domain->getUrl() . Url::fromRoute('user.login')->getInternalPath());
    $this->submitForm([
      'name' => $this->adminUser->getAccountName(),
      'pass' => $this->adminUser->passRaw,
    ], 'Log in');

    // Check initial path fields.
    $this->drupalGet($domain->getUrl() . 'group/' . $group->id() . '/content/create/group_node%3Atest_page');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('domain_path[group_1][path]');
    $this->assertSession()->fieldExists('domain_path[group_1][pathauto]');
    $this->assertSession()->fieldValueEquals('domain_path[group_1][pathauto]', '1');
    $this->assertSession()->fieldNotExists('domain_path[group_2][path]');

    // Check that it's possible manually add a path alias.
    $this->submitForm([
      'title[0][value]' => 'test-page',
      'domain_path[group_1][path]' => '/test-page',
      'domain_path[group_1][pathauto]' => NULL,
    ], 'Save');
    $this->assertSession()->addressEquals('/test-page');

    // Check that it's possible to edit a path alias.
    $node = $this->drupalGetNodeByTitle('test-page');
    $this->drupalGet($domain->getUrl() . 'node/' . $node->id() . '/edit');
    $this->submitForm([
      'title[0][value]' => 'test-page',
      'domain_path[group_1][path]' => '/test-page2',
      'domain_path[group_1][pathauto]' => NULL,
    ], 'Save');
    $this->assertSession()->addressEquals('/test-page2');

    // Check that pathauto works.
    $this->drupalGet($domain->getUrl() . 'node/' . $node->id() . '/edit');
    $this->submitForm([
      'title[0][value]' => 'test-page3',
      'domain_path[group_1][path]' => '',
      'domain_path[group_1][pathauto]' => 1,
    ], 'Save');
    $this->assertSession()->addressEquals('/test-page3');
  }

}
