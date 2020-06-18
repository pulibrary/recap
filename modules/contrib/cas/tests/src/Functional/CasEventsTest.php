<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\cas\CasPropertyBag;
use Drupal\Tests\cas\Traits\CasTestTrait;

/**
 * Class CasEventsTest.
 *
 * @group cas
 */
class CasEventsTest extends CasBrowserTestBase {

  use CasTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'cas', 'cas_mock_server', 'cas_test'];

  /**
   * Tests we can use the CasPreRegisterEvent to alter user properties.
   */
  public function testSettingPropertiesOnRegistration() {
    /* The "cas_test" module includes a subscriber to CasPreRegisterEvent
     * which will prefix all auto-registered users with "testing_"
     */
    $this->drupalLogin($this->drupalCreateUser(['administer account settings']));
    $edit = [
      'user_accounts[auto_register]' => TRUE,
      'user_accounts[email_hostname]' => 'sample.com',
    ];
    $this->drupalPostForm('/admin/config/people/cas', $edit, 'Save configuration');

    $cas_property_bag = new CasPropertyBag('foo');
    \Drupal::service('cas.user_manager')->login($cas_property_bag, 'fake_ticket_string');

    $this->assertFalse(user_load_by_name('foo'), 'User with name "foo" exists, but should not.');
    /** @var \Drupal\user\UserInterface $account */
    $account = user_load_by_name('testing_foo');
    $this->assertNotFalse($account, 'User with name "testing_foo" was not found.');

    /** @var \Drupal\externalauth\AuthmapInterface $authmap */
    $authmap = \Drupal::service('externalauth.authmap');

    // Check that the external name has been registered correctly.
    $this->assertSame('foo', $authmap->get($account->id(), 'cas'));
  }

  /**
   * Tests cancelling the login process from a subscriber.
   */
  public function testLoginCancelling() {
    // Create a local user.
    $account = $this->createUser([], 'Antoine Batiste');
    // And a linked CAS user.
    $this->createCasUser('Antoine Batiste', 'antoine@example.com', 'baTistE', [], $account);
    // Place the login/logout block so that we can check if user is logged in.
    $this->placeBlock('system_menu_block:account');

    // Check the case when the subscriber didn't set a reason message.
    \Drupal::state()->set('cas_test.flag', 'cancel without message');
    $this->casLogin('antoine@example.com', 'baTistE');
    $this->assertSession()->pageTextContains('You do not have access to log in to this website. Please contact a site administrator if you believe you should have access.');
    $this->assertSession()->linkExists('Log in');

    // Check the case when the subscriber has set a reason message.
    \Drupal::state()->set('cas_test.flag', 'cancel with message');
    $this->casLogin('antoine@example.com', 'baTistE');
    $this->assertSession()->pageTextContains('Cancelled with a custom message.');
    $this->assertSession()->linkExists('Log in');
  }

}
