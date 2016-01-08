<?php

/**
 * @file
 * Contains Drupal\Tests\cas\Unit\Controller\LogoutControllerTest.
 */

namespace Drupal\Tests\cas\Unit\Controller;

use Drupal\Tests\UnitTestCase;
use Drupal\cas\Controller\LogoutController;

/**
 * LogoutController unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Controller\LogoutController
 */
class LogoutControllerTest extends UnitTestCase {

  /**
   * The mocked CasHelper.
   *
   * @var \Drupal\cas\Service\CasHelper|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $casHelper;

  /**
   * The mocked request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->casHelper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
                             ->disableOriginalConstructor()
                             ->getMock();
    $this->requestStack = $this->getMockBuilder('\Symfony\Component\HttpFoundation\RequestStack')
                               ->disableOriginalConstructor()
                               ->getMock();

    $this->requestStack->method('getCurrentRequest')
      ->willReturn($this->getMock('\Symfony\Component\HttpFoundation\Request'));
  }

  /**
   * Test the static create method.
   *
   * @covers ::create
   * @covers ::__construct
   */
  public function testCreate() {

    $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->expects($this->any())
      ->method('get')
      ->will($this->onConsecutiveCalls($this->casHelper, $this->requestStack));

    $this->assertInstanceOf('\Drupal\cas\Controller\LogoutController', LogoutController::create($container));
  }

  /**
   * Test the logout callback.
   *
   * @covers ::logout
   */
  public function testLogout() {
    $logout_controller = $this->getMockBuilder('Drupal\cas\Controller\LogoutController')
                              ->setConstructorArgs(array($this->casHelper, $this->requestStack))
                              ->setMethods(array('userLogout'))
                              ->getMock();
    $this->casHelper->expects($this->once())
      ->method('getServerLogoutUrl')
      ->will($this->returnValue('https://example.com/logout'));
    $logout_controller->expects($this->once())
      ->method('userLogout');
    $response = $logout_controller->logout();
    $this->assertTrue($response->isRedirect('https://example.com/logout'));
  }
}
