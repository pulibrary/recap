<?php

namespace Drupal\Tests\cas\Traits;

use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Provides reusable code for tests.
 */
trait CasTestTrait {

  /**
   * Creates a CAS user and starts the CAS mock server.
   *
   * @param string $authname
   *   The CAS authentication name.
   * @param string $email
   *   The CAS user email.
   * @param string $password
   *   The CAS server password.
   * @param array $attributes
   *   (optional) Additional attributes to be added to the CAS account.
   * @param \Drupal\user\UserInterface|null $local_account
   *   (optional) A user local account. If passed, the CAS user will be linked
   *   with the local user.
   */
  protected function createCasUser($authname, $email, $password, array $attributes = [], UserInterface $local_account = NULL) {
    $cas_user = [
      'username' => $authname,
      'email' => $email,
      'password' => $password,
    ] + $attributes;
    \Drupal::service('cas_mock_server.user_manager')->addUser($cas_user);

    // Link with the local account if it has been requested.
    if ($local_account) {
      \Drupal::service('externalauth.externalauth')->linkExistingAccount($authname, 'cas', $local_account);
    }

    // Start the CAS mock server.
    \Drupal::service('cas_mock_server.server_manager')->start();
  }

  /**
   * Logs-in the user to the CAS mock server.
   *
   * @param string $email
   *   The CAS email.
   * @param string $password
   *   The CAS user password.
   */
  protected function casLogin($email, $password) {
    $query = [
      'service' => Url::fromRoute('cas.service')->setAbsolute()->toString(),
    ];
    $edit = [
      'email' => $email,
      'password' => $password,
    ];
    $this->drupalPostForm('/cas-mock-server/login', $edit, 'Log in', ['query' => $query]);
  }

}
