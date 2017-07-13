<?php

namespace Drupal\raven\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\raven\RavenService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigManager;

/**
 * Class LoginForm.
 *
 * @package Drupal\raven\Form
 */
class LoginForm extends FormBase {

  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $configManager;

  /**
   * @var \Drupal\raven\RavenService
   */
  protected $ravenService;

  /**
   * Constructs a new LoginForm object.
   */
  public function __construct(ConfigManager $config_manager, RavenService $raven_service) {
    $this->configManager = $config_manager;
    $this->ravenService = $raven_service;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.manager'),
      $container->get('raven.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Log in with Raven'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new TrustedRedirectResponse($this->ravenService->getCurrentServer()->getRedirect()->toString());
    $form_state->setResponse($response);

  }

}
