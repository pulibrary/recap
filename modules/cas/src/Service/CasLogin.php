<?php

namespace Drupal\cas\Service;

use Drupal\cas\Exception\CasLoginException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\cas\Event\CasUserEvent;
use Drupal\cas\CasPropertyBag;
use Drupal\cas\Event\CasPropertyEvent;

class CasLogin {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $settings;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a CasLogin object.
   *
   * @param ConfigFactoryInterface $settings
   *   The config factory object
   */
  public function __construct(ConfigFactoryInterface $settings, EntityManagerInterface $entity_manager, SessionManagerInterface $session_manager, Connection $database_connection, EventDispatcherInterface $event_dispatcher) {
    $this->settings = $settings;
    $this->entityManager = $entity_manager;
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
   *
   * @throws CasLoginException
   */
  public function loginToDrupal(CasPropertyBag $property_bag, $ticket) {
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
    $this->eventDispatcher->dispatch(CasHelper::CAS_USER_ALTER, new CasUserEvent($account, $property_bag));
    $account->save();
    if (!$property_bag->getLoginStatus()) {
      $_SESSION['cas_temp_disable'] = TRUE;
      throw new CasLoginException("Cannot login, an event listener denied access.");
    }
    $this->userLoginFinalize($account);
    $this->storeLoginSessionData($this->sessionManager->getId(), $ticket);
  }

  /**
   * Register a CAS user.
   *
   * @param string $username
   *   Register a new account with the provided username.
   *
   * @throws CasLoginException
   */
  private function registerUser($username) {
    try {
      $user_storage = $this->entityManager->getStorage('user');
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
   * @codeCoverageIgnore
   */
  protected function userLoginFinalize($account) {
    user_login_finalize($account);
  }

  /**
   * Store the Session ID and ticket for single-log-out purposes.
   *
   * @param string $session_id
   *   The hashed session ID, to be used to kill the session later.
   * @param string $ticket
   *   The CAS service ticket to be used as the lookup key.
   *
   * @codeCoverageIgnore
   */
  protected function storeLoginSessionData($session_id, $ticket) {
    $this->connection->insert('cas_login_data')
      ->fields(
        array('sid', 'ticket'),
        array(Crypt::hashBase64($session_id), $ticket)
      )
      ->execute();
  }
}
