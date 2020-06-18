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
1. `cp drush/sites/example.site.yml drush/sites/recap.site.yml`
1. Uncomment the alias blocks and adjust the config values in the  `drush/sites/recap.site.yml` file to match the current remote and local drupal environments.
1. `lando drush @recap.prod sql-dump --structure-tables-list='watchdog,sessions,cas_data_login,history,captcha_sessions,cache,cache_*' --result-file=/tmp/dump.sql; scp pulsys@recap-www-prod1:/tmp/dump.sql .`
1. `lando db-import dump.sql`
1. `lando drush rsync @recap.prod:%files @recap.local:%files`
1. Copy the hash following `config_` in `sites/default/files`. Add value to `$settings['hash_salt']` in `sites/default/settings.php`. For example, if config directory in `sites/default/files` is `config_abc123`, then:
    ```
    $settings['hash_salt'] = 'abc123';
    ```
1. Copy the same hash from above and add the value to `$settings['config_sync_directory']` in `sites/default/settings.php`. For example, if config directory in `sites/default/files` is `config_abc123`, then:
    ```
    // This was pre 8.8.x
    $config_directories = array(
      CONFIG_SYNC_DIRECTORY => 'sites/default/files/config_abc123',
    );

    // This is the changed key after 8.8.x
    $settings['config_sync_directory'] = 'sites/default/files/config_abc123'
    ```
1. Create a `drush/drush.yml` file with the following
```
options:
  uri: 'http://recap.lndo.site'
```
1. `lando drush uli --name=your-username`

### NPM and Gulp

1. `cd themes/custom/recap`
1. `lando npm install`
1. `lando gulp deploy` (or any other gulp task)

## Deploying to the server

We utilize capistrano to deploy the code out to the server.  To deploy code to an existing server run
`cap <server set> deploy ` (for example `cap production deploy`).

To import a database run `cap <server set> drupal:database:import_dump SQL_DIR=<path to dump> SQL_FILE=<dump file name>`

To install code on a blank sever you must deploy and upload a database, so you need to pass the database bump variables to the deploy command `cap <server set> deploy SQL_DIR=<path to dump> SQL_FILE=<dump file name>`

To see a list of al available command run `cap -T`