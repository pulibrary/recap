<?php

namespace Drupal\health_check\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for route that simply returns current time without page cache.
 */
class HealthController extends ControllerBase {

  /**
   * Health check.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function content() {
    // Use plain response with timestamp.
    $response = new Response();
    $response->headers->set('Content-Type', 'text/plain');
    $response->setContent(time());

    return $response;
  }

}
