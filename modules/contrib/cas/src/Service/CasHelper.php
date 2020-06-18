<?php

namespace Drupal\cas\Service;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Utility\Token;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CasHelper.
 */
class CasHelper {

  /**
   * SSL configuration to use the system's CA bundle to verify CAS server.
   *
   * @var int
   */
  const CA_DEFAULT = 0;

  /**
   * SSL configuration to use provided file to verify CAS server.
   *
   * @var int
   */
  const CA_CUSTOM = 1;

  /**
   * SSL Configuration to not verify CAS server.
   *
   * @var int
   */
  const CA_NONE = 2;

  /**
   * Gateway config: never check preemptively to see if the user is logged in.
   *
   * @var int
   */
  const CHECK_NEVER = -2;

  /**
   * Gateway config: check once per session to see if the user is logged in.
   *
   * @var int
   */
  const CHECK_ONCE = -1;

  /**
   * Gateway config: check on every page load to see if the user is logged in.
   *
   * @var int
   */
  const CHECK_ALWAYS = 0;

  /**
   * Event type identifier for the CasPreUserLoadEvent.
   *
   * @var string
   */
  const EVENT_PRE_USER_LOAD = 'cas.pre_user_load';

  /**
   * Event type identifier for the CasPreUserLoadRedirectEvent event.
   *
   * @var string
   */
  const EVENT_PRE_USER_LOAD_REDIRECT = 'cas.pre_user_load.redirect';

  /**
   * Event type identifier for the CasPreRegisterEvent.
   *
   * @var string
   */
  const EVENT_PRE_REGISTER = 'cas.pre_register';

  /**
   * Event type identifier for the CasPreLoginEvent.
   *
   * @var string
   */
  const EVENT_PRE_LOGIN = 'cas.pre_login';

  /**
   * Event type identifier for pre auth events.
   *
   * @var string
   */
  const EVENT_PRE_REDIRECT = 'cas.pre_redirect';

  /**
   * Event to modify CAS server config before it's used to validate a ticket.
   */
  const EVENT_PRE_VALIDATE_SERVER_CONFIG = 'cas.pre_validate_server_config';

  /**
   * Event type identifier for pre validation events.
   *
   * @var string
   */
  const EVENT_PRE_VALIDATE = 'cas.pre_validate';

  /**
   * Event type identifier for events fired after service validation.
   *
   * @var string
   */
  const EVENT_POST_VALIDATE = 'cas.post_validate';

  /**
   * Event type identifier for events fired after login has completed.
   */
  const EVENT_POST_LOGIN = 'cas.post_login';

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Stores logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $loggerChannel;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, Token $token) {
    $this->settings = $config_factory->get('cas.settings');
    $this->loggerChannel = $logger_factory->get('cas');
    $this->token = $token;
  }

  /**
   * Wrap Drupal's normal logger.
   *
   * This allows us to only log debug messages if configured to do so.
   *
   * @param mixed $level
   *   The message to log.
   * @param string $message
   *   The error message.
   * @param array $context
   *   The context.
   */
  public function log($level, $message, array $context = []) {
    // Back out of logging if it's a debug message and we're not configured
    // to log those types of messages. This helps keep the drupal log clean
    // on busy sites.
    if ($level == LogLevel::DEBUG && !$this->settings->get('advanced.debug_log')) {
      return;
    }
    $this->loggerChannel->log($level, $message, $context);
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
   * However, when we redirect the user after a login success/failure, we can
   * then convert it back to a "destination" parameter and let Drupal do it's
   * thing when redirecting.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Symfony request object.
   */
  public function handleReturnToParameter(Request $request) {
    if ($request->query->has('returnto')) {
      $this->log(LogLevel::DEBUG, "Converting query parameter 'returnto' to 'destination'.");
      $request->query->set('destination', $request->query->get('returnto'));
    }
  }

  /**
   * Returns a translated configurable message given the message config key.
   *
   * @param string $key
   *   The message config key.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The customized message or an empty string.
   *
   * @throws \InvalidArgumentException
   *   If the passed key don't match a config entry.
   */
  public function getMessage($key) {
    assert($key && is_string($key));
    $message = $this->settings->get($key);
    if ($message === NULL || !is_string($message)) {
      throw new \InvalidArgumentException("Invalid key '$key'");
    }

    // Empty string.
    if (!$message) {
      return '';
    }

    return new FormattableMarkup(Xss::filter($this->token->replace($message)), []);
  }

}
