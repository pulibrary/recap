<?php

namespace Drupal\cas\Controller;

use Drupal\cas\Service\CasHelper;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class ForceLoginController implements ContainerInjectionInterface {
  /**
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   *
   * @param CasHelper $cas_helper
   *   The CAS helper service.
   * @param RequestStack $request_stack
   *   Symfony request stack.
   */
  public function __construct(CasHelper $cas_helper, RequestStack $request_stack) {
    $this->casHelper = $cas_helper;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('cas.helper'), $container->get('request_stack'));
  }

  /**
   * Handles a page request for our forced login route.
   */
  public function forceLogin() {
    // TODO: What if CAS is not configured? need to handle that case.

    $query_params = $this->requestStack->getCurrentRequest()->query->all();
    $cas_login_url = $this->casHelper->getServerLoginUrl($query_params);
    $this->casHelper->log("Cas forced login route, redirecting to: $cas_login_url");

    return TrustedRedirectResponse::create($cas_login_url, 302);
  }
}
