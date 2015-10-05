<?php

namespace Drupal\cas\Controller;

use Drupal\cas\Exception\CasLoginException;
use Drupal\cas\Service\CasHelper;
use Drupal\cas\Service\CasLogin;
use Drupal\cas\Exception\CasValidateException;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cas\Service\CasValidator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\cas\Service\CasLogout;
use Symfony\Component\HttpFoundation\Response;

class ServiceController implements ContainerInjectionInterface {
  /**
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * @var \Drupal\cas\Service\CasValidator
   */
  protected $casValidator;

  /**
   * @var \Drupal\cas\Service\CasLogin
   */
  protected $casLogin;

  /**
   * @var \Drupal\cas\Service\CasLogout
   */
  protected $casLogout;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructor.
   *
   * @param CasHelper $cas_helper
   *   The CAS Helper service.
   * @param CasValidator $cas_validator
   *   The CAS Validator service.
   * @param CasLogin $cas_login
   *   The CAS Login service.
   * @param CasLogout $cas_logout
   *   The CAS Logout service.
   * @param UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(CasHelper $cas_helper, CasValidator $cas_validator, CasLogin $cas_login, CasLogout $cas_logout, RequestStack $request_stack, UrlGeneratorInterface $url_generator) {
    $this->casHelper = $cas_helper;
    $this->casValidator = $cas_validator;
    $this->casLogin = $cas_login;
    $this->casLogout = $cas_logout;
    $this->requestStack = $request_stack;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('cas.helper'), $container->get('cas.validator'), $container->get('cas.login'), $container->get('cas.logout'), $container->get('request_stack'), $container->get('url_generator'));
  }

  /**
   * Handles a request to either validate a user login or log a user out.
   *
   * The path that this controller/action handle are always set to the "service"
   * when authenticating with the CAS server, so CAS server communicates back to
   * the Drupal site using this controller.
   */
  public function handle() {
    $request = $this->requestStack->getCurrentRequest();

    // First, check if this is a single-log-out (SLO) request from the server.
    if ($request->request->has('logoutRequest')) {
      $this->casHelper->log("Logout request: passing to casLogout::handleSlo");
      $this->casLogout->handleSlo($request->request->get('logoutRequest'));
      // Always return a 200 code. CAS Server doesn’t care either way what
      // happens here, since it is a fire-and-forget approach taken.
      return Response::create('', 200);
    }

    // Our CAS Subscriber, which implements forced redirect and gateway, will
    // set this query string param which indicates we should disable the
    // subscriber on the next redirect. This prevents an infinite redirect loop.
    if ($request->query->has('cas_temp_disable')) {
      $this->casHelper->log("Temp disable flag set, set session flag.");
      $_SESSION['cas_temp_disable'] = TRUE;
    }

    // Check if there is a ticket parameter. If there isn't, we could be
    // returning from a gateway request and the user may not be logged into CAS.
    // Just redirect away from here.
    if (!$request->query->has('ticket')) {
      $this->casHelper->log("No ticket detected, move along.");
      $this->handleReturnToParameter($request);
      return RedirectResponse::create($this->urlGenerator->generate('<front>'));
    }
    $ticket = $request->query->get('ticket');

    // Our CAS service will need to reconstruct the original service URL
    // when validating the ticket. We always know what the base URL for
    // the service URL (it's this page), but there may be some query params
    // attached as well (like a destination param) that we need to pass in
    // as well. So, detach the ticket param, and pass the rest off.
    $service_params = $request->query->all();
    unset($service_params['ticket']);
    $cas_version = $this->casHelper->getCasProtocolVersion();
    $this->casHelper->log("Configured to use CAS protocol version: $cas_version");
    try {
      $cas_validation_info = $this->casValidator->validateTicket($cas_version, $ticket, $service_params);
    }
    catch (CasValidateException $e) {
      // Validation failed, redirect to homepage and set message.
      $this->setMessage(t('There was a problem validating your login, please contact a site administrator.'), 'error');
      $this->handleReturnToParameter($request);
      return RedirectResponse::create($this->urlGenerator->generate('<front>'));
    }

    try {
      $this->casLogin->loginToDrupal($cas_validation_info, $ticket);
      if ($this->casHelper->isProxy() && $cas_validation_info->getPgt()) {
        $this->casHelper->log("Storing PGT information for this session.");
        $this->casHelper->storePGTSession($cas_validation_info->getPgt());
      }
      $this->setMessage(t('You have been logged in.'));
    }
    catch (CasLoginException $e) {
      $this->setMessage(t('There was a problem logging in, please contact a site administrator.'), 'error');
    }

    $this->handleReturnToParameter($request);
    return RedirectResponse::create($this->urlGenerator->generate('<front>'));
  }

  /**
   * Converts a "returnto" query param to a "destination" query param.
   *
   * The original service URL for CAS server may contain a "returnto" query
   * parameter that was placed there to redirect a user to specific page after
   * logging in with CAS.
   *
   * Drupal has a built in mechanism for doing this, by instead using a
   * "destination" parameter in the URL. Anytime there's a RedirectResponse
   * returned, RedirectResponseSubscriber looks for the destination param and
   * will redirect a user there instead.
   *
   * We cannot use this built in method when constructing the service URL,
   * because when we redirect to the CAS server for login, Drupal would see
   * our destination parameter in the URL and redirect there instead of CAS.
   *
   * However, when we redirect the user after a login success / failure,
   * we can then convert it back to a "destination" parameter and let Drupal
   * do it's thing when redirecting.
   *
   * @param Request $request
   *   The Symfony request object.
   */
  private function handleReturnToParameter(Request $request) {
    if ($request->query->has('returnto')) {
      $this->casHelper->log("Converting returnto parameter to destination.");
      $request->query->set('destination', $request->query->get('returnto'));
    }
  }

  /**
   * Encapsulation of drupal_set_message.
   *
   * See https://www.drupal.org/node/2278383 for discussion about converting
   * drupal_set_message to a service. In the meantime, in order to unit test
   * the error handling here, we have to encapsulate the call in a method.
   *
   * @param string $message
   *   The message text to set.
   * @param string $type
   *   The message type. 
   * @param bool $repeat
   *   Whether identical messages should all be shown.
   *
   * @codeCoverageIgnore
   */
  public function setMessage($message, $type = 'status', $repeat = FALSE) {
    drupal_set_message($message, $type, $repeat);
  }
}
