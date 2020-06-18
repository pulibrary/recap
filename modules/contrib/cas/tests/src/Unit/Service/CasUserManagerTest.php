<?php

namespace Drupal\Tests\cas\Unit\Service;

use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Service\CasProxyHelper;
use Drupal\cas\Service\CasUserManager;
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
   * The mocked Authmap.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

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
   * The mocked Cas Helper service.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * The CAS proxy helper.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $casProxyHelper;

  /**
   * The mocked user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->externalAuth = $this->getMockBuilder('\Drupal\externalauth\ExternalAuth')
      ->disableOriginalConstructor()
      ->getMock();
    $this->authmap = $this->getMockBuilder('\Drupal\externalauth\Authmap')
      ->disableOriginalConstructor()
      ->getMock();
    $storage = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage')
      ->setMethods(NULL)
      ->getMock();
    $this->session = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\Session')
      ->setConstructorArgs([$storage])
      ->getMock();
    $this->session->start();
    $this->connection = $this->getMockBuilder('\Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $this->eventDispatcher = $this->getMockBuilder('\Symfony\Component\EventDispatcher\EventDispatcherInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->casHelper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
      ->disableOriginalConstructor()
      ->getMock();
    $this->account = $this->getMockBuilder('Drupal\user\UserInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->casProxyHelper = $this->prophesize(CasProxyHelper::class);
  }

  /**
   * Basic scenario that user is registered.
   *
   * Create new account for a user.
   *
   * @covers ::register
   */
  public function testUserRegister() {

    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'user_accounts.auto_assigned_roles' => [],
      ],
    ]);

    $this->externalAuth
      ->method('register')
      ->willReturn($this->account);

    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->setMethods(['randomPassword'])
      ->setConstructorArgs([
        $this->externalAuth,
        $this->authmap,
        $config_factory,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
        $this->casHelper,
        $this->casProxyHelper->reveal(),
      ])
      ->getMock();

    $this->assertNotEmpty($cas_user_manager->register('test', [], 'test'), 'Successfully registered user.');
  }

  /**
   * User account doesn't exist but auto registration is disabled.
   *
   * An exception should be thrown and the user should not be logged in.
   *
   * @covers ::login
   */
  public function testUserNotFoundAndAutoRegistrationDisabled() {
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'user_accounts.auto_register' => FALSE,
      ],
    ]);

    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->setMethods(['storeLoginSessionData', 'register'])
      ->setConstructorArgs([
        $this->externalAuth,
        $this->authmap,
        $config_factory,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
        $this->casHelper,
        $this->casProxyHelper->reveal(),
      ])
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

    $this->expectException('Drupal\cas\Exception\CasLoginException', 'Cannot login, local Drupal user account does not exist.');

    $cas_user_manager->login(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * User account doesn't exist, auto reg is enabled, but listener denies.
   *
   * @covers ::login
   */
  public function testUserNotFoundAndEventListenerDeniesAutoRegistration() {
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'user_accounts.auto_register' => TRUE,
        'user_accounts.email_assignment_strategy' => CasUserManager::EMAIL_ASSIGNMENT_STANDARD,
        'user_accounts.email_hostname' => 'sample.com',
      ],
    ]);

    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->setMethods(['storeLoginSessionData', 'register'])
      ->setConstructorArgs([
        $this->externalAuth,
        $this->authmap,
        $config_factory,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
        $this->casHelper,
        $this->casProxyHelper->reveal(),
      ])
      ->getMock();

    $this->externalAuth
      ->method('load')
      ->willReturn(FALSE);

    $this->eventDispatcher
      ->method('dispatch')
      ->willReturnCallback(function ($event_type, $event) {
        if ($event instanceof CasPreRegisterEvent) {
          $event->setAllowAutomaticRegistration(FALSE);
        }
      });

    $cas_user_manager
      ->expects($this->never())
      ->method('register');

    $this->externalAuth
      ->expects($this->never())
      ->method('userLoginFinalize');

    $this->expectException('Drupal\cas\Exception\CasLoginException', 'Cannot register user, an event listener denied access.');

    $cas_user_manager->login(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * User account doesn't exist but is auto-registered and logged in.
   *
   * @dataProvider automaticRegistrationDataProvider
   *
   * @covers ::login
   */
  public function testAutomaticRegistration($email_assignment_strategy) {
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'user_accounts.auto_register' => TRUE,
        'user_accounts.email_assignment_strategy' => $email_assignment_strategy,
        'user_accounts.email_hostname' => 'sample.com',
        'user_accounts.email_attribute' => 'email',
      ],
    ]);

    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->setMethods(['storeLoginSessionData', 'randomPassword'])
      ->setConstructorArgs([
        $this->externalAuth,
        $this->authmap,
        $config_factory,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
        $this->casHelper,
        $this->casProxyHelper->reveal(),
      ])
      ->getMock();

    $this->externalAuth
      ->method('load')
      ->willReturn(FALSE);

    $this->account
      ->method('isactive')
      ->willReturn(TRUE);

    // The email address assigned to the user differs depending on the settings.
    // If CAS is configured to use "standard" assignment, it should combine the
    // username with the specifed email hostname. If it's configured to use
    // "attribute" assignment, it should use the value of the specified CAS
    // attribute.
    if ($email_assignment_strategy === CasUserManager::EMAIL_ASSIGNMENT_STANDARD) {
      $expected_assigned_email = 'test@sample.com';
    }
    else {
      $expected_assigned_email = 'test_email@foo.com';
    }

    $this->externalAuth
      ->expects($this->once())
      ->method('register')
      ->with('test', 'cas', [
        'name' => 'test',
        'mail' => $expected_assigned_email,
        'pass' => NULL,
      ])
      ->willReturn($this->account);

    $this->externalAuth
      ->expects($this->once())
      ->method('userLoginFinalize')
      ->willReturn($this->account);

    $cas_property_bag = new CasPropertyBag('test');
    $cas_property_bag->setAttributes(['email' => 'test_email@foo.com']);

    $cas_user_manager->login($cas_property_bag, 'ticket');
  }

  /**
   * A data provider for testing automatic user registration.
   *
   * @return array
   *   The two different email assignment strategies.
   */
  public function automaticRegistrationDataProvider() {
    return [
      [CasUserManager::EMAIL_ASSIGNMENT_STANDARD],
      [CasUserManager::EMAIL_ASSIGNMENT_ATTRIBUTE],
    ];
  }

  /**
   * An event listener prevents the user from logging in.
   *
   * @covers ::login
   */
  public function testEventListenerPreventsLogin() {
    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->setMethods(['storeLoginSessionData'])
      ->setConstructorArgs([
        $this->externalAuth,
        $this->authmap,
        $this->getConfigFactoryStub(),
        $this->session,
        $this->connection,
        $this->eventDispatcher,
        $this->casHelper,
        $this->casProxyHelper->reveal(),
      ])
      ->getMock();

    $this->account
      ->method('isactive')
      ->willReturn(TRUE);

    $this->externalAuth
      ->method('load')
      ->willReturn($this->account);

    $this->eventDispatcher
      ->method('dispatch')
      ->willReturnCallback(function ($event_type, $event) {
        if ($event instanceof CasPreLoginEvent) {
          $event->cancelLogin();
        }
      });

    $cas_user_manager
      ->expects($this->never())
      ->method('storeLoginSessionData');

    $this->externalAuth
      ->expects($this->never())
      ->method('userLoginFinalize');

    $this->expectException('Drupal\cas\Exception\CasLoginException', 'Cannot login, an event listener denied access.');

    $cas_user_manager->login(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * A user is able to login when their account exists.
   *
   * @covers ::login
   */
  public function testExistingAccountIsLoggedIn() {
    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->setMethods(['storeLoginSessionData'])
      ->setConstructorArgs([
        $this->externalAuth,
        $this->authmap,
        $this->getConfigFactoryStub(),
        $this->session,
        $this->connection,
        $this->eventDispatcher,
        $this->casHelper,
        $this->casProxyHelper->reveal(),
      ])
      ->getMock();

    $this->account
      ->method('isActive')
      ->willReturn(TRUE);

    $this->externalAuth
      ->method('load')
      ->willReturn($this->account);

    $cas_user_manager
      ->expects($this->once())
      ->method('storeLoginSessionData');

    $this->externalAuth
      ->expects($this->once())
      ->method('userLoginFinalize')
      ->willReturn($this->account);

    $attributes = ['attr1' => 'foo', 'attr2' => 'bar'];
    $this->session
      ->method('set')
      ->withConsecutive(
        ['is_cas_user', TRUE],
        ['cas_username', 'test']
      );

    $propertyBag = new CasPropertyBag('test');
    $propertyBag->setAttributes($attributes);

    $cas_user_manager->login($propertyBag, 'ticket');
  }

  /**
   * Blockers users cannot log in.
   *
   * @covers ::login
   */
  public function testBlockedAccountIsNotLoggedIn() {
    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->setMethods(['storeLoginSessionData'])
      ->setConstructorArgs([
        $this->externalAuth,
        $this->authmap,
        $this->getConfigFactoryStub(),
        $this->session,
        $this->connection,
        $this->eventDispatcher,
        $this->casHelper,
        $this->casProxyHelper->reveal(),
      ])
      ->getMock();

    $this->account
      ->method('isactive')
      ->willReturn(FALSE);
    $this->account
      ->method('getaccountname')
      ->willReturn('user');

    $this->externalAuth
      ->method('load')
      ->willReturn($this->account);

    $this->externalAuth
      ->expects($this->never())
      ->method('userLoginFinalize');

    $this->expectException('Drupal\cas\Exception\CasLoginException', 'The username user has not been activated or is blocked.');

    $this->session
      ->method('set')
      ->withConsecutive(
          ['is_cas_user', TRUE],
          ['cas_username', 'test']
      );

    $propertyBag = new CasPropertyBag('test');
    $cas_user_manager->login($propertyBag, 'ticket');
  }

}
