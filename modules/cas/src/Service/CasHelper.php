<?php

/**
 * @file
 * Contains \Drupal\src\Service\CasHelper.
 */

namespace Drupal\cas\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\RfcLogLevel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class CasHelper.
 */
class CasHelper {

  /**
   * SSL configuration to use the system's CA bundle to verify CAS server.
   *
   * @var int
   */
  const CA_DEFAULT = 0;

  /**
   * SSL configuration to use provided file to verify CAS server.
   *
   * @var int
   */
  const CA_CUSTOM = 1;

  /**
   * SSL Configuration to not verify CAS server.
   *
   * @var int
   */
  const CA_NONE = 2;

  /**
   * Gateway config: never check preemptively to see if the user is logged in.
   *
   * @var int
   */
  const CHECK_NEVER = -2;

  /**
   * Gateway config: check once per session to see if the user is logged in.
   *
   * @var int
   */
  const CHECK_ONCE = -1;

  /**
   * Gateway config: check on every page load to see if the user is logged in.
   *
   * @var int
   */
  const CHECK_ALWAYS = 0;

  /**
   * Event type identifier for user load events.
   *
   * @var string
   */
  const EVENT_USER_LOAD = 'cas.user_load';

  /**
   * Event type identifier for pre auth events.
   *
   * @var string
   */
  const EVENT_PRE_AUTH = 'cas.pre_auth';

  /**
   * Stores database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Stores URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Stores logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $loggerChannel;

  /**
   * Used to get session data.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param UrlGeneratorInterface $url_generator
   *   The URL generator.
   * @param Connection $database_connection
   *   The database service.
   * @param LoggerChannelFactory $logger_factory
   *   The logger channel factory.
   * @param SessionInterface $session
   *   The session handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, UrlGeneratorInterface $url_generator, Connection $database_connection, LoggerChannelFactory $logger_factory, SessionInterface $session) {
    $this->urlGenerator = $url_generator;
    $this->connection = $database_connection;
    $this->session = $session;

    $this->settings = $config_factory->get('cas.settings');
    $this->loggerChannel = $logger_factory->get('cas');
  }

  /**
   * Return the login URL to the CAS server.
   *
   * @param array $service_params
   *   An array of query string parameters to add to the service URL.
   * @param bool $gateway
   *   TRUE if this should be a gateway request.
   *
   * @return string
   *   The fully constructed server login URL.
   */
  public function getServerLoginUrl($service_params = array(), $gateway = FALSE) {
    $login_url = $this->getServerBaseUrl() . 'login';

    $params = array();
    if ($gateway) {
      $params['gateway'] = 'true';
    }
    $params['service'] = $this->getCasServiceUrl($service_params);

    return $login_url . '?' . UrlHelper::buildQuery($params);
  }

  /**
   * Return the validation URL used to validate the provided ticket.
   *
   * @param string $ticket
   *   The ticket to validate.
   * @param array $service_params
   *   An array of query string parameters to add to the service URL.
   *
   * @return string
   *   The fully constructed validation URL.
   */
  public function getServerValidateUrl($ticket, $service_params = array()) {
    $validate_url = $this->getServerBaseUrl();
    $path = '';
    switch ($this->getCasProtocolVersion()) {
      case "1.0":
        $path = 'validate';
        break;

      case "2.0":
        if ($this->canBeProxied()) {
          $path = 'proxyValidate';
        }
        else {
          $path = 'serviceValidate';
        }
        break;
    }
    $validate_url .= $path;

    $params = array();
    $params['service'] = $this->getCasServiceUrl($service_params);
    $params['ticket'] = $ticket;
    if ($this->isProxy()) {
      $params['pgtUrl'] = $this->formatProxyCallbackUrl();
    }
    return $validate_url . '?' . UrlHelper::buildQuery($params);
  }

  /**
   * Return the version of the CAS server protocol.
   *
   * @return mixed|null
   *   The version.
   */
  public function getCasProtocolVersion() {
    return $this->settings->get('server.version');
  }

  /**
   * Return the SSL verification method.
   *
   * @return int
   *   The verification method.
   */
  public function getSslVerificationMethod() {
    return $this->settings->get('server.verify');
  }

  /**
   * Return CA PEM file path.
   *
   * @return mixed|null
   *   The path to the PEM file for the CA.
   */
  public function getCertificateAuthorityPem() {
    return $this->settings->get('server.cert');
  }

  /**
   * Return the service URL.
   *
   * @param array $service_params
   *   An array of query string parameters to append to the service URL.
   *
   * @return string
   *   The fully constructed service URL to use for CAS server.
   */
  private function getCasServiceUrl($service_params = array()) {
    return $this->urlGenerator->generate('cas.service', $service_params, TRUE);
  }

  /**
   * Construct the base URL to the CAS server.
   *
   * @return string
   *   The base URL.
   */
  public function getServerBaseUrl() {
    $url = 'https://' . $this->settings->get('server.hostname');
    $port = $this->settings->get('server.port');
    if (!empty($port)) {
      $url .= ':' . $this->settings->get('server.port');
    }
    $url .= $this->settings->get('server.path');
    $url = rtrim($url, '/') . '/';

    return $url;
  }

  /**
   * Determine whether this client is configured to act as a proxy.
   *
   * @return bool
   *   TRUE if proxy, FALSE otherwise.
   */
  public function isProxy() {
    return $this->settings->get('proxy.initialize') == TRUE;
  }

  /**
   * Format the pgtCallbackURL parameter for use with proxying.
   *
   * We have to do a str_replace to force https for the proxy callback URL,
   * because it must use https, and setting the option 'https => TRUE' in the
   * options array won't force https if the user accessed the login route over
   * http and mixed-mode sessions aren't allowed.
   *
   * @return string
   *   The pgtCallbackURL, fully formatted.
   */
  private function formatProxyCallbackUrl() {
    return str_replace('http://', 'https://', $this->urlGenerator->generateFromRoute('cas.proxyCallback', array(), array(
      'absolute' => TRUE,
    )));
  }

  /**
   * Lookup a PGT by PGTIOU.
   *
   * @param string $pgt_iou
   *   A pgtIou to use a key for the lookup.
   *
   * @return string
   *   The PGT value.
   *
   * @codeCoverageIgnore
   */
  protected function lookupPgtByPgtIou($pgt_iou) {
    return $this->connection->select('cas_pgt_storage', 'c')
      ->fields('c', array('pgt'))
      ->condition('pgt_iou', $pgt_iou)
      ->execute()
      ->fetch()
      ->pgt;
  }

  /**
   * Store the PGT in the user session.
   *
   * @param string $pgt_iou
   *   A pgtIou to identify the PGT.
   */
  public function storePgtSession($pgt_iou) {
    $pgt = $this->lookupPgtByPgtIou($pgt_iou);
    $this->session->set('cas_pgt', $pgt);
    // Now that we have the pgt in the session,
    // we can delete the database mapping.
    $this->deletePgtMappingByPgtIou($pgt_iou);
  }

  /**
   * Delete a PGT/PGTIOU mapping from the database.
   *
   * @param string $pgt_iou
   *   A pgtIou string to use as the deletion key.
   *
   * @codeCoverageIgnore
   */
  protected function deletePgtMappingByPgtIou($pgt_iou) {
    $this->connection->delete('cas_pgt_storage')
      ->condition('pgt_iou', $pgt_iou)
      ->execute();
  }

  /**
   * Determine whether this client is allowed to be proxied.
   *
   * @return bool
   *   TRUE if it can be proxied, FALSE otherwise.
   */
  public function canBeProxied() {
    return $this->settings->get('proxy.can_be_proxied') == TRUE;
  }

  /**
   * Return the allowed proxy chains list.
   *
   * @return string
   *   A newline delimited list of proxy chains.
   */
  public function getProxyChains() {
    return $this->settings->get('proxy.proxy_chains');
  }

  /**
   * Log information to the logger.
   *
   * Only log supplied information to the logger if module is configured to do
   * so, otherwise do nothing.
   *
   * @param string $message
   *   The message to log.
   */
  public function log($message) {
    if ($this->settings->get('debugging.log') == TRUE) {
      $this->loggerChannel->log(RfcLogLevel::DEBUG, $message);
    }
  }

  /**
   * Return the logout URL for the CAS server.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request, to provide base url context.
   *
   * @return string
   *   The fully constructed server logout URL.
   */
  public function getServerLogoutUrl(Request $request) {
    $base_url = $this->getServerBaseUrl() . 'logout';
    if ($this->settings->get('logout.logout_destination') != '') {
      $destination = $this->settings->get('logout.logout_destination');
      if ($destination == '<front>') {
        // If we have '<front>', resolve the path.
        $params['service'] = $this->urlGenerator->generate($destination, array(), TRUE);
      }
      elseif ($this->isExternal($destination)) {
        // If we have an absolute URL, use that.
        $params['service'] = $destination;
      }
      else {
        // This is a regular Drupal path.
        $params['service'] = $request->getSchemeAndHttpHost() . '/' . ltrim($destination, '/');
      }
      return $base_url . '?' . UrlHelper::buildQuery($params);
    }
    else {
      return $base_url;
    }
  }

  /**
   * Encapsulate UrlHelper::isExternal.
   *
   * @param string $url
   *   The url to evaluate.
   *
   * @return bool
   *   Whether or not the url points to an external location.
   *
   * @codeCoverageIgnore
   */
  protected function isExternal($url) {
    return UrlHelper::isExternal($url);
  }

  /**
   * Check if the current logout request should be served by caslogout.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request instance.
   *
   * @return bool
   *   Whether to process logout as caslogout.
   */
  public function provideCasLogoutOverride(Request $request) {
    if ($this->settings->get('logout.cas_logout') == TRUE) {
      if ($this->isCasSession($request->getSession()->getId())) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Check if the given session ID was authenticated with CAS.
   *
   * @param string $session_id
   *   The session ID to look up.
   *
   * @return bool
   *   Whether or not this session was authenticated with CAS.
   *
   * @codeCoverageIgnore
   */
  public function isCasSession($session_id) {
    $results = $this->connection->select('cas_login_data')
      ->fields('cas_login_data', array('sid'))
      ->condition('sid', Crypt::hashBase64($session_id))
      ->execute()
      ->fetchAll();

    return !empty($results);
  }

  /**
   * Whether or not session IDs are stored for single logout.
   *
   * @return bool
   *   Whether or not single logout is enabled in the configuration.
   */
  public function getSingleLogOut() {
    return $this->settings->get('logout.enable_single_logout');
  }

}
