<?php

namespace Drupal\raven\Plugin\RavenServerPlugin;

use Drupal\Core\Url;
use Drupal\raven\Plugin\RavenServerPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\raven\RavenServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RedirectDestination;

/**
 * @RavenServerPlugin(
 *  id = "test",
 *  label = @Translation("Local test server."),
 * )
 */
class TestRavenServerPlugin extends RavenServerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Routing\RedirectDestination definition.
   *
   * @var \Drupal\Core\Routing\RedirectDestination
   */
  protected $redirectDestination;

  /**
   * Drupal\raven\RavenServiceInterface definition.
   *
   * @var \Drupal\raven\RavenServiceInterface
   */
  protected $ravenService;

  /**
   * Constructs a new TestRavenServerPlugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RedirectDestination $redirect_destination
   * @param \Drupal\raven\RavenServiceInterface $raven_service
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RedirectDestination $redirect_destination, RavenServiceInterface $raven_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->redirectDestination = $redirect_destination;
    $this->ravenService = $raven_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('redirect.destination'),
      $container->get('raven.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    // TODO: Implement get_raven_url() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getKid() {
    // TODO: Implement get_raven_kid() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getCertificate() {
    // TODO: Implement get_raven_pubkey() method.
  }

  /**
   * @return \Drupal\Core\Url
   */
  public function getRedirect() {
    return Url::fromUserInput($this->createRedirect(3, Url::fromRoute('user.login'/*'raven.main_controller.login_auth'*/,[],[])->toString()));
  }

  /**
   *
   */
  public static function createRedirect($ver, $url, $status = 200, $problem = NULL, $expired = FALSE) {
    if (FALSE === in_array($status, [200, 410, 510, 520, 530, 540, 560, 570, 999])) {
      $status = 200;
    }

    $response = [];
    $response['ver'] = $ver;
    $response['status'] = $status;
    $response['msg'] = '';
    $response['issue'] = date('Ymd\THis\Z', $expired ? time() - 36001 : time());
    $response['id'] = '1351247047-25829-18';

    if ('url' === $problem) {
      $response['url'] = 'http://www.example.com/';
    }
    else {
      $response['url'] = $url;
    }

    $response['url'] = str_replace(['%', '!'], ['%25', '%21'], $response['url']);

    $response['principal'] = 'test0001';

    switch ($problem) {
      case 'auth':
        $response['auth'] = 'test';
        $response['sso'] = '';
        break;

      case 'sso':
        $response['auth'] = '';
        $response['sso'] = 'test';
        break;

      default:
        $response['auth'] = 'pwd';
        $response['sso'] = '';
        break;
    }

    $response['life'] = 36000;
    $response['params'] = '';

    if ('kid' === $problem) {
      $response['kid'] = 999;
    }
    else {
      $response['kid'] = 901;
    }

    $data = implode(
      '!',
      [
        $response['ver'],
        $response['status'],
        $response['msg'],
        $response['issue'],
        $response['id'],
        $response['url'],
        $response['principal'],
        $response['auth'],
        $response['sso'],
        $response['life'],
        $response['params'],
      ]
    );
    $pkeyid = openssl_pkey_get_private(
      '-----BEGIN RSA PRIVATE KEY-----
MIICWwIBAAKBgQC4RYvbSGb42EEEXzsz93Mubo0fdWZ7UJ0HoZXQch5XIR0Zl8AN
aLf3tVpRz4CI2JBUVpUjXEgzOa+wZBbuvczOuiB3BfNDSKKQaftxWKouboJRA5ac
xa3fr2JZc8O5Qc1J6Qq8E8cjuSQWlpxTGa0JEnbKV7/PVUFDuFeEI11e/wIDAQAB
AoGACr2jBUkXF3IjeAnE/aZyxEYVW7wQGSf9vzAf92Jvekyn0ZIS07VC4+FiPlqF
93QIFaJmVwVOAA5guztaStgtU9YX37wRPkFwrtKgjZcqV8ReQeC67bjo5v3Odht9
750F7mKWXctZrm0MD1PoDlkLvVZ2hDolHm5tpfP52jPvQ6ECQQDgtI4K3IuEVOIg
75xUG3Z86DMmwPmme7vsFgf2goWV+p4471Ang9oN7l+l+Jj2VISdz7GE7ZQwW6a1
IQev3+h7AkEA0e9oC+lCcYsMsI9vtXvB8s6Bpl0c1U19HUUWHdJIpluwvxF6SIL3
ug4EJPP+sDT5LvdV5cNy7nmO9uUd+Se2TQJAdxI2UrsbkzwHt7xA8rC60OWadWa8
4+OdaTUjcxUnBJqRTUpDBy1vVwKB3MknBSE0RQvR3canSBjI9iJSmHfmEQJAKJlF
49fOU6ryX0q97bjrPwuUoxmqs81yfrCXoFjEV/evbKPypAc/5SlEv+i3vlfgQKbw
Y6iyl0/GyBRzAXYemQJAVeChw15Lj2/uE7HIDtkqd8POzXjumOxKPfESSHKxRGnP
3EruVQ6+SY9CDA1xGfgDSkoFiGhxeo1lGRkWmz09Yw==
-----END RSA PRIVATE KEY-----'
    );

    openssl_sign($data, $signature, $pkeyid);

    openssl_free_key($pkeyid);

    $signature =
      preg_replace(
        [
          '#\+#',
          '#/#',
          '#=#',
        ],
        [
          '-',
          '.',
          '_',
        ],
        base64_encode($signature)
      );

    $response['sig'] = $signature;

    switch ($problem) {
      case 'invalid':
        // Need an invalid response, so just need to change a value.
        $response['id'] = 12312424;
        break;

      case 'incomplete':
        unset($response['id']);
        break;
    }

    return $url . (FALSE !== strpos($url, '?') ? '&' : '?') . 'WLS-Response=' . urlencode(implode('!', $response));
  }

}
