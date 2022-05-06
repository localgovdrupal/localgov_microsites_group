<?php

namespace Drupal\localgov_microsites_group\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\domain_group\Form\DomainGroupSettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\domain_group\Plugin\DomainGroupSettingsManager;
use Drupal\group\Entity\GroupInterface;
use Drupal\localgov_microsites_group\GroupDefaultContentInterface;

/**
 * Domain Group Settings Form.
 */
class DomainGroupConfigAdd extends FormBase {

  /**
   * The DomainGroupSettingsManager service.
   *
   * @var \Drupal\domain_group\Plugin\DomainGroupSettingsManager
   */
  protected $pluginManagerDomainGroupSettings;

  /**
   * The private store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Default content generator.
   *
   * @var \Drupal\localgov_microsites_group\GroupDefaultContentInterface
   */
  protected $defaultContent;

  /**
   * Constructs a new DomainGroupSettingsForm object.
   */
  public function __construct(DomainGroupSettingsManager $plugin_manager_domain_group_settings, PrivateTempStoreFactory $private_temp_store_factory, EntityTypeManagerInterface $entity_type_manager, GroupDefaultContentInterface $default_content) {
    $this->pluginManagerDomainGroupSettings = $plugin_manager_domain_group_settings;
    $this->privateTempStoreFactory = $private_temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->defaultContent = $default_content;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.domain_group_settings'),
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('localgov_microsites_group.default_content')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_group_config_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, GroupInterface $group = NULL, $extras = []) {
    $form_state->set('group', $group);
    $form['tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('@group_label - Domain Settings', ['@group_label' => $group->label()]),
    ];

    // @todo Make plugin and visible fields configurable.
    // https://github.com/localgovdrupal/localgov_microsites_group/issues/15
    $plugin = $this->pluginManagerDomainGroupSettings->createInstance('domain_group_site_settings');
    $form += $plugin->buildConfigurationForm([], $form_state, $group);
    $form['site_front_page']['#type'] = 'value';
    $form['error_page']['#open'] = FALSE;

    if (!empty($extras['group_wizard'])) {
      $store = $this->privateTempStoreFactory->get($extras['group_wizard_id']);
      $store_id = $extras['store_id'];
      $form_state->set('group_wizard_id', $extras['group_wizard_id']);
      $form_state->set('store_id', $store_id);

      $replace = [
        '@group' => $group->label(),
      ];

      $actions['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save @group and domain', $replace),
        '#submit' => ['::complete'],
      ];

      $actions['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#submit' => ['::back'],
        '#limit_validation_errors' => [],
      ];

      $form['actions'] = $actions;
    }
    else {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->pluginManagerDomainGroupSettings->getAll() as $plugin) {
      $plugin->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->pluginManagerDomainGroupSettings->getAll() as $plugin) {
      $plugin->submitConfigurationForm($form, $form_state);
    }
    $this->messenger()->addStatus('Changes saved');
  }

  /**
   * Complete the wizard. Save all the entities.
   */
  public function complete(array &$form, FormStateInterface $form_state) {
    $wizard_id = $form_state->get('group_wizard_id');
    $store = $this->privateTempStoreFactory->get($wizard_id);
    $store_id = $form_state->get('store_id');
    $group = $store->get("$store_id:entity");
    $group_bundle = $this->entityTypeManager
      ->getStorage('group_type')
      ->load($group->bundle());
    $membership = $store->get("$store_id:membership");

    // Replicate the form from step 1 and call the save method.
    $form_object = $this->entityTypeManager->getFormObject($group->getEntityTypeId(), 'new_domain');
    $form_object->setEntity($group);
    $form_object->save($form, $form_state);

    if ($membership) {
      $form_object = $this->entityTypeManager->getFormObject($membership->getEntityTypeId(), 'new_domain');
      $membership->set('gid', $group->id());
      $form_object->setEntity($membership);
      $form_object->save($form, $form_state);
    }
    elseif ($group_bundle->creatorGetsMembership()) {
      $values = ['group_roles' => $group_bundle->getCreatorRoleIds()];
      $group->addMember($group->getOwner(), $values);
    }

    $content = $this->defaultContent->generate($group);
    $front_page = $content['node']['localgov_page'][0];
    $form_state->setValue('site_front_page', $front_page->toUrl()->toString());

    $form_state->set('group', $group);
    $plugin = $this->pluginManagerDomainGroupSettings->createInstance('domain_group_site_settings');
    $plugin->submitConfigurationForm($form, $form_state);

    // We also clear the temp store so we can start fresh next time around.
    $store->delete("$store_id:step");
    $store->delete("$store_id:entity");
    $store->delete("$store_id:membership");
  }

  /**
   * Goes back to step 1 of the creation wizard.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\group\Entity\Controller\GroupContentController::createForm()
   */
  public function back(array &$form, FormStateInterface $form_state) {

    $store = $this->privateTempStoreFactory->get($form_state->get('group_wizard_id'));
    $store_id = $form_state->get('store_id');
    $group = $store->get("$store_id:entity");
    $group_bundle = $this->entityTypeManager
      ->getStorage('group_type')
      ->load($group->bundle());
    $membership_step =  $group_bundle->creatorMustCompleteMembership();
    $store->set("$store_id:step", $membership_step ? 2 : 1);

    // Disable any URL-based redirect when going back to the previous step.
    $request = $this->getRequest();
    $form_state->setRedirect('<current>', [], ['query' => $request->query->all()]);
    $request->query->remove('destination');
  }

}
