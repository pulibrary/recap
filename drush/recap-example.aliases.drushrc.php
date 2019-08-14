<?php

/**
 * Local alias
 * Set the root and site_path values to point to your local site
 */
// $aliases['local'] = array(
//   'root' => '/app', // Path to project on local machine
//   'uri'  => 'http://recap.lndo.site',
//   'path-aliases' => array(
//     '%dump-dir' => '/tmp',
//     '%files' => 'sites/default/files',
//   ),
// );

/**
 * Production alias
 * Set each option to match your configuration
 */
// $aliases['prod'] = array (
//   'uri' => 'https://recap.princeton.edu',
//   'root' => '', // Add root
//   'remote-user' => '', // Add user
//   'remote-host' => '', // Add host
//   'ssh-options' => '-o PasswordAuthentication=no -i .ssh/id_rsa', // Add ssh
//   'path-aliases' => array(
//     '%dump-dir' => '/tmp',
//   ),
//   'source-command-specific' => array (
//     'sql-sync' => array (
//       'no-cache' => TRUE,
//       'structure-tables-key' => 'common',
//     ),
//   ),
//   'command-specific' => array (
//     'sql-sync' => array (
//       'sanitize' => TRUE,
//       'no-ordered-dump' => TRUE,
//       'structure-tables' => array(
//        // You can add more tables which contain data to be ignored by the database dump
//         'common' => array('cache', 'cache_filter', 'cache_menu', 'cache_page', 'history', 'sessions', 'watchdog'),
//       ),
//     ),
//   ),
// );
?>