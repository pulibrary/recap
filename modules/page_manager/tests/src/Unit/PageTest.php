<?php

/**
 * @file
 * Contains \Drupal\Tests\page_manager\Unit\PageTest.
 */

namespace Drupal\Tests\page_manager\Unit;

use Drupal\page_manager\Entity\Page;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Page entity.
 *
 * @coversDefaultClass \Drupal\page_manager\Entity\Page
 *
 * @group PageManager
 */
class PageTest extends UnitTestCase {

  /**
   * @covers ::isFallbackPage
   *
   * @dataProvider providerTestIsFallbackPage
   */
  public function testIsFallbackPage($id, $expected) {
    $page = $this->getMockBuilder(Page::class)
      ->setConstructorArgs([['id' => $id], 'page'])
      ->setMethods(['configFactory'])
      ->getMock();

    $config_factory = $this->getConfigFactoryStub([
      'page_manager.settings' => [
        'fallback_page' => 'fallback',
      ]]);
    $page->expects($this->once())
      ->method('configFactory')
      ->will($this->returnValue($config_factory));

    $this->assertSame($expected, $page->isFallbackPage());
  }

  /**
   * Provides test data for testIsFallbackPage().
   */
  public function providerTestIsFallbackPage() {
    $data = [];
    $data[] = ['foo', FALSE];
    $data[] = ['fallback', TRUE];
    return $data;
  }

}
