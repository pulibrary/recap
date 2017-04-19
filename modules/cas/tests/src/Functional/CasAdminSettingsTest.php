<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\cas\CasPropertyBag;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests CAS admin settings form.
 *
 * @group cas
 */
class CasAdminSettingsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['cas'];

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Disable strict schema cheking.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer account settings']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that access to the password reset form is disabled.
   *
   * @dataProvider restrictedPasswordEnabledProvider
   */
  public function testPasswordResetBehavior($restricted_password_enabled) {
    $edit = [
      'user_accounts[restrict_password_management]' => $restricted_password_enabled,
      'user_accounts[email_hostname]' => 'sample.com',
    ];
    $this->drupalPostForm('/admin/config/people/cas', $edit, 'Save configuration');

    // The menu router info needs to be rebuilt after saving this form so the
    // CAS menu alter runs again.
    $this->container->get('router.builder')->rebuild();

    $this->drupalLogout();
    $this->drupalGet('user/password');
    if ($restricted_password_enabled) {
      $this->assertSession()->pageTextContains(t('Access denied'));
      $this->assertSession()->pageTextNotContains(t('Reset your password'));
    }
    else {
      $this->assertSession()->pageTextNotContains(t('Access denied'));
      $this->assertSession()->pageTextContains(t('Reset your password'));
    }
  }

  /**
   * Data provider for testPasswordResetBehavior.
   */
  public function restrictedPasswordEnabledProvider() {
    return [[FALSE], [TRUE]];
  }

}
