<?php

declare(strict_types=1);

namespace Drupal\Tests\health_check\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests /health route.
 *
 * @group health_check
 */
class RouteTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['health_check'];

  /**
   * Tests /health route.
   */
  public function testRoute() : void {
    $this->drupalGet('/health');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure /health is available in maintenance mode.
    \Drupal::state()->set('system.maintenance_mode', TRUE);
    Cache::invalidateTags(['rendered']);

    $this->drupalGet('/health');
    $this->assertSession()->statusCodeEquals(200);

    // Health check should return a UNIX timestamp. Make sure the response is
    // not cached.
    $timestamp = (int) $this->getSession()->getPage()->getContent();
    $this->assertTrue($timestamp > 1000000000);
    sleep(2);

    $this->drupalGet('/health');
    $newTimestamp = (int) $this->getSession()->getPage()->getContent();
    $this->assertTrue($newTimestamp > $timestamp);
  }

}
