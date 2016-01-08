<?php

/**
 * @file
 * Contains \Drupal\cas\Service\CasLogout.
 */

namespace Drupal\cas\Service;

use Drupal\cas\Exception\CasSloException;
use Drupal\Core\Database\Connection;

/**
 * Class CasLogout.
 */
class CasLogout {

  /**
   * The CAS helper.
   *
   * @var \Drupal\cas\Service\CasHelper;
   */
  protected $casHelper;

  /**
   * The database connection used to find the user's session ID.
   *
   * @var \Drupal\Core\Database\Connection;
   */
  protected $connection;

  /**
   * CasLogout constructor.
   *
   * @param \Drupal\cas\Service\CasHelper $cas_helper
   *   The CAS helper.
   * @param \Drupal\Core\Database\Connection $database_connection
   *   The database connection.
   */
  public function __construct(CasHelper $cas_helper, Connection $database_connection) {
    $this->casHelper = $cas_helper;
    $this->connection = $database_connection;
  }

  /**
   * Handles a single-log-out request from a CAS server.
   *
   * @param string $data
   *   The raw data posted to us from the CAS server.
   */
  public function handleSlo($data) {
    $this->casHelper->log("Attempting to handle SLO request.");

    // Only look up tickets if they were stored to begin with.
    if (!$this->casHelper->getSingleLogout()) {
      $this->casHelper->log("Aborting; SLO is not enabled in CAS settings.");
      return;
    }

    $service_ticket = $this->getServiceTicketFromData($data);

    // Look up the session ID by the service ticket, then load up that
    // session and destroy it.
    $sid = $this->lookupSessionIdByServiceTicket($service_ticket);
    if (!$sid) {
      return;
    }

    $this->destroySession($sid);

    $this->casHelper->log("SLO request completed successfully.");
  }

  /**
   * Load up the session and destroy it.
   *
   * @param string $sid
   *   The ticket id to destroy.
   *
   * @codeCoverageIgnore
   */
  protected function destroySession($sid) {
    session_id($sid);
    session_unset();
    session_destroy();
    session_write_close();
    session_regenerate_id(TRUE);
  }

  /**
   * Parse the SLO SAML and return the service ticket.
   *
   * @param string $data
   *   The raw data posted to us from the CAS server.
   *
   * @return string
   *   The service ticket to log out.
   *
   * @throws CasSloException
   *   If the logout data could not be parsed.
   */
  private function getServiceTicketFromData($data) {
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = FALSE;
    $dom->encoding = "utf-8";

    if ($dom->loadXML($data) === FALSE) {
      throw new CasSloException("SLO data from CAS server is not valid.");
    }

    $session_elements = $dom->getElementsByTagName('SessionIndex');
    if ($session_elements->length == 0) {
      throw new CasSloException("SLO data from CAS server is not valid.");
    }

    $session_element = $session_elements->item(0);
    return $session_element->nodeValue;
  }

  /**
   * Lookup Session ID by CAS service ticket.
   *
   * @param string $ticket
   *   A service ticket value from CAS to lookup in the database.
   *
   * @return string
   *   The session ID corresponding to the session ticket.
   *
   * @codeCoverageIgnore
   */
  private function lookupSessionIdByServiceTicket($ticket) {
    $result = $this->connection->select('cas_login_data', 'c')
      ->fields('c', array('plainsid'))
      ->condition('ticket', $ticket)
      ->execute()
      ->fetch();
    if (!empty($result)) {
      return $result->plainsid;
    }
  }

}
