<?php

/**
 * @file
 * Contains Drupal\Tests\cas\Unit\Service\CasLoginTest.
 */

namespace Drupal\Tests\cas\Unit\Service;

use Drupal\cas\Event\CasPreAuthEvent;
use Drupal\cas\Event\CasUserLoadEvent;
use Drupal\Tests\UnitTestCase;
use Drupal\cas\CasPropertyBag;

/**
 * CasLogin unit tests.
 *
 * @ingroup cas
 *
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Service\CasLogin
 */
class CasLoginTest extends UnitTestCase {

  /**
   * The mocked Entity Manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $connection;

  /**
   * The mocked session manager.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $session;

  /**
   * The mocked event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->connection = $this->getMockBuilder('\Drupal\Core\Database\Connection')
                             ->disableOriginalConstructor()
                             ->getMock();
    $storage = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage')
                    ->setMethods(NULL)
                    ->getMock();
    $this->session = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\Session')
                          ->setConstructorArgs(array($storage))
                          ->setMethods(NULL)
                          ->getMock();
    $this->session->start();
    $this->eventDispatcher = $this->getMockBuilder('\Symfony\Component\EventDispatcher\EventDispatcherInterface')
                                  ->disableOriginalConstructor()
                                  ->getMock();
  }

  /**
   * Basic scenario that this class can log a user in.
   *
   * Assumes that an account with this username already exists.
   *
   * @covers ::loginToDrupal
   */
  public function testExistingAccountIsLoggedIn() {
    $cas_login = $this->getMockBuilder('Drupal\cas\Service\CasLogin')
      ->setMethods(array('userLoadByName', 'userLoginFinalize', 'storeLoginSessionData', 'randomPassword'))
      ->setConstructorArgs(array(
        $this->getConfigFactoryStub(),
        $this->entityManager,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
      ))
      ->getMock();

    $account = $this->getMockBuilder('Drupal\user\UserInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $cas_login
      ->method('userLoadByName')
      ->with('test')
      ->willReturn($account);

    $cas_login
      ->expects($this->once())
      ->method('userLoginFinalize');

    $cas_login
      ->expects($this->once())
      ->method('storeLoginSessionData');

    $cas_login->loginToDrupal(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * User account doesn't exist but auto registration is disabled.
   *
   * An exception should be thrown and the user should not be logged in.
   *
   * @covers ::loginToDrupal
   */
  public function testUserNotFoundAndAutoRegistrationDisabled() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'user_accounts.auto_register' => FALSE,
      ),
    ));

    $cas_login = $this->getMockBuilder('Drupal\cas\Service\CasLogin')
      ->setMethods(array('userLoadByName', 'randomPassword'))
      ->setConstructorArgs(array(
        $config_factory,
        $this->entityManager,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
      ))
      ->getMock();

    $cas_login
      ->method('userLoadByName')
      ->willReturn(FALSE);

    $cas_login
      ->expects($this->never())
      ->method('registerUser');

    $cas_login
      ->expects($this->never())
      ->method('userLoginFinalize');

    $this->setExpectedException('Drupal\cas\Exception\CasLoginException', 'Cannot login, local Drupal user account does not exist.');

    $cas_login->loginToDrupal(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * User account doesn't exist, but event listener prevents auto reg.
   *
   * @covers ::loginToDrupal
   */
  public function testUserNotFoundAndEventListenerDeniesAutoRegistration() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'user_accounts.auto_register' => TRUE,
      ),
    ));

    $cas_login = $this->getMockBuilder('Drupal\cas\Service\CasLogin')
      ->setMethods(array('userLoadByName'))
      ->setConstructorArgs(array(
        $config_factory,
        $this->entityManager,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
      ))
      ->getMock();

    $cas_login
      ->method('userLoadByName')
      ->willReturn(FALSE);

    $this->eventDispatcher
      ->method('dispatch')
      ->willReturnCallback(function ($event_type, $event) {
        if ($event instanceof CasUserLoadEvent) {
          $event->allowAutoRegister = FALSE;
        }
      });

    $cas_login
      ->expects($this->never())
      ->method('registerUser');

    $cas_login
      ->expects($this->never())
      ->method('userLoginFinalize');

    $this->setExpectedException('Drupal\cas\Exception\CasLoginException', 'Cannot register user, an event listener denied access.');

    $cas_login->loginToDrupal(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * User account doesn't exist but is auto-registered and logged in.
   *
   * @covers ::loginToDrupal
   */
  public function testUserNotFoundAndIsRegisteredBeforeLogin() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'user_accounts.auto_register' => TRUE,
      ),
    ));

    $cas_login = $this->getMockBuilder('Drupal\cas\Service\CasLogin')
      ->setMethods(array('userLoadByName', 'registerUser', 'userLoginFinalize', 'storeLoginSessionData'))
      ->setConstructorArgs(array(
        $config_factory,
        $this->entityManager,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
      ))
      ->getMock();

    $cas_login
      ->method('userLoadByName')
      ->willReturn(FALSE);

    $account = $this->getMockBuilder('Drupal\user\UserInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $cas_login
      ->expects($this->once())
      ->method('registerUser')
      ->willReturn($account);

    $cas_login
      ->expects($this->once())
      ->method('userLoginFinalize');

    $cas_login
      ->expects($this->once())
      ->method('storeLoginSessionData');

    $cas_login->loginToDrupal(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * An event listener prevents the user from logging in.
   *
   * @covers ::loginToDrupal
   */
  public function testEventListenerPreventsLogin() {
    $cas_login = $this->getMockBuilder('Drupal\cas\Service\CasLogin')
      ->setMethods(array('userLoadByName'))
      ->setConstructorArgs(array(
        $this->getConfigFactoryStub(),
        $this->entityManager,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
      ))
      ->getMock();

    $account = $this->getMockBuilder('Drupal\user\UserInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $cas_login
      ->method('userLoadByName')
      ->willReturn($account);

    $this->eventDispatcher
      ->method('dispatch')
      ->willReturnCallback(function ($event_type, $event) {
        if ($event instanceof CasPreAuthEvent) {
          $event->allowLogin = FALSE;
        }
      });

    $cas_login
      ->expects($this->never())
      ->method('userLoginFinalize');

    $this->setExpectedException('Drupal\cas\Exception\CasLoginException', 'Cannot login, an event listener denied access.');

    $cas_login->loginToDrupal(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * An event listener alters username before attempting to load user.
   *
   * @covers ::loginToDrupal
   */
  public function testEventListenerChangesCasUsername() {
    $cas_login = $this->getMockBuilder('Drupal\cas\Service\CasLogin')
      ->setMethods(array('userLoadByName', 'userLoginFinalize', 'storeLoginSessionData'))
      ->setConstructorArgs(array(
        $this->getConfigFactoryStub(),
        $this->entityManager,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
      ))
      ->getMock();

    $this->eventDispatcher
      ->method('dispatch')
      ->willReturnCallback(function ($event_type, $event) {
        if ($event instanceof CasUserLoadEvent) {
          $event->getCasPropertyBag()->setUsername('foobar');
        }
      });

    $account = $this->getMockBuilder('Drupal\user\UserInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $cas_login
      ->method('userLoadByName')
      ->with('foobar')
      ->willReturn($account);

    $cas_login
      ->expects($this->once())
      ->method('userLoginFinalize');

    $cas_login
      ->expects($this->once())
      ->method('storeLoginSessionData');

    $cas_login->loginToDrupal(new CasPropertyBag('test'), 'ticket');
  }

}
