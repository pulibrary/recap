<?php

/**
 * @file
 * Contains Drupal\Tests\cas\Unit\Service\CasLoginTest.
 */

namespace Drupal\Tests\cas\Unit\Service;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\cas\CasPropertyBag;

/**
 * CasLogin unit tests.
 *
 * @ingroup cas
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
   * @var \Drupal\Core\Session\SessionManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $sessionManager;

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
    $this->sessionManager = $this->getMockBuilder('\Drupal\Core\Session\SessionManager')
                                 ->disableOriginalConstructor()
                                 ->getMock();
    $this->eventDispatcher = $this->getMockBuilder('\Symfony\Component\EventDispatcher\EventDispatcherInterface')
                                  ->disableOriginalConstructor()
                                  ->getMock();
  }

  /**
   * Test logging a Cas user into Drupal.
   *
   * @covers ::loginToDrupal
   * @covers ::__construct
   * @covers ::registerUser
   *
   * @dataProvider loginToDrupalDataProvider
   */
  public function testLoginToDrupal($account_auto_create, $account_exists) {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'user_accounts.auto_register' => $account_auto_create,
      ),
    ));

    $cas_login = $this->getMockBuilder('Drupal\cas\Service\CasLogin')
      ->setMethods(array('userLoadByName', 'userLoginFinalize', 'storeLoginSessionData'))
      ->setConstructorArgs(array(
        $config_factory,
        $this->entityManager,
        $this->sessionManager,
        $this->connection,
        $this->eventDispatcher,
      ))
      ->getMock();

    if ($account_auto_create && !$account_exists) {
      $entity_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
      $entity_account = $this->getMock('Drupal\user\UserInterface');
      $this->entityManager->expects($this->once())
        ->method('getStorage')
        ->will($this->returnValue($entity_storage));
      $entity_storage->expects($this->once())
        ->method('create')
        ->will($this->returnValue($entity_account));
    }

    // We cannot test actual login, so we just check if functions were called.
    $account = $this->getMockBuilder('\Drupal\user\UserInterface')
                    ->disableOriginalConstructor()
                    ->getMock();
    $cas_login->expects($this->once())
      ->method('userLoadByName')
      ->will($this->returnValue($account_exists ? $account : FALSE));
    $cas_login->expects($this->once())
      ->method('userLoginFinalize');
    $cas_login->expects($this->once())
      ->method('storeLoginSessionData');

    $property_bag = new CasPropertyBag($this->randomMachineName(8));

    $cas_login->loginToDrupal($property_bag, $this->randomMachineName(24));
  }

  /**
   * Provide parameters to testLoginToDrupal.
   *
   * @return array
   *   Parameters.
   *
   * @see \Drupal\Tests\cas\Unit\Service\CasLoginTest::testLoginToDrupal
   */
  public function loginToDrupalDataProvider() {
    /* There are three positive scenarios: 1. Account exists and autocreate
     * off, 2. Account exists and autocreate on, 3. Account does not exist, and
     * autocreate on.
     */
    return array(
      array(FALSE, TRUE),
      array(TRUE, TRUE),
      array(TRUE, FALSE),
    );
  }

  /**
   * Test exceptions thrown by loginToDrupal().
   *
   * @covers ::loginToDrupal
   * @covers ::__construct
   * @covers ::registerUser
   *
   * @dataProvider loginToDrupalExceptionDataProvider
   */
  public function testLoginToDrupalException($account_auto_create, $account_exists, $exception_type, $exception_message) {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'user_accounts.auto_register' => $account_auto_create,
      ),
    ));

    $cas_login = $this->getMockBuilder('Drupal\cas\Service\CasLogin')
      ->setMethods(array('userLoadByName', 'userLoginFinalize', 'storeLoginSessionData'))
      ->setConstructorArgs(array(
        $config_factory,
        $this->entityManager,
        $this->sessionManager,
        $this->connection,
        $this->eventDispatcher,
      ))
      ->getMock();

    if ($account_auto_create && !$account_exists) {
      $entity_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
      $this->entityManager->expects($this->once())
        ->method('getStorage')
        ->will($this->returnValue($entity_storage));
      $entity_storage->expects($this->once())
        ->method('create')
        ->will($this->throwException(new EntityStorageException()));
    }

    $account = $this->getMockBuilder('Drupal\user\UserInterface')
                    ->disableOriginalConstructor()
                    ->getMock();
    // We cannot test actual login, so we just check if functions were called.
    $cas_login->expects($this->once())
      ->method('userLoadByName')
      ->will($this->returnValue($account_exists ? $account : FALSE));

    $this->setExpectedException($exception_type, $exception_message);
    $property_bag = new CasPropertyBag($this->randomMachineName(8));
    $cas_login->loginToDrupal($property_bag, $this->randomMachineName(24));
  }

  /**
   * Provides parameters and exceptions for testLoginToDrupalException.
   *
   * @return array
   *   Parameters and exceptions
   *
   * @see \Drupal\Tests\cas\Unit\Service\CasLoginTest::testLoginToDrupalException
   */
  public function loginToDrupalExceptionDataProvider() {
    /* There are two exceptions that can be triggered: the user does not exist
     * and account autocreation is off, and user does not exist and account
     * autocreation failed.
     */
    $exception_type = '\Drupal\cas\Exception\CasLoginException';
    return array(
      array(
        FALSE,
        FALSE,
        $exception_type,
        'Cannot login, local Drupal user account does not exist.',
      ),
      array(
        TRUE,
        FALSE,
        $exception_type,
        'Error registering user: ',
      ),
    );
  }

  /**
   * Test generating exception when listeners deny access.
   *
   * @covers ::loginToDrupal
   * @covers ::__construct
   *
   * @dataProvider loginToDrupalListenerDeniedDataProvider
   */
  public function testLoginToDrupalListenerDenied(CasPropertyBag $property_bag, $exception_message) {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'user_accounts.auto_register' => TRUE,
      ),
    ));

    $cas_login = $this->getMockBuilder('Drupal\cas\Service\CasLogin')
      ->setMethods(array('userLoadByName', 'userLoginFinalize', 'storeLoginSessionData'))
      ->setConstructorArgs(array(
        $config_factory,
        $this->entityManager,
        $this->sessionManager,
        $this->connection,
        $this->eventDispatcher,
      ))
      ->getMock();
    $account = $this->getMockBuilder('Drupal\user\UserInterface')
                    ->disableOriginalConstructor()
                    ->getMock();

    $cas_login->expects($this->any())
      ->method('userLoadByName')
      ->will($this->returnValue($property_bag->getRegisterStatus() ? $account : FALSE));

    $_SESSION['cas_temp_disable'] = FALSE;
    $ticket = $this->randomMachineName(24);
    $this->setExpectedException('\Drupal\cas\Exception\CasLoginException', $exception_message);
    
    $cas_login->loginToDrupal($property_bag, $ticket);
    $this->assertEquals('TRUE', $_SESSION['cas_temp_disable']);
  }

  /**
   * Provides parameters and exceptions for testLoginToDrupalListenerDenied
   *
   * @return array
   *   Parameters and exceptions
   *
   * @see \Drupal\Tests\cas\Unit\Service\CasLoginTest::testLoginToDrupalListenerDenied
   */
  public function loginToDrupalListenerDeniedDataProvider() {
    // Test denying login access.
    $bag1 = new CasPropertyBag($this->randomMachineName(8));
    $bag1->setLoginStatus(FALSE);
    $message1 = 'Cannot login, an event listener denied access.';

    // Test denying register access.
    $bag2 = new CasPropertyBag($this->randomMachineName(8));
    $bag2->setRegisterStatus(FALSE);
    $message2 = 'Cannot register user, an event listener denied access.';

    return array(
      array($bag1, $message1),
      array($bag2, $message2),
    );
  }
}
