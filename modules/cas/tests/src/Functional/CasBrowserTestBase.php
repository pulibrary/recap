<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the CAS forced login controller.
 *
 * @group cas
 */
abstract class CasBrowserTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['cas'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tell mink not to automatically follow redirects.
   */
  protected function disableRedirects()
  {
    $this->getSession()->getDriver()->getClient()->followRedirects(FALSE);
  }

  protected function enabledRedirects()
  {
    $this->getSession()->getDriver()->getClient()->followRedirects(TRUE);
  }

  /**
   * Helper function for constructing an expected service URL.
   *
   * Any parameters passed into the optional array will be appended to the
   * service URL.
   *
   * @param array $service_url_params
   * @return string
   */
  protected function buildServiceUrlWithParams(array $service_url_params = []) {
    $service_url = $this->baseUrl . '/casservice';
    if (!empty($service_url_params)) {
      $encoded_params = UrlHelper::buildQuery($service_url_params);
      $service_url .= '?' . $encoded_params;
    }
    return $service_url;
  }
}
