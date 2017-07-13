<?php

namespace Drupal\raven\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Raven Server Plugin plugins.
 */
interface RavenServerPluginInterface extends PluginInspectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getUrl();

  /**
   * {@inheritdoc}
   */
  public function getKid();

  /**
   * {@inheritdoc}
   */
  public function getCertificate();

  /**
   * {@inheritdoc}
   */
  public function getRedirect();

}
