<?php

/**
 * @file
 * Post-update functions for CAS module.
 */

/**
 * Add prevent normal login and restrict password management error messages.
 */
function cas_post_update_8001() {
  \Drupal::configFactory()->getEditable('cas.settings')
    ->set('error_handling.message_prevent_normal_login', 'This account must log in using <a href="[cas:login-url]">CAS</a>.')
    ->set('error_handling.message_restrict_password_management', 'The requested account is associated with CAS and its password cannot be managed from this website.')
    ->save();
}
