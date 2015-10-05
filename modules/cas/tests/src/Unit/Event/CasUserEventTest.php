<?php

/**
 * @file
 * Contains Drupal\Tests\cas\Unit\Event\CasUserEventTest.
 */

namespace Drupal\Tests\cas\Unit\Event;

use Drupal\Tests\UnitTestCase;
use Drupal\cas\CasPropertyBag;
use Drupal\cas\Event\CasUserEvent;

/**
 * CasUserEvent unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Event\CasUserEvent
 */
class CasUserEventTest extends UnitTestCase {

  /**
   * Test the constructor.
   *
   * @covers ::__construct
   */
  public function testConstruct() {
    $bag = $this->getMockBuilder('Drupal\cas\CasPropertyBag')
                ->setConstructorArgs(array($this->randomMachineName(8)))
                ->getMock();
    $user = $this->getMockBuilder('Drupal\user\UserInterface')
                 ->disableOriginalConstructor()
                 ->getMock();
    $event = new CasUserEvent($user, $bag);
    $this->assertEquals($bag, \PHPUnit_Framework_Assert::readAttribute($event, 'casPropertyBag'));
    $this->assertEquals($user, \PHPUnit_Framework_Assert::readAttribute($event, 'user'));
  }

  /**
   * Test getting the user.
   *
   * @covers ::getUser
   */
  public function testGetUser() {
    $bag = $this->getMockBuilder('Drupal\cas\CasPropertyBag')
                ->setConstructorArgs(array($this->randomMachineName(8)))
                ->getMock();
    $user = $this->getMockBuilder('Drupal\user\UserInterface')
                 ->disableOriginalConstructor()
                 ->getMock();
    $event = new CasUserEvent($user, $bag);
    $this->assertEquals($user, $event->getUser());
  }

  /**
   * Test getting the property bag.
   *
   * @covers ::getCasPropertyBag
   */
  public function testGetCasPropertyBag() {
    $bag = $this->getMockBuilder('Drupal\cas\CasPropertyBag')
                ->setConstructorArgs(array($this->randomMachineName(8)))
                ->getMock();
    $user = $this->getMockBuilder('Drupal\user\UserInterface')
                 ->disableOriginalConstructor()
                 ->getMock();
    $event = new CasUserEvent($user, $bag);
    $this->assertEquals($bag, $event->getCasPropertyBag());
  }

}
