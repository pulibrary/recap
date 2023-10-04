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
   */
  public function content(): Response {
    // Use plain response with timestamp.
    return new Response(time(), 200, ['Content-Type' => 'text/plain']);
  }

}
