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
      $redirect = '/';
      if (isset($_GET['back_path'])) {
        if(FALSE === UrlHelper::isExternal($_GET['back_path'])) {
          $redirect = '/' . implode('/',array_filter(explode('/', $_GET['back_path'])));
        }
      }
    }
    $website_description = \Drupal::config('raven.raven_settings')->get('raven_website_description');

    $params['ver'] = '3';
    $params['url'] = $base_url . '/raven/login';
    $params['desc'] = !empty($website_description) ? $website_description : \Drupal::config('raven.raven_settings')->get('site_name', $base_url);
    $params['params'] = $redirect;
    return Url::fromUri($this->getUrl(), array('query' => $params));
  }

}
