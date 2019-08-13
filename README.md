# ReCAP

## Local development with Lando

1. `git clone git@github.com:pulibrary/recap.git`
1. `cp sites/default/default.settings.php sites/default/settings.php`
1. In your local `sites/default/settings.php` file include the following lando-style db config values:

    ```
    $databases = array (
      'default' =>
      array (
        'default' =>
        array (
          'database' => 'drupal8',
          'username' => 'drupal8',
          'password' => 'drupal8',
          'host' => 'database',
          'port' => '3306',
          'driver' => 'mysql',
          'prefix' => '',
        ),
      ),
    );
    ```
1. Add the following useful local development configuration to the end of `sites/default/settings.php`
    ```
    /* Overrides for the local environment */
    $conf['securepages_enable'] = 0;
    /* This should be set in your php.ini file */
    ini_set('memory_limit', '1G');
    /* Turn off all caching */
    $conf['css_gzip_compression'] = FALSE;
    $conf['js_gzip_compression'] = FALSE;
    $conf['cache'] = 0;
    $conf['block_cache'] = 0;
    $conf['preprocess_css'] = 0;
    $conf['preprocess_js'] = 0;
    /* end cache settings */
    /* Turn on theme debugging. Injects the path to every Template utilized in the HTML source. */
    $conf['theme_debug'] = TRUE;

    /* Makes sure jquery is loaded on every page */
    /* set to false in production */
    $conf['javascript_always_use_jquery'] = TRUE;

    $settings['trusted_host_patterns'] = [
      '^recap.lndo.site$',
    ];
    ```
1. `mkdir .ssh` # excluded from version control
1. `cp $HOME/.ssh/id_rsa .ssh/.`
1. `cp $HOME/.ssh/id_rsa.pub .ssh/.` // key should be registered in princeton_ansible deploy role
1. `lando start`
1. `cp drush/recap-example.aliases.drushrc.php drush/recap.aliases.drushrc.php`
1. Adjust the config values in the  `drush/recap.aliases.drushrc.php` file to match the current remote drupal environment
    ```
    $aliases['prod'] = array (
      'uri' => 'https://recap.princeton.edu',
      'root' => '', // Add root
      'remote-user' => 'deploy', // Add user
      'remote-host' => 'app-server-name', // Add app server host name
      'ssh-options' => '-o PasswordAuthentication=no -i .ssh/id_rsa',
      'path-aliases' => array(
        '%dump-dir' => '/tmp',
      ),
      'source-command-specific' => array (
        'sql-sync' => array (
          'no-cache' => TRUE,
          'structure-tables-key' => 'common',
        ),
      ),
      'command-specific' => array (
        'sql-sync' => array (
          'sanitize' => TRUE,
          'no-ordered-dump' => TRUE,
          'structure-tables' => array(
            // You can add more tables which contain data to be ignored by the database dump
            'common' => array('cache', 'cache_*', 'history', 'sessions', 'watchdog', 'cas_data_login', 'captcha_sessions'),
          ),
        ),
      ),
    );
    ```
1. Uncomment the alias block for the local lando site
    ```
    $aliases['local'] = array(
      'root' => '/app', // Path to project on local machine
      'uri'  => 'http://recap.lndo.site',
      'path-aliases' => array(
        '%dump-dir' => '/tmp',
        '%files' => 'sites/default/files',
      ),
    );
    ```
1. `lando drush @recap.prod sql-dump --structure-tables-list='watchdog,sessions,cas_data_login,history,captcha_sessions,cache,cache_*' --result-file=/tmp/dump.sql; scp pulsys@libraryphp:/tmp/dump.sql .` // Change @libraryphp based on your ssh alias
1. `lando db-import dump.sql`
1. `lando drush rsync @recap.prod:%files @recap.local:%files`
1. Copy the hash following `config_` in `sites/default/files`. Add value to `$settings['hash_salt']` in `sites/default/settings.php`. For example, if config directory in `sites/default/files` is `config_abc123`, then:
    ```
    $settings['hash_salt'] = 'abc123';
    ```
1. Copy the same has from above and add the value to the `$config_directories` array in `sites/default/settings.php`. For example, if config directory in `sites/default/files` is `config_abc123`, then:
    ```
    $config_directories = array(
      CONFIG_SYNC_DIRECTORY => 'sites/default/files/config_abc123',
    );
    ```
1. `lando drush @recap.local uli your-username`

### NPM and Gulp

1. `cd themes/custom/recap`
1. `lando npm install`
1. `lando gulp deploy` (or any other gulp task)

### Solr / Search API

1. In your browser, go to `http://recap.lndo.site/admin/config/search/search_api/server/recap_solr_core/edit`
1. Edit **Solr host** to have the value of `search`
1. Edit **Solr path** to have a value of `/solr/recap`
1. Clear the search index in the browser on the view page (you should be there after the edit) `http://recap.lndo.site/admin/config/search/search_api/server/recap_solr_core` and click `Delete all indexed data` button on the bottom left of the page
1. `lando drush search-api-index` will index all content to the local solr index
1. `lando drush cc all` will update the caches to show the data