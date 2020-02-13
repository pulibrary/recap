<?php

namespace Drupal\cas\Controller;

use Drupal\cas\Event\CasPreUserLoadEvent;
use Drupal\cas\Event\CasPreUserLoadRedirectEvent;
use Drupal\cas\Exception\CasLoginException;
use Drupal\cas\Exception\CasSloException;
use Drupal\cas\Service\CasHelper;
use Drupal\cas\Exception\CasValidateException;
use Drupal\cas\Service\CasUserManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\externalauth\ExternalAuthInterface;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cas\Service\CasValidator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\cas\Service\CasLogout;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Class ServiceController.
 */
class ServiceController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * CAS Helper.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * Used to validate CAS service tickets.
   *
   * @var \Drupal\cas\Service\CasValidator
   */
  protected $casValidator;

  /**
   * Used to log a user in after they've been validated.
   *
   * @var \Drupal\cas\Service\CasUserManager
   */
  protected $casUserManager;

  /**
   * Used to log a user out due to a single log out request.
   *
   * @var \Drupal\cas\Service\CasLogout
   */
  protected $casLogout;

  /**
   * Used to retrieve request parameters.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Used to generate redirect URLs.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Stores a Messenger object.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The external auth service.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalAuth;

  /**
   * Constructor.
   *
   * @param \Drupal\cas\Service\CasHelper $cas_helper
   *   The CAS Helper service.
   * @param \Drupal\cas\Service\CasValidator $cas_validator
   *   The CAS Validator service.
   * @param \Drupal\cas\Service\CasUserManager $cas_user_manager
   *   The CAS User Manager service.
   * @param \Drupal\cas\Service\CasLogout $cas_logout
   *   The CAS Logout service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\externalauth\ExternalAuthInterface $external_auth
   *   The external auth service.
   */
  public function __construct(CasHelper $cas_helper, CasValidator $cas_validator, CasUserManager $cas_user_manager, CasLogout $cas_logout, RequestStack $request_stack, UrlGeneratorInterface $url_generator, ConfigFactoryInterface $config_factory, MessengerInterface $messenger, EventDispatcherInterface $event_dispatcher, ExternalAuthInterface $external_auth) {
    $this->casHelper = $cas_helper;
    $this->casValidator = $cas_validator;
    $this->casUserManager = $cas_user_manager;
    $this->casLogout = $cas_logout;
    $this->requestStack = $request_stack;
    $this->urlGenerator = $url_generator;
    $this->settings = $config_factory->get('cas.settings');
    $this->messenger = $messenger;
    $this->eventDispatcher = $event_dispatcher;
    $this->externalAuth = $external_auth;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cas.helper'),
      $container->get('cas.validator'),
      $container->get('cas.user_manager'),
      $container->get('cas.logout'),
      $container->get('request_stack'),
      $container->get('url_generator'),
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('event_dispatcher'),
      $container->get('externalauth.externalauth')
    );
  }

  /**
   * Main point of communication between CAS server and the Drupal site.
   *
   * The path that this controller/action handle are always set to the "service"
   * url when authenticating with the CAS server, so CAS server communicates
   * back to the Drupal site using this controller action. That's why there's
   * so much going on in here - it needs to process a few different types of
   * requests.
   */
  public function handle() {
    $request = $this->requestStack->getCurrentRequest();

    // First, check if this is a single-log-out (SLO) request from the server.
    if ($request->request->has('logoutRequest')) {
      try {
        $this->casLogout->handleSlo($request->request->get('logoutRequest'));
      }
      catch (CasSloException $e) {
        $this->casHelper->log(
          LogLevel::ERROR,
          'Error when handling single-log-out request: %error',
          ['%error' => $e->getMessage()]
        );
      }
      // Always return a 200 response. CAS Server doesn’t care either way what
      // happens here, since it is a fire-and-forget approach taken.
      return Response::create('', 200);
    }

    // We will be redirecting the user below. To prevent the CasSubscriber from
    // initiating an automatic authentiation on that request (like forced
    // auth or gateway auth) and potentially creating an authentication loop,
    // we set a session variable instructing the CasSubscriber skip auto auth
    // for that request.
    $request->getSession()->set('cas_temp_disable_auto_auth', TRUE);

    /* If there is no ticket parameter on the request, the browser either:
     * (a) is returning from a gateway request to the CAS server in which
     *     the user was not already authenticated to CAS, so there is no
     *     service ticket to validate and nothing to do.
     * (b) has hit this URL for some other reason (crawler, curiosity, etc)
     *     and there is nothing to do.
     * In either case, we just want to redirect them away from this controller.
     */
    if (!$request->query->has('ticket')) {
      $this->casHelper->log(LogLevel::DEBUG, "No CAS ticket found in request to service controller; backing out.");
      $this->casHelper->handleReturnToParameter($request);
      return RedirectResponse::create($this->urlGenerator->generate('<front>'));
    }

    // There is a ticket present, meaning CAS server has returned the browser
    // to the Drupal site so we can authenticate the user locally using the
    // ticket.
    $ticket = $request->query->get('ticket');

    // Our CAS service will need to reconstruct the original service URL
    // when validating the ticket. We always know what the base URL for
    // the service URL is (it's this page), but there may be some query params
    // attached as well (like a destination param) that we need to pass in
    // as well. So, detach the ticket param, and pass the rest off.
    $service_params = $request->query->all();
    unset($service_params['ticket']);
    try {
      $cas_validation_info = $this->casValidator->validateTicket($ticket, $service_params);
    }
    catch (CasValidateException $e) {
      // Validation failed, redirect to homepage and set message.
      $this->casHelper->log(
        LogLevel::ERROR,
        'Error when validating ticket: %error',
        ['%error' => $e->getMessage()]
      );
      $message_validation_failure = $this->casHelper->getMessage('error_handling.message_validation_failure');
      if (!empty($message_validation_failure)) {
        $this->messenger->addError($message_validation_failure);
      }

      return $this->createRedirectResponse($request, TRUE);
    }

    $this->casHelper->log(LogLevel::DEBUG, 'Starting login process for CAS user %username', ['%username' => $cas_validation_info->getUsername()]);

    // Dispatch an event that allows modules to alter any of the CAS data before
    // it's used to lookup a Drupal user account via the authmap table.
    $this->casHelper->log(LogLevel::DEBUG, 'Dispatching EVENT_PRE_USER_LOAD.');
    $this->eventDispatcher->dispatch(CasHelper::EVENT_PRE_USER_LOAD, new CasPreUserLoadEvent($cas_validation_info));

    if ($cas_validation_info->getUsername() !== $cas_validation_info->getOriginalUsername()) {
      $this->casHelper->log(
        LogLevel::DEBUG,
        'Username was changed from %original to %new from a subscriber.',
        ['%original' => $cas_validation_info->getOriginalUsername(), '%new' => $cas_validation_info->getUsername()]
      );
    }

    // At this point, the ticket is validated and third-party modules got the
    // chance to alter the username and also perform other 'pre user load'
    // tasks. Before authenticating the user locally, let's allow third-party
    // code to inject user interaction into the flow.
    // @see \Drupal\cas\Event\CasPreUserLoadRedirectEvent
    $cas_pre_user_load_redirect_event = new CasPreUserLoadRedirectEvent($ticket, $cas_validation_info, $service_params);
    $this->casHelper->log(LogLevel::DEBUG, 'Dispatching EVENT_PRE_USER_LOAD_REDIRECT.');
    $this->eventDispatcher->dispatch(CasHelper::EVENT_PRE_USER_LOAD_REDIRECT, $cas_pre_user_load_redirect_event);

    // A subscriber might have set an HTTP redirect response allowing potential
    // user interaction to be injected into the flow.
    $redirect_response = $cas_pre_user_load_redirect_event->getRedirectResponse();
    if ($redirect_response) {
      $this->casHelper->log(LogLevel::DEBUG, 'Redirecting to @url as requested by one of EVENT_PRE_USER_LOAD event subscribers.', ['@url' => $redirect_response->getTargetUrl()]);
      return $redirect_response;
    }

    // Now that the ticket has been validated, we can use the information from
    // validation request to authenticate the user locally on the Drupal site.
    try {
      $this->casUserManager->login($cas_validation_info, $ticket);

      $login_success_message = $this->casHelper->getMessage('login_success_message');
      if (!empty($login_success_message)) {
        $this->messenger->addStatus($login_success_message);
      }
    }
    catch (CasLoginException $e) {
      // Use an appropiate log level depending on exception type.
      if (empty($e->getCode()) || $e->getCode() === CasLoginException::ATTRIBUTE_PARSING_ERROR) {
        $error_level = LogLevel::ERROR;
      }
      else {
        $error_level = LogLevel::INFO;
      }

      $this->casHelper->log($error_level, $e->getMessage());
      $login_error_message = $this->getLoginErrorMessage($e);
      if ($login_error_message) {
        $this->messenger->addError($login_error_message, 'error');
      }

      return $this->createRedirectResponse($request, TRUE);
    }

    return $this->createRedirectResponse($request);
  }

  /**
   * Create a redirect response that sends users somewhere after login.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param bool $login_failed
   *   Indicates if the login failed or not.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  private function createRedirectResponse(Request $request, $login_failed = FALSE) {
    // If login failed, we may have a failure page to send them to.
    if ($login_failed && $this->settings->get('error_handling.login_failure_page')) {
      // Remove 'destination' parameter, otherwise Drupal's
      // RedirectResponseSubscriber will send users to that location instead of
      // the failure page.
      $request->query->remove('destination');

      return RedirectResponse::create(Url::fromUserInput($this->settings->get('error_handling.login_failure_page'))->toString());
    }
    // Otherwise, send them to the homepage, or to the previous page they were
    // on when login was initiated (which will be represented by the 'returnto'
    // parameter).
    else {
      $this->casHelper->handleReturnToParameter($request);
      return RedirectResponse::create($this->urlGenerator->generate('<front>'));
    }
  }

  /**
   * Get the error message to display when there is a login exception.
   *
   * @param \Drupal\cas\Exception\CasLoginException $e
   *   The login exception.
   *
   * @return array|\Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The error message.
   */
  private function getLoginErrorMessage(CasLoginException $e) {
    $code = $e->getCode();
    switch ($code) {
      case CasLoginException::NO_LOCAL_ACCOUNT:
        $msgKey = 'message_no_local_account';
        break;

      case CasLoginException::SUBSCRIBER_DENIED_REG:
        $msgKey = 'message_subscriber_denied_reg';
        break;

      case CasLoginException::ACCOUNT_BLOCKED:
        $msgKey = 'message_account_blocked';
        break;

      case CasLoginException::SUBSCRIBER_DENIED_LOGIN:
        $msgKey = 'message_subscriber_denied_login';
        break;

      case CasLoginException::ATTRIBUTE_PARSING_ERROR:
        // Re-use the normal validation error message.
        $msgKey = 'message_validation_failure';
        break;

      case CasLoginException::USERNAME_ALREADY_EXISTS:
        $msgKey = 'message_username_already_exists';
        break;
    }

    if (!empty($msgKey)) {
      $message = $this->casHelper->getMessage('error_handling.' . $msgKey);
      if ($message) {
        return $message;
      }
    }

    return $this->t('There was a problem logging in. Please contact a site administrator.');
  }

}
