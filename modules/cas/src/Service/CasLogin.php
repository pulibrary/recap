<?php

/**
 * @file
 * Contains \Drupal\cas\Service\CasLogin.
 */

namespace Drupal\cas\Service;

use Drupal\cas\Exception\CasLoginException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Component\Utility\Crypt;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\cas\Event\CasUserEvent;
use Drupal\cas\CasPropertyBag;
use Drupal\cas\Event\CasPropertyEvent;

/**
 * Class CasLogin.
 */
class CasLogin {

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $settings;

  /**
   * Used when creating a new user.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Used to get session data.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * Used when storing CAS login data.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Used to dispatch CAS login events.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * CasLogin constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $settings
   *   The settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session manager.
   * @param \Drupal\Core\Database\Connection $database_connection
   *   The database connection.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(ConfigFactoryInterface $settings, EntityTypeManagerInterface $entity_type_manager, SessionManagerInterface $session_manager, Connection $database_connection, EventDispatcherInterface $event_dispatcher) {
    $this->settings = $settings;
    $this->entityTypeManager = $entity_type_manager;
    $this->sessionManager = $session_manager;
    $this->connection = $database_connection;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Attempts to log the authenticated CAS user into Drupal.
   *
   * This method should be used to login a user after they have successfully
   * authenticated with the CAS server.
   *
   * @param CasPropertyBag $property_bag
   *   CasPropertyBag containing username and attributes from CAS.
   * @param string $ticket
   *   The service ticket.
   *
   * @throws CasLoginException
   *   Thrown if there was a problem logging in the user.
   */
  public function loginToDrupal(CasPropertyBag $property_bag, $ticket) {
    // Fire CAS pre-login event to allow modules the opportunity to inspect
    // the CAS data and deny login or registration for the user.
    $this->eventDispatcher->dispatch(CasHelper::CAS_PROPERTY_ALTER, new CasPropertyEvent($property_bag));

    $account = $this->userLoadByName($property_bag->getUsername());
    if (!$account) {
      $config = $this->settings->get('cas.settings');
      if ($config->get('user_accounts.auto_register') === TRUE) {
        if (!$property_bag->getRegisterStatus()) {
          $_SESSION['cas_temp_disable'] = TRUE;
          throw new CasLoginException("Cannot register user, an event listener denied access.");
        }
        $account = $this->registerUser($property_bag->getUsername());
      }
      else {
        throw new CasLoginException("Cannot login, local Drupal user account does not exist.");
      }
    }

    // Dispatch additional CAS pre-login event to allow modules to alter the
    // user object before logging them in (like to add user roles).
    $this->eventDispatcher->dispatch(CasHelper::CAS_USER_ALTER, new CasUserEvent($account, $property_bag));

    if (!$property_bag->getLoginStatus()) {
      $_SESSION['cas_temp_disable'] = TRUE;
      throw new CasLoginException("Cannot login, an event listener denied access.");
    }

    // Save user entity since event listeners may have altered it.
    $account->save();

    $this->userLoginFinalize($account);
    $this->storeLoginSessionData($this->sessionManager->getId(), $ticket);
  }

  /**
   * Register a CAS user.
   *
   * @param string $username
   *   Register a new account with the provided username.
   *
   * @return \Drupal\user\UserInterface
   *   The created user entity.
   *
   * @throws CasLoginException
   *   Thrown if there was a problem registering the user.
   */
  private function registerUser($username) {
    try {
      $user_storage = $this->entityTypeManager->getStorage('user');
      $account = $user_storage->create(array(
        'name' => $username,
        'status' => 1,
      ));
      $account->enforceIsNew();
      $account->save();
      return $account;
    }
    catch (EntityStorageException $e) {
      throw new CasLoginException("Error registering user: " . $e->getMessage());
    }
  }

  /**
   * Encapsulate user_load_by_name.
   *
   * See https://www.drupal.org/node/2157657
   *
   * @param string $username
   *   The username to lookup a User entity by.
   *
   * @return object|bool
   *   A loaded $user object or FALSE on failure.
   *
   * @codeCoverageIgnore
   */
  protected function userLoadByName($username) {
    return user_load_by_name($username);
  }

  /**
   * Encapsulate user_login_finalize.
   *
   * See https://www.drupal.org/node/2157657
   *
   * @param \Drupal\user\UserInterface $account
   *   The user entity.
   *
   * @codeCoverageIgnore
   */
  protected function userLoginFinalize(UserInterface $account) {
    user_login_finalize($account);
  }

  /**
   * Store the Session ID and ticket for single-log-out purposes.
   *
   * @param string $session_id
   *   The session ID, to be used to kill the session later.
   * @param string $ticket
   *   The CAS service ticket to be used as the lookup key.
   *
   * @codeCoverageIgnore
   */
  protected function storeLoginSessionData($session_id, $ticket) {
    if ($this->settings->get('cas.settings')->get('logout.enable_single_logout') === TRUE) {
      $plainsid = $session_id;
    }
    else {
      $plainsid = '';
    }
    $this->connection->insert('cas_login_data')
      ->fields(
        array('sid', 'plainsid', 'ticket'),
        array(Crypt::hashBase64($session_id), $plainsid, $ticket)
      )
      ->execute();
  }

}
