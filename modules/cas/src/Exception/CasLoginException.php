<?php

namespace Drupal\cas\Exception;

/**
 * Class CasLoginException.
 */
class CasLoginException extends \Exception {

  /**
   * Auto registraton turned off, and local account does not exist.
   */
  const NO_LOCAL_ACCOUNT = 1;

  /**
   * Auto reg turned on, but subscriber denied auto reg.
   */
  const SUBSCRIBER_DENIED_REG = 2;

  /**
   * Could not log user in, because Drupal account is blocked.
   */
  const ACCOUNT_BLOCKED = 3;

  /**
   * Event listener prevented login.
   */
  const SUBSCRIBER_DENIED_LOGIN = 4;

  /**
   * Error parsing CAS attributes during login.
   */
  const ATTRIBUTE_PARSING_ERROR = 5;

  /**
   * Auto registration attempted to register Drupal user that already exists.
   */
  const USERNAME_ALREADY_EXISTS = 6;

}
