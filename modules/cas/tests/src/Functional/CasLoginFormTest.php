<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the login link on the user login form.
 *
 * @group cas
 */
class CasLoginFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['cas'];

  /**
   * Tests the login link on the user login form.
   */
  public function testLoginLinkOnLoginForm() {
    // Should be disabled by default.
    $config = $this->config('cas.settings');
    $this->assertFalse($config->get('login_link_enabled'));
    $this->assertEquals('CAS Login', $config->get('login_link_label'));
    $this->drupalGet('/user/login');
    $this->assertSession()->linkNotExists('CAS Login');

    // Enable it.
    $this->drupalLogin($this->drupalCreateUser(['administer account settings']));
    $edit = [
      'general[login_link_enabled]' => TRUE,
      'general[login_link_label]' => 'Click here to login!',
    ];
    $this->drupalPostForm('/admin/config/people/cas', $edit, 'Save configuration');
    $config = $this->config('cas.settings');
    $this->assertTrue($config->get('login_link_enabled'));
    $this->assertEquals('Click here to login!', $config->get('login_link_label'));

    // Test that it appears properly.
    $this->drupalLogout();
    $this->drupalGet('/user/login');
    $this->assertSession()->linkExists('Click here to login!');
  }

}
