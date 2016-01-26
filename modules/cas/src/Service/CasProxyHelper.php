<?php

/**
 * @file
 * Contains \Drupal\cas\Service\CasProxyHelper.
 */

namespace Drupal\cas\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Drupal\Component\Utility\UrlHelper;
use GuzzleHttp\Cookie\CookieJar;
use Drupal\cas\Exception\CasProxyException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class CasProxyHelper.
 */
class CasProxyHelper {

  /**
   * The Guzzle HTTP client used to make ticket validation request.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * CAS Helper object.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * Used to get session data.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructor.
   *
   * @param Client $http_client
   *   The HTTP Client library.
   * @param CasHelper $cas_helper
   *   The CAS Helper service.
   * @param SessionInterface $session
   *   The session manager.
   */
  public function __construct(Client $http_client, CasHelper $cas_helper, SessionInterface $session) {
    $this->httpClient = $http_client;
    $this->casHelper = $cas_helper;
    $this->session = $session;
  }

  /**
   * Format a CAS Server proxy ticket request URL.
   *
   * @param string $target_service
   *   The service to be proxied.
   *
   * @return string
   *   The fully formatted URL.
   */
  private function getServerProxyUrl($target_service) {
    $url = $this->casHelper->getServerBaseUrl() . 'proxy';
    $params = array();
    $params['pgt'] = $this->session->get('cas_pgt');
    $params['targetService'] = $target_service;
    return $url . '?' . UrlHelper::buildQuery($params);
  }

  /**
   * Proxy authenticates to a target service.
   *
   * Returns cookies from the proxied service in a
   * CookieJar object for use when later accessing resources.
   *
   * @param string $target_service
   *   The service to be proxied.
   *
   * @return \GuzzleHttp\Cookie\CookieJar
   *   A CookieJar object (array storage) containing cookies from the
   *   proxied service.
   *
   * @throws CasProxyException
   *   Thrown if there was a problem communicating with the CAS server
   *   or if there was is invalid use rsession data.
   */
  public function proxyAuthenticate($target_service) {
    $cas_proxy_helper = $this->session->get('cas_proxy_helper');
    // Check to see if we have proxied this application already.
    if (isset($cas_proxy_helper[$target_service])) {
      $cookies = array();
      foreach ($cas_proxy_helper[$target_service] as $cookie) {
        $cookies[$cookie['Name']] = $cookie['Value'];
      }
      $domain = $cookie['Domain'];
      $jar = CookieJar::fromArray($cookies, $domain);
      $this->casHelper->log("$target_service already proxied. Returning information from session.");
      return $jar;
    }

    if (!($this->casHelper->isProxy() && $this->session->has('cas_pgt'))) {
      // We can't perform proxy authentication in this state.
      throw new CasProxyException("Session state not sufficient for proxying.");
    }

    // Make request to CAS server to retrieve a proxy ticket for this service.
    $cas_url = $this->getServerProxyUrl($target_service);
    try {
      $this->casHelper->log("Retrieving proxy ticket from: $cas_url");
      $response = $this->httpClient->get($cas_url);
      $this->casHelper->log("Received: " . htmlspecialchars($response->getBody()->__toString()));
    }
    catch (ClientException $e) {
      throw new CasProxyException($e->getMessage());
    }
    $proxy_ticket = $this->parseProxyTicket($response->getBody());
    $this->casHelper->log("Extracted proxy ticket: $proxy_ticket");

    // Make request to target service with our new proxy ticket.
    // The target service will validate this ticket against the CAS server
    // and set a cookie that grants authentication for further resource calls.
    $params['ticket'] = $proxy_ticket;
    $service_url = $target_service . "?" . UrlHelper::buildQuery($params);
    $cookie_jar = new CookieJar();
    try {
      $this->casHelper->log("Contacting service: $service_url");
      $this->httpClient->get($service_url, ['cookies' => $cookie_jar]);
    }
    catch (ClientException $e) {
      throw new CasProxyException($e->getMessage());
    }
    // Store in session storage for later reuse.
    $cas_proxy_helper[$target_service] = $cookie_jar->toArray();
    $this->session->set('cas_proxy_helper', $cas_proxy_helper);
    $this->casHelper->log("Stored cookies from $target_service in session.");
    return $cookie_jar;
  }

  /**
   * Parse proxy ticket from CAS Server response.
   *
   * @param string $xml
   *   XML response from CAS Server.
   *
   * @return mixed
   *   A proxy ticket to be used with the target service, FALSE on failure.
   *
   * @throws CasProxyException
   *   Thrown if there was a problem parsing the proxy validation response.
   */
  private function parseProxyTicket($xml) {
    $dom = new \DomDocument();
    $dom->preserveWhiteSpace = FALSE;
    $dom->encoding = "utf-8";
    if (@$dom->loadXML($xml) === FALSE) {
      throw new CasProxyException("CAS Server returned non-XML response.");
    }
    $failure_elements = $dom->getElementsByTagName("proxyFailure");
    if ($failure_elements->length > 0) {
      // Something went wrong with proxy ticket validation.
      throw new CasProxyException("CAS Server rejected proxy request.");
    }
    $success_elements = $dom->getElementsByTagName("proxySuccess");
    if ($success_elements->length === 0) {
      // Malformed response from CAS Server.
      throw new CasProxyException("CAS Server returned malformed response.");
    }
    $success_element = $success_elements->item(0);
    $proxy_ticket = $success_element->getElementsByTagName("proxyTicket");
    if ($proxy_ticket->length === 0) {
      // Malformed ticket.
      throw new CasProxyException("CAS Server provided invalid or malformed ticket.");
    }
    return $proxy_ticket->item(0)->nodeValue;
  }

}
