<?php

/**
 * @file
 * Contains Drupal\cas\Subscriber\CasSubscriber.
 */

namespace Drupal\cas\Subscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Condition\ConditionManager;
use Drupal\cas\Service\CasHelper;

/**
 * Provides a CasSubscriber.
 */
class CasSubscriber implements EventSubscriberInterface {

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Route matcher object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatcher;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Condition manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * CAS helper.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * Constructs a new CasSubscriber.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_matcher
   *   The route matcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition manager.
   * @param \Drupal\cas\Service\CasHelper $cas_helper
   *   The CAS Helper service.
   */
  public function __construct(RequestStack $request_stack, RouteMatchInterface $route_matcher, ConfigFactoryInterface $config_factory, AccountInterface $current_user, ConditionManager $condition_manager, CasHelper $cas_helper) {
    $this->requestStack = $request_stack;
    $this->routeMatcher = $route_matcher;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->conditionManager = $condition_manager;
    $this->casHelper = $cas_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Priority is just before the Dynamic Page Cache subscriber, but after
    // important services like route matcher and maintenance mode subscribers.
    $events[KernelEvents::REQUEST][] = array('handle', 29);
    return $events;
  }

  /**
   * The entry point for our subscriber.
   *
   * @param GetResponseEvent $event
   *   The response event from the kernel.
   */
  public function handle(GetResponseEvent $event) {
    // Don't do anything if this is a sub request and not a master request.
    if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    // Nothing to do if the user is already logged in.
    if ($this->currentUser->isAuthenticated()) {
      return;
    }

    // Some routes we don't want to run on.
    if ($this->isIgnoreableRoute()) {
      return;
    }

    // Don't do anything if this is a request from cron, drush, crawler, etc.
    if ($this->isNotNormalRequest()) {
      return;
    }

    // The service controller may have indicated that this current request
    // should not be automatically sent to CAS for authentication checking.
    // This is to prevent infinite redirect loops.
    $session = $this->requestStack->getCurrentRequest()->getSession();
    if ($session->has('cas_temp_disable_auto_auth')) {
      $session->remove('cas_temp_disable_auto_auth');
      $this->casHelper->log("Temp disable flag set, skipping CAS subscriber.");
      return;
    }

    // Check to see if we should require a forced login. It will set a response
    // on the event if so.
    if ($this->handleForcedPath($event)) {
      return;
    }

    // Check to see if we should initiate a gateway auth check. It will set a
    // response on the event if so.
    $this->handleGateway($event);
  }

  /**
   * Check if a forced login path is configured, and force login if so.
   *
   * @param GetResponseEvent $event
   *   The response event from the kernel.
   *
   * @return bool
   *   TRUE if we are forcing the login, FALSE otherwise
   */
  private function handleForcedPath(GetResponseEvent $event) {
    $config = $this->configFactory->get('cas.settings');
    if ($config->get('forced_login.enabled') != TRUE) {
      return FALSE;
    }

    // Check if user provided specific paths to force/not force a login.
    $condition = $this->conditionManager->createInstance('request_path');
    $condition->setConfiguration($config->get('forced_login.paths'));

    if ($this->conditionManager->execute($condition)) {
      $cas_login_url = $this->casHelper->getServerLoginUrl(array(
        'returnto' => $this->requestStack->getCurrentRequest()->getUri(),
      ));
      $this->casHelper->log("Forced login path detected, redirecting to: $cas_login_url");
      $event->setResponse($this->createNonCachedRedirectToCasServer($cas_login_url));
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if we should implement the CAS gateway feature.
   *
   * @param GetResponseEvent $event
   *   The response event from the kernel.
   *
   * @return bool
   *   TRUE if gateway mode was implemented, FALSE otherwise.
   */
  private function handleGateway(GetResponseEvent $event) {
    // Only implement gateway feature for GET requests, to prevent users from
    // being redirected to CAS server for things like form submissions.
    if (!$this->requestStack->getCurrentRequest()->isMethod('GET')) {
      return FALSE;
    }

    $config = $this->configFactory->get('cas.settings');
    $check_frequency = $config->get('gateway.check_frequency');
    if ($check_frequency === CasHelper::CHECK_NEVER) {
      return FALSE;
    }

    // User can indicate specific paths to enable (or disable) gateway mode.
    $condition = $this->conditionManager->createInstance('request_path');
    $condition->setConfiguration($config->get('gateway.paths'));
    if (!$this->conditionManager->execute($condition)) {
      return FALSE;
    }

    // If set to only implement gateway once per session, we use a session
    // variable to store the fact that we've already done the gateway check
    // so we don't keep doing it.
    if ($check_frequency === CasHelper::CHECK_ONCE) {
      // If the session var is already set, we know to back out.
      if ($this->requestStack->getCurrentRequest()->getSession()->has('cas_gateway_checked')) {
        $this->casHelper->log("Gateway already checked, will not check again.");
        return FALSE;
      }
      $this->requestStack->getCurrentRequest()->getSession()->set('cas_gateway_checked', TRUE);
    }

    $cas_login_url = $this->casHelper->getServerLoginUrl(array(
      'returnto' => $this->requestStack->getCurrentRequest()->getUri(),
    ), TRUE);
    $this->casHelper->log("Gateway activated, redirecting to $cas_login_url");

    $event->setResponse($this->createNonCachedRedirectToCasServer($cas_login_url));

    return TRUE;
  }

  /**
   * Check is the current request is a normal web request from a user.
   *
   * We don't want to perform any CAS redirects for things like cron
   * and drush.
   *
   * @return bool
   *   Whether or not this is a normal request.
   */
  private function isNotNormalRequest() {
    $current_request = $this->requestStack->getCurrentRequest();
    if (stristr($current_request->server->get('SCRIPT_FILENAME'), 'xmlrpc.php')) {
      return TRUE;
    }
    if (stristr($current_request->server->get('SCRIPT_FILENAME'), 'cron.php')) {
      $this->casHelper->log("Skip processing requests for cron.");
      return TRUE;
    }
    if ($current_request->server->get('HTTP_USER_AGENT')) {
      $crawlers = array(
        'Google',
        'msnbot',
        'Rambler',
        'Yahoo',
        'AbachoBOT',
        'accoona',
        'AcoiRobot',
        'ASPSeek',
        'CrocCrawler',
        'Dumbot',
        'FAST-WebCrawler',
        'GeonaBot',
        'Gigabot',
        'Lycos',
        'MSRBOT',
        'Scooter',
        'AltaVista',
        'IDBot',
        'eStyle',
        'Scrubby',
        'gsa-crawler',
      );
      // Return on the first find.
      foreach ($crawlers as $c) {
        if (stripos($current_request->server->get('HTTP_USER_AGENT'), $c) !== FALSE) {
          $this->casHelper->log("Ignoring request from $c");
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Checks current request route against a list of routes we want to ignore.
   *
   * @return bool
   *   TRUE if we should ignore this request, FALSE otherwise.
   */
  private function isIgnoreableRoute() {
    $routes_to_ignore = array(
      'cas.service',
      'cas.proxyCallback',
      'cas.login',
      'cas.logout',
    );

    $current_route = $this->routeMatcher->getRouteName();
    if (in_array($current_route, $routes_to_ignore)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Create a redirect response to the CAS server.
   *
   * We ensure this response is not cacheable, otherwise an infinite redirect
   * loop is created when users are returned to the URL they are on when
   * forced login or gateway mode was triggered.
   *
   * @param string $url
   *   The URL to the CAS server.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   The non-cacheable redirect response.
   *
   * @see https://www.drupal.org/node/2607818
   */
  private function createNonCachedRedirectToCasServer($url) {
    // Don't allow this redirect to be cached to prevent an infinite redirect
    // loop when we return users to this page.
    $redirect = TrustedRedirectResponse::create($url)
      ->addCacheableDependency([]);

    return $redirect;
  }

}
