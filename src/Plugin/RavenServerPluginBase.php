<?php

namespace Drupal\raven\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Url;

/**
 * Base class for Raven Server Plugin plugins.
 */
abstract class RavenServerPluginBase extends PluginBase implements RavenServerPluginInterface {

  use ConfigFormBaseTrait;
  public function getEditableConfigNames() {
    return [
      'raven.raven_settings',
    ];
  }
  function ravenLogin($redirect = NULL) {
    global $base_url;

    if ($redirect === NULL) {
      if (isset($_GET['destination']) && FALSE === UrlHelper::isExternal($_GET['destination'])) {
        $redirect = $_GET['destination'];
      }
      elseif (NULL != $_SERVER['HTTP_REFERER']) {
        $redirect = $_SERVER['HTTP_REFERER'];
      }
      else {
        $redirect = $base_url . '/';
      }
    }

    $website_description = \Drupal::config('raven.raven_settings')->get('raven_website_description');

    $params['ver'] = '3';
    $params['url'] = $base_url . '/';
    $params['desc'] = !empty($website_description) ? $website_description : \Drupal::config('raven.raven_settings')->get('site_name', $base_url);

    // @TODO
    // $params['params'] = Url::fromUserInput($redirect, array('absolute' => TRUE, 'language' => (object) array('language' => FALSE)))->toString();
    unset($_GET['destination']);
    return Url::fromUri($this->getUrl(), array('query' => $params));
  }

}
