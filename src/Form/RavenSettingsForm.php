<?php

namespace Drupal\raven\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\raven\Plugin\RavenServerPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RavenSettingsForm.
 *
 * @package Drupal\raven\Form
 */
class RavenSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $configManager;

  /**
   * \Drupal\raven\Plugin\RavenServerPluginManager definition.
   *
   * @var \Drupal\raven\Plugin\RavenServerPluginManager
   */
  protected $ravenServerPluginManager;

  /**
   * Constructs a new RavenSettingsForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RavenServerPluginManager $raven_server_plugin_manager) {
    parent::__construct($config_factory);
    $this->ravenServerPluginManager = $raven_server_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.raven_server_plugin')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'raven.raven_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'raven_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('raven.raven_settings');
    $form['raven_login_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Raven login override'),
      '#description' => $this->t('Override the normal login paths so that users can only log in using Raven.'),
      '#default_value' => $config->get('raven_login_override', TRUE),
    ];

    $form['raven_backdoor_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable non-Raven backdoor login'),
      '#default_value' => $config->get('raven_backdoor_login', TRUE),
      '#states' => [
        // Hide the settings when the cancel notify checkbox is disabled.
        'invisible' => [
          ':input[name="raven_login_override"]' => ['checked' => FALSE],
        ],
      ],
      '#description' => $this->t('Open a hidden path (\'@raven_backdoor_login_path\') to still allow normal Drupal logins. This mean that site-created users such as \'@user1\' will still be able to log in.<br /><i>Warning: Disabling this without having an administrator able to log in with Raven will lock you out of your site.</i>', [
        '@raven_backdoor_login_path' => 'user/backdoor/login',
        // user 1 should always exist, but just in case
        '@user1' => 'admin'/*$user1 ? $user1->name : 'admin'*/,
      ]),
    ];

    // Let the user override the administrator approval process for Raven auth accounts.
    $form['raven_override_administrator_approval'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable administrator approval override'),
      '#default_value' => $config->get('raven_override_administrator_approval', FALSE),
      '#description' => $this->t('Override the Drupal administrator approval settings for users successfully authenticated by Raven.'),
    ];

    // Allow Raven for Life accounts to be authenticated.
    $form['raven_allow_raven_for_life'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow <a href="@url">Raven for Life accounts</a> to be authenticated', ['@url' => 'http://www.ucs.cam.ac.uk/accounts/ravenleaving']),
      '#default_value' => $config->get('raven_allow_raven_for_life', FALSE),
    ];

    // Set which Raven service to use.
    $form['raven_service'] = [
      '#type' => 'radios',
      '#title' => $this->t('Raven service'),
      '#options' => array_map(function ($definition) {
        return $definition['label'];
      },
      $this->ravenServerPluginManager->getDefinitions()),
      '#default_value' => $config->get('raven_service', 'test'),
      '#description' => $this->t('The <a href="@url">demo Raven service</a> can be useful for development, especially when not on the University network.<br /><i>Warning: the demo service must not be used in production, otherwise your site will be compromised.</i>', ['@url' => 'https://demo.raven.cam.ac.uk/']),
    ];

    // Log users out when closing the browser?
    $form['raven_logout_on_browser_close'] = [
      '#type' => 'checkbox',
      '#disabled' => TRUE,
      '#title' => $this->t('Log out Raven users when closing the browser'),
      '#default_value' => $config->get('raven_logout_on_browser_close', FALSE),
      '#description' => $this->t('Drupal, by default, does not log the user out when closing the browser (the session is kept active for over %session.storage.options% days into services.yml). Raven applications, however, are usually expected to do so. This option logs out users who have logged in through Raven when the browser is closed.<br /><i>Note: Enabling this will not affect existing sessions, users will need to log out manually first.</i>'),
    ];

    $site_name = \Drupal::config('system.site')->get('name', '');

    // Site description for Raven Login page.
    $form['raven_website_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your website description'),
      '#default_value' => $config->get('raven_website_description', NULL),
      // Get custom description, otherwise the site name, otherwise the site url.
      '#description' => $this->t('When Raven prompts the user to log in it will display a message with the text <i>\'[...] This resource calls itself \'SITE DESCRIPTION\' and [...]</i>, where SITE DESCRIPTION is specified here. If left blank, the site name will be used (currently \'@sitename\').', ['@sitename' => $site_name]),
    ];

    // Site redirect if a login fails.
    $form['raven_login_fail_redirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login failure redirect'),
      '#field_prefix' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
      '#default_value' => $config->get('raven_login_fail_redirect', NULL),
      '#description' => $this->t('If a login fails, they will be redirected to this page.'),
      '#required' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate login failure redirect path.
    if (!empty($form_state->getValue('raven_login_fail_redirect')) && !\Drupal::service('path.validator')->isValid($form_state->getValue('raven_login_fail_redirect'))) {
      $form_state->setErrorByName('raven_login_fail_redirect', $this->t("The path '%path' is either invalid or you do not have access to it.", array('%path' => $form_state->getValue('raven_login_fail_redirect'))));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('raven.raven_settings')
      ->set('raven_login_override', $form_state->getValue('raven_login_override'))
      ->set('raven_backdoor_login', $form_state->getValue('raven_backdoor_login'))
      ->set('raven_override_administrator_approval', $form_state->getValue('raven_override_administrator_approval'))
      ->set('raven_allow_raven_for_life', $form_state->getValue('raven_allow_raven_for_life'))
      ->set('raven_service', $form_state->getValue('raven_service'))
      ->set('raven_logout_on_browser_close', $form_state->getValue('raven_logout_on_browser_close'))
      ->set('raven_website_description', $form_state->getValue('raven_website_description'))
      ->set('raven_login_fail_redirect', $form_state->getValue('raven_login_fail_redirect'))
      ->save();

    drupal_flush_all_caches();
  }

}
