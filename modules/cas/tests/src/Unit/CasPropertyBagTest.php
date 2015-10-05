<?php

/**
 * @file
 * Contains Drupal\Tests\cas\Unit\CasPropertyBagTest.
 */

namespace Drupal\Tests\cas\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\cas\CasPropertyBag;

/**
 * CasPropertyBag unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\CasPropertyBag
 */
class CasPropertyBagTest extends UnitTestCase {

  /**
   * Test constructing a bag with a username.
   *
   * @covers ::__construct
   */
  public function testConstruct() {
    $name = $this->randomMachineName(8);
    $bag = new CasPropertyBag($name);
    $this->assertEquals($name, \PHPUnit_Framework_Assert::readAttribute($bag, 'username'));
  }

  /**
   * Test setting a username.
   *
   * @covers ::setUsername
   */
  public function testSetUsername() {
    $bag = new CasPropertyBag($this->randomMachineName(8));
    $new_name = $this->randomMachineName(8);
    $bag->setUsername($new_name);
    $this->assertEquals($new_name, \PHPUnit_Framework_Assert::readAttribute($bag, 'username'));
    $this->assertEquals(TRUE, \PHPUnit_Framework_Assert::readAttribute($bag, 'loginStatus'));
    $this->assertEquals(TRUE, \PHPUnit_Framework_Assert::readAttribute($bag, 'registerStatus'));
  }

  /**
   * Test setting a proxy granting ticket.
   *
   * @covers ::setPgt
   */
  public function testSetPgt() {
    $bag = new CasPropertyBag($this->randomMachineName(8));
    $pgt = $this->randomMachineName(24);
    $bag->setPgt($pgt);
    $this->assertEquals($pgt, \PHPUnit_Framework_Assert::readAttribute($bag, 'pgt'));
  }

  /**
   * Test setting the attributes array.
   *
   * @covers ::setAttributes
   */
  public function testSetAttributes() {
    $bag = new CasPropertyBag($this->randomMachineName(8));
    $attributes = array(
      'foo' => array('bar'),
      'baz' => array('quux, foobar'),
    );
    $bag->setAttributes($attributes);
    $this->assertEquals($attributes, \PHPUnit_Framework_Assert::readAttribute($bag, 'attributes'));
  }

  /**
   * Test setting the login status.
   *
   * @covers ::setLoginStatus
   */
  public function testSetLoginStatus() {
    $bag = new CasPropertyBag($this->randomMachineName(8));
    $bag->setLoginStatus(FALSE);
    $this->assertEquals(FALSE, \PHPUnit_Framework_Assert::readAttribute($bag, 'loginStatus'));
  }

  /**
   * Test setting the register status.
   *
   * @covers ::setRegisterStatus
   */
  public function testSetRegisterStatus() {
    $bag = new CasPropertyBag($this->randomMachineName(8));
    $bag->setRegisterStatus(FALSE);
    $this->assertEquals(FALSE, \PHPUnit_Framework_Assert::readAttribute($bag, 'registerStatus'));
  }

  /**
   * Test getting the username.
   *
   * @covers ::getUsername
   */
  public function testGetUsername() {
    $bag = new CasPropertyBag($this->randomMachineName(8));
    $reflection = new \ReflectionClass($bag);
    $property = $reflection->getProperty('username');
    $property->setAccessible(TRUE);
    $new_name = $this->randomMachineName(8);
    $property->setValue($bag, $new_name);
    $this->assertEquals($new_name, $bag->getUsername());
  }

  /**
   * Test getting the proxy granting ticket.
   *
   * @covers ::getPgt
   */
  public function testGetPgt() {
    $bag = new CasPropertyBag($this->randomMachineName(8));
    $reflection = new \ReflectionClass($bag);
    $property = $reflection->getProperty('pgt');
    $property->setAccessible(TRUE);
    $pgt = $this->randomMachineName(24);
    $property->setValue($bag, $pgt);
    $this->assertEquals($pgt, $bag->getPgt());
  }

  /**
   * Test getting the attributes.
   *
   * @covers ::getAttributes
   */
  public function testGetAttributes() {
    $bag = new CasPropertyBag($this->randomMachineName(8));
    $reflection = new \ReflectionClass($bag);
    $property = $reflection->getProperty('attributes');
    $property->setAccessible(TRUE);
    $attributes = array(
      'foo' => array('bar'),
      'baz' => array('quux', 'foobar'),
    );
    $property->setValue($bag, $attributes);
    $this->assertEquals($attributes, $bag->getAttributes());
  }

  /**
   * Test getting the login status.
   *
   * @covers ::getLoginStatus
   */
  public function testGetLoginStatus() {
    $bag = new CasPropertyBag($this->randomMachineName(8));
    $reflection = new \ReflectionClass($bag);
    $property = $reflection->getProperty('loginStatus');
    $property->setAccessible(TRUE);
    $property->setValue($bag, FALSE);
    $this->assertEquals(FALSE, $bag->getLoginStatus());
  }
    
  /**
   * Test getting the register status.
   *
   * @covers ::getRegisterStatus
   */
  public function testGetRegisterStatus() {
    $bag = new CasPropertyBag($this->randomMachineName(8));
    $reflection = new \ReflectionClass($bag);
    $property = $reflection->getProperty('registerStatus');
    $property->setAccessible(TRUE);
    $property->setValue($bag, FALSE);
    $this->assertEquals(FALSE, $bag->getRegisterStatus());
  }
}
