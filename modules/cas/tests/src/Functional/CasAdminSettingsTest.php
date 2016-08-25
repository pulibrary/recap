<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\cas\CasPropertyBag;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

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
   * @todo fix the config schema.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['administer account settings']));
  }

  /**
   * Tests Standard installation profile.
   */
  public function testCasAutoAssignedRoles() {
    $role_id = $this->drupalCreateRole([]);
    $role_id_2 = $this->drupalCreateRole([]);
    $edit = [
      'user_accounts[auto_register]' => TRUE,
      'user_accounts[auto_assigned_roles_enable]' => TRUE,
      'user_accounts[auto_assigned_roles][]' => [$role_id, $role_id_2],
    ];
    $this->drupalPostForm('/admin/config/people/cas', $edit, 'Save configuration');

    $this->assertEquals([$role_id, $role_id_2], $this->config('cas.settings')->get('user_accounts.auto_assigned_roles'));

    $cas_property_bag = new CasPropertyBag('test_cas_user_name');
    \Drupal::service('cas.login')->loginToDrupal($cas_property_bag, 'fake_ticket_string');
    $user = user_load_by_name('test_cas_user_name');
    $this->assertTrue($user->hasRole($role_id), 'The user has the auto assigned role: ' . $role_id);
    $this->assertTrue($user->hasRole($role_id_2), 'The user has the auto assigned role: ' . $role_id_2);

    Role::load($role_id_2)->delete();

    $this->assertEquals([$role_id], $this->config('cas.settings')->get('user_accounts.auto_assigned_roles'));
  }

}
