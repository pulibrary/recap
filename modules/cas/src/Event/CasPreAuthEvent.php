<?php

/**
 * @file
 * Contains \Drupal\cas\Event\CasPreAuthEvent.
 */

namespace Drupal\cas\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\user\UserInterface;
use Drupal\cas\CasPropertyBag;

/**
 * Class CasPreAuthEvent.
 */
class CasPreAuthEvent extends Event {

  /**
   * The user account to be altered by this event.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The user information returned from the CAS server.
   *
   * @var \Drupal\cas\CasPropertyBag
   */
  protected $casPropertyBag;

  /**
   * Whether or not to allow this user to authenticate to Drupal.
   *
   * @var bool
   */
  public $allowLogin = TRUE;

  /**
   * Contructor.
   *
   * @param \Drupal\user\UserInterface $user_object
   *   The user object to be altered.
   * @param \Drupal\cas\CasPropertyBag $cas_property_bag
   *   The CasPropertyBag for context.
   */
  public function __construct(UserInterface $user_object, CasPropertyBag $cas_property_bag) {
    $this->user = $user_object;
    $this->casPropertyBag = $cas_property_bag;
  }

  /**
   * Return the user object of the event.
   *
   * @return \Drupal\user\UserInterface
   *   The $user property.
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Return the CasPropertyBag of the event.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   The $casPropertyBag property.
   */
  public function getCasPropertyBag() {
    return $this->casPropertyBag;
  }

}
