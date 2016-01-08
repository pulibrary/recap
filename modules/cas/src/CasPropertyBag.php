<?php

/**
 * @file
 * Contains \Drupal\cas\CasPropertyBag.
 */

namespace Drupal\cas;

/**
 * Class CasPropertyBag.
 */
class CasPropertyBag {

  /**
   * The username of the CAS user.
   *
   * @var string
   */
  protected $username;

  /**
   * The proxy granting ticket, if supplied.
   *
   * @var string
   */
  protected $pgt;

  /**
   * An array containing attributes returned from the server.
   *
   * @var array
   */
  protected $attributes;

  /**
   * Whether or not user is allowed to log in.
   *
   * @var bool
   */
  protected $loginStatus;

  /**
   * Whether or not user is allowed to register an account.
   *
   * @var bool
   */
  protected $registerStatus;

  /**
   * Contructor.
   *
   * @param string $user
   *   The username of the CAS user.
   */
  public function __construct($user) {
    $this->username = $user;
    $this->loginStatus = TRUE;
    $this->registerStatus = TRUE;
  }

  /**
   * Username property setter.
   *
   * @param string $user
   *   The new username.
   */
  public function setUsername($user) {
    $this->username = $user;
  }

  /**
   * Proxy granting ticket property setter.
   *
   * @param string $ticket
   *   The ticket to set as pgt.
   */
  public function setPgt($ticket) {
    $this->pgt = $ticket;
  }

  /**
   * Attributes property setter.
   *
   * @param array $cas_attributes
   *   An associative array containing attribute names as keys.
   */
  public function setAttributes(array $cas_attributes) {
    $this->attributes = $cas_attributes;
  }

  /**
   * Login status property setter.
   *
   * @param bool $status
   *   Whether or not user is allowed to log in.
   */
  public function setLoginStatus($status) {
    $this->loginStatus = $status;
  }

  /**
   * Register status property setter.
   *
   * @param bool $status
   *   Whether or not user is allowed to register.
   */
  public function setRegisterStatus($status) {
    $this->registerStatus = $status;
  }

  /**
   * Username property getter.
   *
   * @return string
   *   The username property.
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * Proxy granting ticket getter.
   *
   * @return string
   *   The pgt property.
   */
  public function getPgt() {
    return $this->pgt;
  }

  /**
   * Cas attributes getter.
   *
   * @return array
   *   The attributes property.
   */
  public function getAttributes() {
    return $this->attributes;
  }

  /**
   * Login status getter.
   *
   * @return bool
   *   The loginStatus property.
   */
  public function getLoginStatus() {
    return $this->loginStatus;
  }

  /**
   * Register status getter.
   *
   * @return bool
   *   The registerStatus property.
   */
  public function getRegisterStatus() {
    return $this->registerStatus;
  }

}
