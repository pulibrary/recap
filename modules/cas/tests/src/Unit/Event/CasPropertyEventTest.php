<?php

/**
 * @file
 * Contains Drupal\Tests\cas\Unit\Event\CasPropertyEventTest.
 */

namespace Drupal\Tests\cas\Unit\Event;

use Drupal\Tests\UnitTestCase;
use Drupal\cas\CasPropertyBag;
use Drupal\cas\Event\CasPropertyEvent;

/**
 * CasPropertyEvent unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Event\CasPropertyEvent
 */
class CasPropertyEventTest extends UnitTestCase {

  /**
   * Test the constructor.
   *
   * @covers ::__construct
   */
  public function testConstruct() {
    $bag = $this->getMockBuilder('Drupal\cas\CasPropertyBag')
                ->setConstructorArgs(array($this->randomMachineName(8)))
                ->getMock();
    $event = new CasPropertyEvent($bag);
    $this->assertEquals($bag, \PHPUnit_Framework_Assert::readAttribute($event, 'casPropertyBag'));
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
    $event = new CasPropertyEvent($bag);
    $this->assertEquals($bag, $event->getCasPropertyBag());
  }

}
