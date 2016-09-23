<?php

namespace Drupal\Tests\cas\Unit\Service;

use Drupal\cas\Event\CasPreAuthEvent;
use Drupal\cas\Event\CasUserLoadEvent;
use Drupal\Tests\UnitTestCase;
use Drupal\cas\CasPropertyBag;

/**
 * CasUserManager unit tests.
 *
 * @ingroup cas
 *
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Service\CasUserManager
 */
class CasUserManagerTest extends UnitTestCase {

  /**
   * The mocked External Auth manager.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalAuth;

  /**
   * The mocked Entity Manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked session manager.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $session;

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $connection;

  /**
   * The mocked event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $eventDispatcher;

  /**
   * The mocked user manager.
   *
   * @var \Drupal\cas\Service\CasUserManager
   */
  protected $userManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->externalAuth = $this->getMockBuilder('\Drupal\externalauth\ExternalAuth')
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
    $this->connection = $this->getMockBuilder('\Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $this->eventDispatcher = $this->getMockBuilder('\Symfony\Component\EventDispatcher\EventDispatcherInterface')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Basic scenario that user is registered.
   *
   * Create new account for a user.
   *
   * @covers ::register
   */
  public function testUserRegister() {
    $account = $this->getMockBuilder('Drupal\user\UserInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->externalAuth
      ->method('register')
      ->willReturn($account);

    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->setMethods(NULL)
      ->setConstructorArgs(array(
        $this->externalAuth,
        $this->getConfigFactoryStub(),
        $this->session,
        $this->connection,
        $this->eventDispatcher,
      ))
      ->getMock();

    $this->assertNotEmpty($cas_user_manager->register('test'), 'Successfully registered user.');
  }

  /**
   * User account doesn't exist but auto registration is disabled.
   *
   * An exception should be thrown and the user should not be logged in.
   *
   * @covers ::login
   */
  public function testUserNotFoundAndAutoRegistrationDisabled() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'user_accounts.auto_register' => FALSE,
      ),
    ));

    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->setMethods(array('storeLoginSessionData'))
      ->setConstructorArgs(array(
        $this->externalAuth,
        $config_factory,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
      ))
      ->getMock();

    $this->externalAuth
      ->method('load')
      ->willReturn(FALSE);

    $cas_user_manager
      ->expects($this->never())
      ->method('register');

    $this->externalAuth
      ->expects($this->never())
      ->method('userLoginFinalize');

    $this->setExpectedException('Drupal\cas\Exception\CasLoginException', 'Cannot login, local Drupal user account does not exist.');

    $cas_user_manager->login(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * User account doesn't exist, auto reg is enabled, but listener denies.
   *
   * @covers ::login
   */
  public function testUserNotFoundAndEventListenerDeniesAutoRegistration() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'user_accounts.auto_register' => TRUE,
      ),
    ));

    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->setMethods(array('storeLoginSessionData'))
      ->setConstructorArgs(array(
        $this->externalAuth,
        $config_factory,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
      ))
      ->getMock();

    $this->externalAuth
      ->method('load')
      ->willReturn(FALSE);

    $this->eventDispatcher
      ->method('dispatch')
      ->willReturnCallback(function ($event_type, $event) {
        if ($event instanceof CasUserLoadEvent) {
          $event->allowAutoRegister = FALSE;
        }
      });

    $cas_user_manager
      ->expects($this->never())
      ->method('register');

    $this->externalAuth
      ->expects($this->never())
      ->method('userLoginFinalize');

    $this->setExpectedException('Drupal\cas\Exception\CasLoginException', 'Cannot register user, an event listener denied access.');

    $cas_user_manager->login(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * User account doesn't exist but is auto-registered and logged in.
   *
   * @covers ::login
   */
  public function testUserNotFoundAndIsRegisteredBeforeLogin() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'user_accounts.auto_register' => TRUE,
      ),
    ));

    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->setMethods(array('register', 'storeLoginSessionData'))
      ->setConstructorArgs(array(
        $this->externalAuth,
        $config_factory,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
      ))
      ->getMock();

    $this->externalAuth
      ->method('load')
      ->willReturn(FALSE);

    $account = $this->getMockBuilder('Drupal\user\UserInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $cas_user_manager
      ->method('register')
      ->willReturn($account);

    $this->externalAuth
      ->expects($this->once())
      ->method('userLoginFinalize');

    $cas_user_manager->login(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * An event listener prevents the user from logging in.
   *
   * @covers ::login
   */
  public function testEventListenerPreventsLogin() {
    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->setMethods(array('storeLoginSessionData'))
      ->setConstructorArgs(array(
        $this->externalAuth,
        $this->getConfigFactoryStub(),
        $this->session,
        $this->connection,
        $this->eventDispatcher,
      ))
      ->getMock();

    $account = $this->getMockBuilder('Drupal\user\UserInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->externalAuth
      ->method('load')
      ->willReturn($account);

    $this->eventDispatcher
      ->method('dispatch')
      ->willReturnCallback(function ($event_type, $event) {
        if ($event instanceof CasPreAuthEvent) {
          $event->allowLogin = FALSE;
        }
      });

    $cas_user_manager
      ->expects($this->never())
      ->method('storeLoginSessionData');

    $this->externalAuth
      ->expects($this->never())
      ->method('userLoginFinalize');

    $this->setExpectedException('Drupal\cas\Exception\CasLoginException', 'Cannot login, an event listener denied access.');

    $cas_user_manager->login(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * An event listener alters username before attempting to load user.
   *
   * @covers ::login
   */
  public function testEventListenerChangesCasUsername() {
    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->setMethods(array('storeLoginSessionData'))
      ->setConstructorArgs(array(
        $this->externalAuth,
        $this->getConfigFactoryStub(),
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

    $this->externalAuth
      ->method('load')
      ->with('foobar')
      ->willReturn($account);

    $this->externalAuth
      ->expects($this->once())
      ->method('userLoginFinalize');

    $cas_user_manager->login(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * A user is able to login when their account exists.
   *
   * @covers ::login
   */
  public function testExistingAccountIsLoggedIn() {
    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->setMethods(array('storeLoginSessionData'))
      ->setConstructorArgs(array(
        $this->externalAuth,
        $this->getConfigFactoryStub(),
        $this->session,
        $this->connection,
        $this->eventDispatcher,
      ))
      ->getMock();

    $account = $this->getMockBuilder('Drupal\user\UserInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->externalAuth
      ->method('load')
      ->willReturn($account);

    $cas_user_manager
      ->expects($this->once())
      ->method('storeLoginSessionData');

    $this->externalAuth
      ->expects($this->once())
      ->method('userLoginFinalize');

    $cas_user_manager->login(new CasPropertyBag('test'), 'ticket');
  }

}
