<?php

/**
 * @file
 * Contains Drupal\Tests\cas\Unit\Controller\ForceLoginControllerTest.
 */

namespace Drupal\Tests\cas\Unit\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\cas\Controller\ForceLoginController;

/**
 * ForceLoginController unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Controller\ForceLoginController
 */
class ForceLoginControllerTest extends UnitTestCase {

  /**
   * The mocked CasHelper.
   *
   * @var \Drupal\cas\Service\CasHelper|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $casHelper;

  /**
   * The mocked Request Stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->requestStack = $this->getMock('\Symfony\Component\HttpFoundation\RequestStack');
    $this->casHelper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
                             ->disableOriginalConstructor()
                             ->getMock();
  }

  /**
   * Test the forcedLogin redirect.
   *
   * @covers ::forceLogin
   * @covers ::__construct
   */
  public function testForceLogin() {
    $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
    $query = $this->getMock('\Symfony\Component\HttpFoundation\ParameterBag');
    $request->query = $query;
    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->will($this->returnValue($request));
    $parameters = array('returnto' => 'node/1', 'foo' => 'bar');
    $query->expects($this->once())
      ->method('all')
      ->will($this->returnValue($parameters));
    $this->casHelper->expects($this->once())
      ->method('getServerLoginUrl')
      ->with($this->equalTo($parameters))
      ->will($this->returnValue('https://example.com'));

    $cacheableMetadata = new CacheableMetadata();
    $cacheableMetadata->addCacheTags(array(
      'config:cas.settings'
    ));
    $expected_response = new TrustedRedirectResponse('https://example.com', 302);
    $expected_response->addCacheableDependency($cacheableMetadata);

    $force_login_controller = new ForceLoginController($this->casHelper, $this->requestStack);
    $response = $force_login_controller->forceLogin();
    $this->assertEquals($expected_response, $response);
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

    $this->assertInstanceOf('\Drupal\cas\Controller\ForceLoginController', ForceLoginController::create($container));
  }

}
