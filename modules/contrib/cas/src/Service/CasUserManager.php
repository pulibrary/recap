<?php

namespace Drupal\cas\Service;

use Drupal\cas\Event\CasPostLoginEvent;
use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\externalauth\AuthmapInterface;
use Drupal\externalauth\Exception\ExternalAuthRegisterException;
use Drupal\cas\Exception\CasLoginException;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\user\UserInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\cas\CasPropertyBag;
use Drupal\Component\Utility\Crypt;

/**
 * Class CasUserManager.
 */
class CasUserManager {

  /**
   * Email address for new users is combo of username + custom hostname.
   *
   * @var int
   */
  const EMAIL_ASSIGNMENT_STANDARD = 0;

  /**
   * Email address for new users is derived from a CAS attirbute.
   *
   * @var int
   */
  const EMAIL_ASSIGNMENT_ATTRIBUTE = 1;

  /**
   * Used to include the externalauth service from the external_auth module.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalAuth;

  /**
   * An authmap service object.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $settings;

  /**
   * Used to get session data.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Used when storing CAS login data.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The CAS Helper.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * CAS proxy helper.
   *
   * @var \Drupal\cas\Service\CasProxyHelper
   */
  protected $casProxyHelper;

  /**
   * Used to dispatch CAS login events.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The name of the external auth provider we use.
   *
   * @var string
   */
  protected $provider = 'cas';

  /**
   * CasUserManager constructor.
   *
   * @param \Drupal\externalauth\ExternalAuthInterface $external_auth
   *   The external auth interface.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The authmap interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $settings
   *   The settings.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param \Drupal\Core\Database\Connection $database_connection
   *   The database connection.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\cas\Service\CasHelper $cas_helper
   *   The CAS helper.
   * @param \Drupal\cas\Service\CasProxyHelper $cas_proxy_helper
   *   The CAS Proxy helper.
   */
  public function __construct(ExternalAuthInterface $external_auth, AuthmapInterface $authmap, ConfigFactoryInterface $settings, SessionInterface $session, Connection $database_connection, EventDispatcherInterface $event_dispatcher, CasHelper $cas_helper, CasProxyHelper $cas_proxy_helper = NULL) {
    $this->externalAuth = $external_auth;
    $this->authmap = $authmap;
    $this->settings = $settings;
    $this->session = $session;
    $this->connection = $database_connection;
    $this->eventDispatcher = $event_dispatcher;
    $this->casHelper = $cas_helper;
    if (!$cas_proxy_helper) {
      @trigger_error('Calling CasUserManager::__construct() without the $cas_proxy_helper argument is deprecated in cas:8.x-1.6 and the $cas_proxy_helper argument will be required in cas:8.x-1.10.', E_USER_DEPRECATED);
      $cas_proxy_helper = \Drupal::service('cas.proxy_helper');
    }
    $this->casProxyHelper = $cas_proxy_helper;
  }

  /**
   * Register a local Drupal user given a CAS username.
   *
   * @param string $authname
   *   The CAS username.
   * @param array $property_values
   *   Property values to assign to the user on registration.
   * @param string $local_username
   *   The local Drupal username to be created.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity of the newly registered user.
   *
   * @throws \Drupal\cas\Exception\CasLoginException
   *   When the user account could not be registered.
   */
  public function register($authname, array $property_values = [], $local_username = NULL) {
    if (!$local_username) {
      @trigger_error('Calling CasUserManager::register() without the $local_username argument is deprecated in cas:8.x-1.6 and the $local_username argument will be required in cas:8.x-2.0.', E_USER_DEPRECATED);
      $local_username = $authname;
    }

    $property_values['name'] = $local_username;
    $property_values['pass'] = $this->randomPassword();

    try {
      $user = $this->externalAuth->register($authname, $this->provider, $property_values);
    }
    catch (ExternalAuthRegisterException $e) {
      throw new CasLoginException($e->getMessage(), CasLoginException::USERNAME_ALREADY_EXISTS);
    }
    return $user;
  }

  /**
   * Attempts to log the user in to the Drupal site.
   *
   * @param \Drupal\cas\CasPropertyBag $property_bag
   *   CasPropertyBag containing username and attributes from CAS.
   * @param string $ticket
   *   The service ticket.
   *
   * @throws \Drupal\cas\Exception\CasLoginException
   *   Thrown if there was a problem logging in the user.
   */
  public function login(CasPropertyBag $property_bag, $ticket) {
    $account = $this->externalAuth->load($property_bag->getUsername(), $this->provider);
    if ($account === FALSE) {
      // Check if we should create the user or not.
      $config = $this->settings->get('cas.settings');
      if ($config->get('user_accounts.auto_register') === TRUE) {
        $this->casHelper->log(
          LogLevel::DEBUG,
          'Existing account not found for user, attempting to auto-register.'
        );

        // Dispatch an event that allows modules to deny automatic registration
        // for this user account or to set properties for the user that will
        // be created.
        $cas_pre_register_event = new CasPreRegisterEvent($property_bag);
        $cas_pre_register_event->setPropertyValue('mail', $this->getEmailForNewAccount($property_bag));
        $this->casHelper->log(LogLevel::DEBUG, 'Dispatching EVENT_PRE_REGISTER.');
        $this->eventDispatcher->dispatch(CasHelper::EVENT_PRE_REGISTER, $cas_pre_register_event);
        if ($cas_pre_register_event->getAllowAutomaticRegistration()) {
          $account = $this->register($property_bag->getUsername(), $cas_pre_register_event->getPropertyValues(), $cas_pre_register_event->getDrupalUsername());
        }
        else {
          throw new CasLoginException("Cannot register user, an event listener denied access.", CasLoginException::SUBSCRIBER_DENIED_REG);
        }
      }
      else {
        throw new CasLoginException("Cannot login, local Drupal user account does not exist.", CasLoginException::NO_LOCAL_ACCOUNT);
      }
    }

    // Check if the retrieved user is blocked before moving forward.
    if (!$account->isActive()) {
      throw new CasLoginException(sprintf('The username %s has not been activated or is blocked.', $account->getAccountName()), CasLoginException::ACCOUNT_BLOCKED);
    }

    // Dispatch an event that allows modules to prevent this user from logging
    // in and/or alter the user entity before we save it.
    $pre_login_event = new CasPreLoginEvent($account, $property_bag);
    $this->casHelper->log(LogLevel::DEBUG, 'Dispatching EVENT_PRE_LOGIN.');
    $this->eventDispatcher->dispatch(CasHelper::EVENT_PRE_LOGIN, $pre_login_event);

    // Save user entity since event listeners may have altered it.
    // @todo Don't take it for granted. Find if the account was really altered.
    // @todo Should this be swapped with the following if(...) block? Why
    //   altering the account if the login has been denied?
    $account->save();

    if (!$pre_login_event->getAllowLogin()) {
      $reason = $pre_login_event->getCancelLoginReason();
      throw (new CasLoginException('Cannot login, an event listener denied access.', CasLoginException::SUBSCRIBER_DENIED_LOGIN))->setSubscriberCancelReason($reason);
    }

    $this->externalAuth->userLoginFinalize($account, $property_bag->getUsername(), $this->provider);
    $this->storeLoginSessionData($this->session->getId(), $ticket);
    $this->session->set('is_cas_user', TRUE);
    $this->session->set('cas_username', $property_bag->getOriginalUsername());

    $postLoginEvent = new CasPostLoginEvent($account, $property_bag);
    $this->casHelper->log(LogLevel::DEBUG, 'Dispatching EVENT_POST_LOGIN.');
    $this->eventDispatcher->dispatch(CasHelper::EVENT_POST_LOGIN, $postLoginEvent);

    if ($this->settings->get('proxy.initialize') && $property_bag->getPgt()) {
      $this->casHelper->log(LogLevel::DEBUG, "Storing PGT information for this session.");
      $this->casProxyHelper->storePgtSession($property_bag->getPgt());
    }
  }

  /**
   * Store the Session ID and ticket for single-log-out purposes.
   *
   * @param string $session_id
   *   The session ID, to be used to kill the session later.
   * @param string $ticket
   *   The CAS service ticket to be used as the lookup key.
   */
  protected function storeLoginSessionData($session_id, $ticket) {
    if ($this->settings->get('cas.settings')->get('logout.enable_single_logout') === TRUE) {
      $this->connection->insert('cas_login_data')
        ->fields(
          ['sid', 'plainsid', 'ticket', 'created'],
          [Crypt::hashBase64($session_id), $session_id, $ticket, time()]
        )
        ->execute();
    }
  }

  /**
   * Return CAS username for account, or FALSE if it doesn't have one.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return bool|string
   *   The CAS username if it exists, or FALSE otherwise.
   */
  public function getCasUsernameForAccount($uid) {
    return $this->authmap->get($uid, $this->provider);
  }

  /**
   * Return uid of account associated with passed in CAS username.
   *
   * @param string $cas_username
   *   The CAS username to lookup.
   *
   * @return bool|int
   *   The uid of the user associated with the $cas_username, FALSE otherwise.
   */
  public function getUidForCasUsername($cas_username) {
    return $this->authmap->getUid($cas_username, $this->provider);
  }

  /**
   * Save an association of the passed in Drupal user account and CAS username.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account entity.
   * @param string $cas_username
   *   The CAS username.
   */
  public function setCasUsernameForAccount(UserInterface $account, $cas_username) {
    $this->authmap->save($account, $this->provider, $cas_username);
  }

  /**
   * Remove the CAS username association with the provided user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account entity.
   */
  public function removeCasUsernameForAccount(UserInterface $account) {
    $this->authmap->delete($account->id());
  }

  /**
   * Generate a random password for new user registrations.
   *
   * @return string
   *   A random password.
   */
  protected function randomPassword() {
    // Default length is 10, use a higher number that's harder to brute force.
    return \user_password(30);
  }

  /**
   * Return the email address that should be assigned to an auto-register user.
   *
   * @param \Drupal\cas\CasPropertyBag $cas_property_bag
   *   The CasPropertyBag associated with the user's login attempt.
   *
   * @return string
   *   The email address.
   *
   * @throws \Drupal\cas\Exception\CasLoginException
   *   Thrown when the email address cannot be derived properly.
   */
  public function getEmailForNewAccount(CasPropertyBag $cas_property_bag) {
    $email_assignment_strategy = $this->settings->get('cas.settings')->get('user_accounts.email_assignment_strategy');
    if ($email_assignment_strategy === self::EMAIL_ASSIGNMENT_STANDARD) {
      return $cas_property_bag->getUsername() . '@' . $this->settings->get('cas.settings')->get('user_accounts.email_hostname');
    }
    elseif ($email_assignment_strategy === self::EMAIL_ASSIGNMENT_ATTRIBUTE) {
      $email_attribute = $this->settings->get('cas.settings')->get('user_accounts.email_attribute');
      if (empty($email_attribute) || !array_key_exists($email_attribute, $cas_property_bag->getAttributes())) {
        throw new CasLoginException('Specified CAS email attribute does not exist.', CasLoginException::ATTRIBUTE_PARSING_ERROR);
      }

      $val = $cas_property_bag->getAttributes()[$email_attribute];
      if (empty($val)) {
        throw new CasLoginException('Empty data found for CAS email attribute.', CasLoginException::ATTRIBUTE_PARSING_ERROR);
      }

      // The attribute value may actually be an array of values, but we need it
      // to only contain 1 value.
      if (is_array($val) && count($val) !== 1) {
        throw new CasLoginException('Specified CAS email attribute was formatted in an unexpected way.', CasLoginException::ATTRIBUTE_PARSING_ERROR);
      }

      if (is_array($val)) {
        $val = $val[0];
      }

      return trim($val);
    }
    else {
      throw new CasLoginException('Invalid email address assignment type for auto user registration specified in settings.');
    }
  }

}
