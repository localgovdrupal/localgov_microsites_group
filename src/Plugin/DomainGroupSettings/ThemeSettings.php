<?php

namespace Drupal\localgov_microsites_group\Plugin\DomainGroupSettings;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\domain\DomainStorageInterface;
use Drupal\domain_group\Plugin\DomainGroupSettingsBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\domain\Entity\Domain;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\domain\DomainInterface;
use Drupal\group\Access\GroupAccessResult;

/**
 * Provides options for group domain.
 *
 * @DomainGroupSettings(
 *   id = "localgov_microsites_theme_settings",
 *   label = @Translation("Theme override"),
 * )
 */
class ThemeSettings extends DomainGroupSettingsBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The domain entity storage.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, DomainStorageInterface $domain_storage, LanguageManagerInterface $language_manager, ThemeHandlerInterface $theme_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->domainStorage = $domain_storage;
    $this->languageManager = $language_manager;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('domain'),
      $container->get('language_manager'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(GroupInterface $group, AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'set localgov microsite theme override');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, GroupInterface $group) {
    $config_override = NULL;
    if (!$group->isNew() && $domain = $this->domainStorage->load('group_' . $group->id())) {
      $config_override = $this->loadConfigOverride($domain);
      if ($config_override->isNew()) {
        $config_override = NULL;
      }
    }

    $site_config = $this->configFactory->get('system.theme');
    $site_default = $site_config->get('default');
    $site_admin = $site_config->get('admin');

    $admin_options = ['' => ''];
    $default_options = ['' => ''];
    // Get all available themes.
    $themes = $this->themeHandler->rebuildThemeData();
    // Remove obsolete themes.
    $themes = array_filter($themes, function ($theme) {
      return !$theme->isObsolete();
    });
    uasort($themes, [ThemeExtensionList::class, 'sortByName']);
    foreach ($themes as &$theme) {
      if (!empty($theme->status)) {
        if ($theme->getName() != $site_default) {
          $default_options[$theme->getName()] = $theme->info['name'];
        }
        if ($theme->getName() != $site_admin) {
          $admin_options[$theme->getName()] = $theme->info['name'];
        }
      }
    }

    $admin = $config_override ? $config_override->get('admin') : '';
    $form['admin'] = [
      '#type' => 'select',
      '#title' => $this->t('Admin theme'),
      '#default_value' => $admin,
      '#description' => $this->t("Override theme used for administration pages."),
      '#options' => $admin_options,
    ];
    $default = $config_override ? $config_override->get('default') : '';
    $form['default'] = [
      '#type' => 'select',
      '#title' => $this->t('Default theme'),
      '#default_value' => $default,
      '#description' => $this->t("Override theme used for user pages."),
      '#options' => $default_options,
    ];

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
    $group = $form_state->get('group');
    $domain = Domain::load('group_' . $group->id());
    $config_override = $this->loadConfigOverride($domain);

    foreach (['admin', 'default'] as $field_name) {
      $value = $form_state->getValue($field_name);
      if (empty($value)) {
        $config_override->clear($field_name);
      }
      else {
        $config_override->set($field_name, $value);
      }
    }
    $config_override->save();
  }

  /**
   * Load, or create, domain config override - for language.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain site configuration being overriden.
   *
   * @return \Drupal\Core\Config\Config
   *   Editable configuaration.
   */
  private function loadConfigOverride(DomainInterface $domain) {
    if ($this->languageManager->getConfigOverrideLanguage() == $this->languageManager->getDefaultLanguage()) {
      $config_id = 'domain.config.' . $domain->id() . '.system.theme';
    }
    else {
      $config_id = 'domain.config.' . $domain->id() . '.' . $this->languageManager->getConfigOverrideLanguage() . '.system.theme';
    }
    $config_override = $this->configFactory->getEditable($config_id);

    return $config_override;
  }

}
