<?php

namespace Drupal\raven;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\externalauth\Authmap;
use Drupal\externalauth\ExternalAuth;
use Drupal\raven\Plugin\RavenServerPluginManager;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class RavenService.
 *
 * @package Drupal\raven
 */
class RavenService implements RavenServiceInterface {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\raven\Plugin\RavenServerPluginManager definition.
   *
   * @var \Drupal\raven\Plugin\RavenServerPluginManager
   */
  protected $ravenServerPlugin;

  /**
   * @var object
   */
  protected $currentServer;

  /**
   * @var array|mixed|null
   */
  protected $handler;

  /**
   * @var \Drupal\externalauth\ExternalAuth
   */
  protected $externalAuth;

  /**
   * @var \Drupal\externalauth\Authmap
   */
  protected $authmap;

  /**
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  private $session;

  /**
   * Constructs a new RavenService object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   * @param \Drupal\raven\Plugin\RavenServerPluginManager $raven_server_manager
   */
  public function __construct(ConfigFactory $config_factory, RavenServerPluginManager $raven_server_manager, ExternalAuth $external_auth, Authmap $authmap, Session $session) {
    $this->configFactory = $config_factory;
    $this->ravenServerPlugin = $raven_server_manager;
    $this->externalAuth = $external_auth;
    $this->authmap = $authmap;
    $this->config = $this->configFactory->get('raven.raven_settings');
    $this->logger = \Drupal::logger('raven');
    $this->session = $session;
  }

  /**
   * @deprecated
   * @return \Drupal\raven\Plugin\RavenServerPluginInterface|object
   */
  public function getCurrentServer() {
    return $this->ravenServerPlugin->createInstance($this->config->get('raven_service'), []);
  }

  function raven_auth($response = NULL) {
    global $base_url;

    if (FALSE != empty($response)) {
      return $this->failRedirect();
    }

    // Parse Raven Reply
    $parts = explode('!', $response);

    $r_ver = array_shift($parts);
    $versions = array('1' => 12, '2' => 12, '3' => 13);

    if (FALSE === in_array($r_ver, array('1', '2', '3'), TRUE) || count($parts) <> $versions[$r_ver]) {
      drupal_set_message(t('Suspicious login attempt denied and logged.'), 'error');
      $this->logger->alert('Suspicious login attempt. Raven response is not acceptable (@wls_response).', array('@wls_response' => $response));
      return $this->failRedirect();
    }

    $r_sig = array_pop($parts);
    $r_kid = array_pop($parts);

    if ($r_ver >= 3) {
      list($r_status, $r_msg, $r_issue, $r_id, $r_url, $r_principal, $r_ptags, $r_auth, $r_sso, $r_life, $r_params) = $parts;
    }
    else {
      list($r_status, $r_msg, $r_issue, $r_id, $r_url, $r_principal, $r_auth, $r_sso, $r_life, $r_params) = $parts;
    }

    array_unshift($parts, $r_ver);

    if (($r_status === '200') && ($this->raven_signature_check(implode('!', $parts), $r_sig) === TRUE)) {
      // Timeout check
      if ((time() - strtotime($r_issue)) > 30) {
        drupal_set_message(t('Login attempt timed out.'), 'error');
        $this->logger->warning('Timeout on login attempt for @raven_id', array('@raven_id' => $r_principal));
        return $this->failRedirect();
      }

      // 'kid' check
      if ($r_kid !== $this->getCurrentServer()->getKid()) {
        drupal_set_message(t('Suspicious login attempt denied and logged.'), 'error');
        $this->logger->alert('Suspicious login attempt claiming to be @raven_id. \'kid\' validation failed: expecting \'@expected\', got \'@given\'.', array(
          '@raven_id' => $r_principal,
          '@expected' => $this->getCurrentServer()->getKid(),
          '@given' => $r_kid,
        ));
        return $this->failRedirect();
      }

      // Valid path check
      if ($r_url !== $base_url . '/') {
        drupal_set_message(t('Suspicious login attempt denied and logged.'), 'error');
        $this->logger->alert('Suspicious login attempt claiming to be @raven_id. \'url\' validation failed: expecting \'@expected\', got \'@given\'.', array(
          '@raven_id' => $r_principal,
          '@expected' => $base_url . '/',
          '@given' => $r_url,
        ));
        return $this->failRedirect();
      }

      // 'auth' check
      if ($r_auth !== 'pwd' && $r_auth !== '') {
        drupal_set_message(t('Suspicious login attempt denied and logged.'), 'error');
        $this->logger->alert('Suspicious login attempt claiming to be @raven_id. \'auth\' validation failed: expecting \'@expected\', got \'@given\'.', array(
          '@raven_id' => $r_principal,
          '@expected' => 'pwd',
          '@given' => $r_auth,
        ));
        return $this->failRedirect();
      }

      // 'sso' check
      if ($r_sso !== 'pwd' && $r_auth === '') {
        drupal_set_message(t('Suspicious login attempt denied and logged.'), 'error');
        $this->logger->alert('Suspicious login attempt claiming to be @raven_id. \'sso\' validation failed: expecting \'@expected\', got \'@given\'.', array(
          '@raven_id' => $r_principal,
          '@expected' => 'pwd',
          '@given' => $r_sso,
        ));
        return $this->failRedirect();
      }

      // Raven for Life check
      if (isset($r_ptags) && $r_ptags !== 'current' && $this->config->get('raven_allow_raven_for_life', FALSE) != TRUE) {
        drupal_set_message(t('Raven for Life accounts are not allowed to access the site.'), 'error');
        $this->logger->info('Raven for Life account @raven_id denied access.', array('@raven_id' => $r_principal));
        return $this->failRedirect();
      }

      // Successful login
      $this->user_raven_login_register($r_principal);
      $url = !empty($r_params) ? urldecode($r_params) : Url::fromRoute('<front>')->toString();
      return new RedirectResponse($url);
    }
    elseif ($r_status === '410') {
      $this->logger->info('Raven authentication cancelled.', array());
      drupal_set_message(t('Raven authentication cancelled.'));
      return $this->failRedirect();
    }
    else {
      drupal_set_message(t('Raven authentication failure.'), 'error');
      $this->logger->error('Authentication failure: @message.', array('@message' => $this->raven_response_status_name($r_status)));
      return $this->failRedirect();
    }
  }

  function raven_signature_check($data, $sig) {
    $key = openssl_pkey_get_public($this->getCurrentServer()->getCertificate());
    $result = openssl_verify(rawurldecode($data), $this->raven_signature_decode(rawurldecode($sig)), $key);
    openssl_free_key($key);
    switch ($result) {
      case 1:
        return TRUE;
        break;
      case 0:
        return FALSE;
        break;
      default:
        drupal_set_message(t('Error authenticating.'), 'error');
        $this->logger->error('raven', 'OpenSSL error.', array());
        return FALSE;
        break;
    }
  }

  function raven_signature_decode($str) {
    $result = preg_replace(array(
      '/-/',
      '/\./',
      '/_/',
    ), array(
      '+',
      '/',
      '=',
    ), $str);
    $result = base64_decode($result);
    return $result;
  }

  public function user_raven_login_register($name) {
    $edit = array();

    $account = $this->externalAuth->load($name, 'raven');

    if ($this->config->get('raven_logout_on_browser_close', FALSE) == TRUE) {
      // @TODO https://www.drupal.org/node/2238561
      ini_set('session.cookie_lifetime', 0);
    }

    if ($account === FALSE) {
      // User hasn't logged in with Raven before
      $account = user_load_by_name($name);
      if ($account === FALSE) {
        // User does not exist yet
        // Check if overriding admin approval is set
        if ($this->config->get('raven_override_administrator_approval', FALSE)) {
          $status = 1;
        }
        else {
          switch ($this->configFactory->get('user.settings')->get('register')) {
            case USER_REGISTER_ADMINISTRATORS_ONLY:
              drupal_set_message(t('Only site administrators can create accounts.'), 'error');
              unset($_GET['destination']);
              return $this->failRedirect();
            case USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL:
              drupal_set_message(t('Thank you for applying for an account. Your account is currently pending approval by the site administrator.'));
              $status = 0;
              break;
            default:
              $status = 1;
              break;
          }
        }
        $edit = array(
          'name' => $name,
          'pass' => user_password(),
          'init' => $name . '@cam.ac.uk',
          'mail' => $name . '@cam.ac.uk',
          'status' => $status,
          'access' => REQUEST_TIME,
        );
        $account = User::create($edit);
//        $account->set('is_raven_user', TRUE);

//        drupal_alter('raven_register', $edit, $account);
        $this->logger->notice('New user: @name (@email).', array(
          '@name' => $edit['name'],
          '@email' => $edit['mail']
          )
        );
      }
      else {
//        $account->set('is_raven_user', TRUE);
//        drupal_alter('raven_migrate', $edit, $account);
        $this->logger->notice('Migrated user: @name (@email).', array(
            '@name' => $account->name->value,
            '@email' => isset($edit['mail']) ? $edit['mail'] : $account->mail->value,
          )
        );
      }
    }
    else {
//      $account->set('is_raven_user', TRUE);
//      drupal_alter('raven_login', $edit, $account);
    }

    $account->save();
//    $account = entity_create('user', $edit);

    if (FALSE === $account) {
      drupal_set_message(t('Error saving user account.'), 'error');
    }
    elseif (FALSE === isset($status) && user_is_blocked($account->name->value)) {
      drupal_set_message(t('The username @name is blocked.', array('@name' => $account->name->value)), 'error');
    }
    else {
      $this->authmap->save($account, 'raven', $account->name->value);

      // Log user in
      $this->externalAuth->userLoginFinalize($account, $account->name->value, 'raven');
    }

    /** @var \Drupal\user\Entity\User $user */
    $user = \Drupal::currentUser()->getAccount();

    if (!$user->id() || $user->status == 0) {
      unset($_GET['destination']);
      return $this->failRedirect();
    }
  }

  /**
   * Get Raven response status name.
   *
   * @param int $code
   *   Response status code.
   *
   * @return string
   *   Response status name.
   */
  function raven_response_status_name($code) {
    switch ($code) {
      case 200:
        return 'Successful authentication';
      case 410:
        return 'The user cancelled the authentication request';
      case 510:
        return 'No mutually acceptable authentication types available';
      case 520:
        return 'Unsupported protocol version';
      case 530:
        return 'General request parameter error';
      case 540:
        return 'Interaction would be required';
      case 560:
        return 'WAA not authorised';
      case 570:
        return 'Authentication declined';
      default:
        return 'Unknown status code';
    }
  }

  private function failRedirect() {
    return new RedirectResponse($this->config->get('raven_login_fail_redirect'));
  }
}
