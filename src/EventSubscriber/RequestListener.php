<?php

namespace Drupal\raven\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Config\ConfigManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RequestListener.
 *
 * @package Drupal\raven
 */
class RequestListener implements EventSubscriberInterface {

  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $configManager;

  /**
   * Constructs a new RequestListener object.
   * @param \Drupal\Core\Config\ConfigManager $config_manager
   */
  public function __construct(ConfigManager $config_manager) {
    $this->configManager = $config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = ['onKernelRequest', 30];
//    $events[KernelEvents::REQUEST] = ['onRequest', 10];

    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function onKernelRequest(GetResponseEvent $event) {

    $request = $event->getRequest();
    if (FALSE === $request->query->has('WLS-Response')) {
      return;
    }

    $wlsResponse = $request->query->get('WLS-Response');
    $request->query->remove('WLS-Response');
    $uri = $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo();
    if ($request->query->count() > 0) {
      $uri .= '?' . http_build_query($request->query->all());
    }
    $request->getSession()->set('wls_response', $wlsResponse);
    $event->setResponse(new RedirectResponse(Url::fromRoute('raven.main_controller.login_auth')->toString()));

  }

//  public function onRequest(GetResponseEvent $event) {
//
//    $request = $event->getRequest();
//    $session = $request->getSession();
//
//    if ($session != FALSE && $session->has('wls_response')) {
//      //$session->remove('wls_response');
//      drupal_set_message('Event kernel.request thrown by Subscriber in module raven.', 'status', TRUE);
//
//    }
//  }
}
