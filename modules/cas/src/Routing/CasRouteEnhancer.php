<?php

/**
 * @file
 * Contains \Drupal\cas\Routing\CasRouteEnhancer.
 */

namespace Drupal\cas\Routing;

use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\cas\Service\CasHelper;

class CasRouteEnhancer implements RouteEnhancerInterface {

  /**
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * Constructor.
   *
   * @param CasHelper $cas_helper
   *   The CAS helper service.
   */
  public function __construct(CasHelper $cas_helper) {
    $this->casHelper = $cas_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if ($this->casHelper->provideCasLogoutOverride($request)) {
      $defaults['_controller'] = '\Drupal\cas\Controller\LogoutController::logout';
    }

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return ($route->getPath() == '/user/logout');
  }

}
