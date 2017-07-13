<?php

namespace Drupal\raven\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Raven Server Plugin item annotation object.
 *
 * @see \Drupal\raven\Plugin\RavenServerPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class RavenServerPlugin extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
