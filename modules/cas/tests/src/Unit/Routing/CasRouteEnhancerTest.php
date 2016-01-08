<?php

/**
 * @file
 * Contains \Drupal\Tests\cas\Unit\Routing\CasRouteEnhancerTest.
 */

namespace Drupal\Tests\cas\Unit\Routing;

use Drupal\Tests\UnitTestCase;
use Drupal\cas\Service\CasHelper;
use Drupal\cas\Routing\CasRouteEnhancer;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * CasRouteEnhancer unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Routing\CasRouteEnhancer
 */
class CasRouteEnhancerTest extends UnitTestCase {

  /**
   * The mocked CasHelper.
   *
   * @var \Drupal\cas\Service\CasHelper|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $casHelper;

  /**
   * The mocked Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $request;

  /**
   * The mocked Route.
   *
   * @var \Symfony\Component\Routing\Route|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $route;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->casHelper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
                            ->disableOriginalConstructor()
                            ->getMock();
    $this->request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
                          ->disableOriginalConstructor()
                          ->getMock();
    $this->route = $this->getMockBuilder('\Symfony\Component\Routing\Route')
                        ->disableOriginalConstructor()
                        ->getMock();
  }

  /**
   * Test the constructor.
   *
   * @covers ::__construct
   */
  public function testConstruct() {
    $this->assertInstanceOf('\Drupal\cas\Routing\CasRouteEnhancer', new CasRouteEnhancer($this->casHelper));
  }

  /**
   * Test the applies() method.
   *
   * @covers ::applies
   *
   * @dataProvider appliesDataProvider
   */
  public function testApplies($path, $return) {
    $this->route->expects($this->once())
                ->method('getPath')
                ->willReturn($path);
    $enhancer = new CasRouteEnhancer($this->casHelper);
    $this->assertEquals($return, $enhancer->applies($this->route));
  }

  /**
   * Provides route strings and expected returns for testApplies().
   *
   * @return array
   *   Parameters.
   *
   * @see \Drupal\Tests\cas\Unit\Routing\CasRouteEnhancerTest::testApplies
   */
  public function appliesDataProvider() {
    $params[] = ['/foo', FALSE];
    $params[] = ['/user/logout', TRUE];
    return $params;
  }

  /**
   * Tests the enhance() method.
   *
   * @covers ::enhance
   *
   * @dataProvider enhanceDataProvider
   */
  public function testEnhance($config) {
    $this->casHelper->expects($this->once())
                    ->method('provideCasLogoutOverride')
                    ->willReturn($config);
    $enhancer = new CasRouteEnhancer($this->casHelper);
    $defaults = array();
    $defaults = $enhancer->enhance($defaults, $this->request);
    if ($config) {
      $this->assertArraySubset(['_controller' => '\Drupal\cas\Controller\LogoutController::logout'], $defaults);
    }
    else {
      $this->assertEmpty($defaults);
    }
  }

  /**
   * Provides configuration values for testEnhance()
   *
   * @return array
   *   Parameters.
   *
   * @see \Drupal\Tests\cas\Unit\Routing\CasRouteEnhancerTest::testEnhance
   */
  public function enhanceDataProvider() {
    $params = [[TRUE], [FALSE]];
    return $params;
  }

}
