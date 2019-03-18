<?php

namespace Drupal\raven\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Plugin\Block\UserLoginBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'RavenLoginLinkBlock' block.
 *
 * @Block(
 *  id = "raven_login_link_block",
 *  admin_label = @Translation("Raven Login Link Block"),
 * )
 */
class RavenLoginLinkBlock extends UserLoginBlock implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  protected $settings;

  /**
   * Constructs a new RavenLoginLinkBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config_factory, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_match);
    $this->configFactory = $config_factory;
    $this->settings = $this->configFactory->get('raven.raven_settings');
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
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->settings->get('raven_login_override', FALSE) == FALSE) {
      return parent::build();
    }
    $build = [];
    $build['raven_login_form'] = \Drupal::formBuilder()
      ->getForm('\Drupal\raven\Form\LoginForm');
    return $build;
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return $this|\Drupal\Core\Access\AccessResultInterface
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::allowed()
        ->addCacheContexts(['route.name', 'user.roles:anonymous']);
    }
    return AccessResult::forbidden();
  }

}
