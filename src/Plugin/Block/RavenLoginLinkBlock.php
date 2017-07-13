<?php

namespace Drupal\raven\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
class RavenLoginLinkBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $configManager;

  /**
   * Constructs a new RavenLoginLinkBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigManager $config_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigManager $config_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configManager = $config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['raven_login_form'] = \Drupal::formBuilder()->getForm('\Drupal\raven\Form\LoginForm');
    return $build;
  }

}
