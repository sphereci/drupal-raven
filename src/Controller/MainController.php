<?php

namespace Drupal\raven\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RedirectDestination;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\raven\RavenService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigManager;

/**
 * Class MainController.
 *
 * @package Drupal\raven\Controller
 */
class MainController extends ControllerBase {

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
   * Constructs a new MainController object.
   */
  public function __construct(ConfigManager $config_manager, RavenService $raven_service) {
    $this->configManager = $config_manager;
    $this->ravenService = $raven_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.manager'),
      $container->get('raven.service')
    );
  }

  /**
   * Raven.login_form.
   *
   * @return string
   *   Return Hello string.
   */
  public function loginForm() {
    $request = \Drupal::request();
    $session = $request->getSession();

    if ($session != FALSE && $session->has('wls_response')) {
      $wlsResponse = $session->get('wls_response');
      $session->remove('wls_response');
      return $this->ravenService->raven_auth($wlsResponse);

    }
    return new TrustedRedirectResponse($this->ravenService->getCurrentServer()
      ->getRedirect()
      ->toString());
  }

  /**
   * Hello.
   *
   * @return string
   *   Return Hello string.
   */
  public function loginAuth() {

//    $request = \Drupal::request();
//    $session = $request->getSession();
//
//    if ($session != FALSE && $session->has('wls_response')) {
//      $session->remove('wls_response');
////      drupal_set_message('Event kernel.request thrown by Subscriber in module raven.', 'status', TRUE);
//
//      $wlsResponse = $session->get('wls_response');
//      return $this->ravenService->raven_auth($wlsResponse);
//    }
//
//    return new TrustedRedirectResponse('/');

  }

}
