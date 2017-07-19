<?php

namespace Drupal\raven\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\Plugin\Block\UserLoginBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigManager;

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

  /**
   * Constructs a new RavenLoginLinkBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigManager $config_factory
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config_factory, RouteMatchInterface $route_match) { // , BlockManager $block_manager
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
    if($this->settings->get('raven_login_override', FALSE) == FALSE) {
      return parent::build();
    }
    $build = [];
    $build['raven_login_form'] = \Drupal::formBuilder()->getForm('\Drupal\raven\Form\LoginForm');
    return $build;
  }

}
