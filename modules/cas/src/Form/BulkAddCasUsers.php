<?php

namespace Drupal\cas\Form;

use Drupal\cas\Exception\CasLoginException;
use Drupal\cas\Service\CasUserManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;

/**
 * Class BulkAddCasUsers.
 *
 * A form for bulk registering CAS users.
 */
class BulkAddCasUsers extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_add_cas_users';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['intro'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('Use this form to pre-register one or more users, allowing them to log in using CAS.'),
      '#suffix' => '</p>',
    ];
    $form['cas_usernames'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CAS username(s)'),
      '#required' => TRUE,
      '#default_value' => '',
      '#description' => $this->t('Enter one username per line.'),
    ];

    $roles = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Role(s)'),
      '#options' => $roles,
      '#description' => $this->t('Optionally assign one or more roles to each user. Note that if you have CAS configured to assign roles during automatic registration on login, those will be ignored.'),
    ];
    $form['roles'][RoleInterface::AUTHENTICATED_ID] = [
      '#default_value' => TRUE,
      '#disabled' => TRUE,
    ];

    $form['extra_info'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t("Note that because CAS attributes are only available when a user has logged in, any role or field assignment based on attributes will not be available using this form."),
      '#suffix' => '</p>',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create new accounts'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $roles = array_filter($form_state->getValue('roles'));
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    $roles = array_keys($roles);

    $cas_usernames = trim($form_state->getValue('cas_usernames'));
    $cas_usernames = preg_split('/[\n\r|\r|\n]+/', $cas_usernames);

    $operations = [];
    foreach ($cas_usernames as $cas_username) {
      $cas_username = trim($cas_username);
      if (!empty($cas_username)) {
        $operations[] = [
          '\Drupal\cas\Form\BulkAddCasUsers::userAdd',
          [$cas_username, $roles],
        ];
      }
    }

    $batch = [
      'title' => $this->t('Creating CAS users...'),
      'operations' => $operations,
      'finished' => '\Drupal\cas\Form\BulkAddCasUsers::userAddFinished',
      'progress_message' => $this->t('Processed @current out of @total.'),
    ];

    batch_set($batch);
  }

  /**
   * Perform a single CAS user creation batch operation.
   *
   * Callback for batch_set().
   *
   * @param string $cas_username
   *   The CAS username, which will also become the Drupal username.
   * @param array $roles
   *   An array of roles to assign to the user.
   * @param array $context
   *   The batch context array, passed by reference.
   */
  public static function userAdd($cas_username, array $roles, array &$context) {
    $cas_user_manager = \Drupal::service('cas.user_manager');

    // Back out of an account already has this CAS username.
    $existing_uid = $cas_user_manager->getUidForCasUsername($cas_username);
    if ($existing_uid) {
      $context['results']['messages']['already_exists'][] = $cas_username;
      return;
    }

    $user_properties = [
      'roles' => $roles,
    ];

    // If the email assignment strategy is based on username and fixed hostname,
    // then we can provide an email address for the account. If the email is
    // filled out based on a CAS attribute, there's nothing we can do to fill
    // it out because we don't have access to CAS attributes without a login
    // occuring.
    $cas_settings = \Drupal::config('cas.settings');
    $email_assignment_strategy = $cas_settings->get('user_accounts.email_assignment_strategy');
    if ($email_assignment_strategy === CasUserManager::EMAIL_ASSIGNMENT_STANDARD) {
      $user_properties['mail'] = $cas_username . '@' . $cas_settings->get('user_accounts.email_hostname');
    }

    try {
      $cas_user_manager->register($cas_username, $user_properties, $cas_username);
    }
    catch (CasLoginException $e) {
      \Drupal::logger('cas')->error('CasLoginException when registering user with name %name: %e', ['%name' => $cas_username, '%e' => $e->getMessage()]);
      $context['results']['messages']['errors'][] = $cas_username;
      return;
    }

    $context['results']['messages']['created'][] = $cas_username;
  }

  /**
   * Complete CAS user creation batch process.
   *
   * Callback for batch_set().
   *
   * Consolidates message output.
   */
  public static function userAddFinished($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      if (!empty($results['messages']['errors'])) {
        $messenger->addError(t(
          'An error was encountered creating accounts for the following users (check logs for more details): %usernames',
          ['%usernames' => implode(', ', $results['messages']['errors'])]
        ));
      }
      if (!empty($results['messages']['already_exists'])) {
        $messenger->addError(t(
          'The following accounts were not registered because existing accounts are already using the usernames: %usernames',
          ['%usernames' => implode(', ', $results['messages']['already_exists'])]
        ));
      }
      if (!empty($results['messages']['created'])) {
        $messenger->addStatus(t(
          'Successfully created accounts for the following usernames: %usernames',
          ['%usernames' => implode(', ', $results['messages']['created'])]
        ));
      }
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addError(t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]));
    }
  }

}
