<?php

/**
 * @file
 * Contains \Drupal\cas\Controller\LogoutController.
 */

namespace Drupal\cas\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cas\Service\CasHelper;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class LogoutController.
 */
class LogoutController implements ContainerInjectionInterface {

  /**
   * The cas helper used to get settings from.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * The request stack to get the request object from.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack;
   */
  protected $requestStack;

  /**
   * Constructor.
   *
   * @param CasHelper $cas_helper
   *   The CasHelper to get the logout Url from.
   * @param RequestStack $request_stack
   *   The current request stack, to provide context.
   */
  public function __construct(CasHelper $cas_helper, RequestStack $request_stack) {
    $this->casHelper = $cas_helper;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // This needs to get the necessary __construct requirements from
    // the container.
    return new static($container->get('cas.helper'), $container->get('request_stack'));
  }

  /**
   * Logs a user out of Drupal, then redirects them to the CAS server logout.
   */
  public function logout() {
    // Get the CAS server logout Url.
    $logout_url = $this->casHelper->getServerLogoutUrl($this->requestStack->getCurrentRequest());

    // Log the user out. This invokes hook_user_logout and destroys the
    // session.
    $this->userLogout();

    $this->casHelper->log("Drupal session terminated; redirecting to CAS logout at: $logout_url");

    // Redirect the user to the CAS logout screen.
    return new TrustedRedirectResponse($logout_url, 302);
  }

  /**
   * Encapsulate user_logout().
   *
   * @codeCoverageIgnore
   */
  protected function userLogout() {
    user_logout();
  }

}
