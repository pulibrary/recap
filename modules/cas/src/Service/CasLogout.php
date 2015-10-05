<?php

namespace Drupal\cas\Service;

use Drupal\cas\Exception\CasSloException;

class CasLogout {

  /**
   * Handle's a single-log-out request from a CAS server.
   *
   * @param string $data
   *   The raw data posted to us from the CAS server.
   */
  public function handleSlo($data) {
    try {
      $service_ticket = $this->getServiceTicketFromData($data);

      // TODO: Find and remove the session based on the service ticket.
      // A quick look at SessionManager doesn't look good, as deleting a session
      // can only be done by a user. We cannot just delete the session record
      // from the DB because you can have session data stored elsewhere.
    }
    catch (CasSloException $e) {
      // Log the error, do nothing else.
    }
  }

  /**
   * Parse the SLO SAML and return the service ticket.
   *
   * @param string $data
   *   The raw data posted to us from the CAS server.
   *
   * @return string
   *   The service ticket to log out.
   * @throws CasSloException
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
}
