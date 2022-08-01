<?php

namespace Drupal\localgov_microsites_group\Plugin\DomainGroupSettings;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\domain_group\Plugin\DomainGroupSettingsBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\localgov_microsites_group\GroupPermissionsHelperInterface;

/**
 * Provides simplified permissions / content type enabling per microsite.
 *
 * @DomainGroupSettings(
 *   id = "microsites_content_types",
 *   label = @Translation("Content Types"),
 * )
 */
class ContentTypeSettings extends DomainGroupSettingsBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * Group.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * The group permissions helper.
   *
   * @var \Drupal\localgov_microsites_group\GroupPermissionsHelperInterface
   */
  protected $groupPermissionsHelper;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GroupPermissionsHelperInterface $permissions_helper, ModuleExtensionList $module_extension_list) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupPermissionsHelper = $permissions_helper;
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('localgov_microsites_group.permissions_helper'),
      $container->get('extension.list.module'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(GroupInterface $group, AccountInterface $account) {
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'manage microsite enabled module permissions');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, GroupInterface $group) {
    $hide_descriptions = system_admin_compact_mode();
    $this->group = $group;
    $module_permissions = $this->groupPermissionsHelper->modulesList($group);
    if (empty($module_permissions)) {
      $form['empty'] = [
        '#type' => 'markup',
        '#markup' => $this->t('There are no modules with permissions enabled yet.'),
      ];
      $form['modules'] = [
        '#type' => 'value',
        '#value' => [],
      ];
      return $form;
    }
    $form['modules'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Modules'),
        [
          'data' => $this->t('Enabled'),
          'class' => ['checkbox'],
        ],
      ],
      '#id' => 'modules',
      '#attributes' => ['class' => ['modules']],
      '#sticky' => TRUE,
    ];
    foreach ($module_permissions as $module_name => $status) {
      $module = $this->moduleExtensionList->getExtensionInfo($module_name);
      $form['modules'][$module_name]['module'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="module"><span class="title">{{ title }}</span>{% if description %}<div class="description">{{ description }}</div>{% endif %}</div>',
        '#context' => [
          // @codingStandardsIgnoreLine
          'title' => $this->t($module['name']),
        ],
      ];
      if (!$hide_descriptions) {
        // @codingStandardsIgnoreLine
        $form['modules'][$module_name]['module']['#context']['description'] = $this->t($module['description']);
      }
      $form['modules'][$module_name]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => $status == GroupPermissionsHelperInterface::ENABLED,
        '#disabled' => $status == GroupPermissionsHelperInterface::UNKNOWN,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $disabled = [];
    $enabled = [];
    foreach ($form_state->getValue('modules') as $module => $status) {
      if ($status['enabled'] && $this->groupPermissionsHelper->moduleStatus($module, $this->group) == GroupPermissionsHelperInterface::DISABLED) {
        $this->groupPermissionsHelper->moduleEnable($module, $this->group);
        $enabled[] = $module;
      }
      elseif (!$status['enabled'] && $this->groupPermissionsHelper->moduleStatus($module, $this->group) == GroupPermissionsHelperInterface::ENABLED) {
        $this->groupPermissionsHelper->moduleDisable($module, $this->group);
        $disabled[] = $module;
      }
    }

    if (!empty($enabled)) {
      array_walk($enabled, function (&$module) {
        $info = $this->moduleExtensionList->getExtensionInfo($module);
        // @codingStandardsIgnoreLine
        $module = $this->t($info['name']);
      });
      $this->messenger()->addMessage($this->t('Enabled: %list', ['%list' => implode(',', $enabled)]));
    }
    if (!empty($disabled)) {
      array_walk($disabled, function (&$module) {
        $info = $this->moduleExtensionList->getExtensionInfo($module);
        // @codingStandardsIgnoreLine
        $module = $this->t($info['name']);
      });
      $this->messenger()->addMessage($this->t('Disabled: %list', ['%list' => implode(',', $disabled)]));
    }

  }

}
