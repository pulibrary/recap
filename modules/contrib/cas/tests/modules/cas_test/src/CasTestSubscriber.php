<?php

namespace Drupal\cas_test;

use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Service\CasHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CasTestSubscriber.
 */
class CasTestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CasHelper::EVENT_PRE_REGISTER][] = ['onPreRegister', 0];
    $events[CasHelper::EVENT_PRE_LOGIN][] = ['onPreLogin', 0];
    return $events;
  }

  /**
   * Change the username of the user being registered.
   *
   * @param \Drupal\cas\Event\CasPreRegisterEvent $event
   *   The event.
   */
  public function onPreRegister(CasPreRegisterEvent $event) {
    // Add a prefix of "testing_" to the CAS username.
    $username = $event->getDrupalUsername();
    $new_username = 'testing_' . $username;
    $event->setDrupalUsername($new_username);
  }

  /**
   * Cancels the login.
   *
   * @param \Drupal\cas\Event\CasPreLoginEvent $event
   */
  public function onPreLogin(CasPreLoginEvent $event) {
    $flag = \Drupal::state()->get('cas_test.flag');
    if ($flag === 'cancel without message') {
      $event->cancelLogin();
    }
    elseif ($flag === 'cancel with message') {
      $event->cancelLogin('Cancelled with a custom message.');
    }
  }

}
