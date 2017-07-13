<?php

namespace Drupal\raven\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Raven Server Plugin plugin manager.
 */
class RavenServerPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new RavenServerPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/RavenServerPlugin', $namespaces, $module_handler, 'Drupal\raven\Plugin\RavenServerPluginInterface', 'Drupal\raven\Annotation\RavenServerPlugin');

    $this->alterInfo('raven_raven_server_plugin_info');
    $this->setCacheBackend($cache_backend, 'raven_raven_server_plugin_plugins');
  }

}
