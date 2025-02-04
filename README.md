# ReCAP

***This Project has been archived. This codebase is no longer maintained.***

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
          'database' => 'drupal9',
          'username' => 'drupal9',
          'password' => 'drupal9',
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

    $settings['install_profile'] = 'standard';
    ```
1. `mkdir .ssh` # excluded from version control
1. `cp $HOME/.ssh/id_ed25519 .ssh/.`
1. `cp $HOME/.ssh/id_ed25519.pub .ssh/.` // key should be registered in princeton_ansible deploy role
1. `lando start`
1. `cp drush/sites/example.site.yml drush/sites/recap.site.yml`
1. Uncomment the alias blocks and adjust the config values in the  `drush/sites/recap.site.yml` file to match the current remote and local drupal environments.
1. `bundle exec cap production database_dump` // this will produce a datestamped dump file in the format "backup-YYYY-MM-DD-{environment}.sql.gz".
1. `lando db-import backup-YYYY-MM-DD-{environment}.sql.gz`
1. `lando drush rsync @recap.prod:%files @recap.local:%files`
1. Copy the hash following `config_` in `sites/default/files`. Add value to `$settings['hash_salt']` in `sites/default/settings.php`. For example, if config directory in `sites/default/files` is `config_abc123`, then:
    ```
    $settings['hash_salt'] = 'abc123';
    ```
1. Uncomment the line beginning with `$settings['config_sync_directory']` in `sites/default/settings.php`. It should look like :
    ```
    $settings['config_sync_directory'] = 'sites/default/config'
    ```
1. Create a `drush/drush.yml` file with the following
```
options:
  uri: 'http://recap.lndo.site'
```
1. `lando drush uli --name=your-username`

### NPM and Gulp

1. `cd themes/custom/pinwheel`
1. `lando npm install`
1. `lando gulp deploy` (or any other gulp task)

### Configuration Syncing

Each time you pull from production it is a good idea to check the status of your site.  To check and see if you need to get changes run
```
lando drush config:status
```
If everything is up to date you will see
```
[notice] No differences between DB and sync directory.
```

If there are changes you need to import you will see something like **(note: Only in sync dir in the State)**
```
 ---------------------------------------------------- ------------------ 
  Name                                                 State             
 ---------------------------------------------------- ------------------ 
  core.entity_form_display.node.a_z_resource.default   Only in sync dir  
```

If there are changes you need to export you will see something like **(note: Only in DB in the State)**
```
---------------------------------------------------- ------------ 
  Name                                                 State       
 ---------------------------------------------------- ------------ 
  core.entity_form_display.node.a_z_resource.default   Only in DB  
```

#### Importing Configuration
Most of the time you will want to import the entire configuration.  The only time this would not be the case is if you have some states that are `Only in DB` and some the are `Only in sync dir` (You made changes and another developer have made changes).  To import the entire configuration run `lando drush config:import` or `lando drush config:import -y`.  If you run without the -y you will see a list of the changes being made before they get applied like below:
```
+------------+----------------------------------------------------+-----------+
| Collection | Config                                             | Operation |
+------------+----------------------------------------------------+-----------+
|            | field.storage.node.field_resource_link             | Create    |
|            | node.type.a_z_resource                             | Create    |
|            | field.field.node.a_z_resource.field_resource_link  | Create    |
```

If you have both exports and imports see the section below.

#### Exporting Configuration
Most of the time you will want to export the entire configuration.  The only time this would not be the case is if you have some states that are `Only in DB` and some the are `Only in sync dir` (You made changes and another developer have made changes).  To export the entire configuration run `lando drush config:export` or `lando drush config:export -y`.  If you run without the -y you will see a list of the changes being made before they get applied like below:
```
 [notice] Differences of the active config to the export directory:
+------------+----------------------------------------------------+-----------+
| Collection | Config                                             | Operation |
+------------+----------------------------------------------------+-----------+
|            | field.storage.node.field_resource_link             | Create    |
|            | node.type.a_z_resource                             | Create    |
|            | field.field.node.a_z_resource.
+------------+----------------------------------------------------+-----------+


 The .yml files in your export directory (sites/default/config) will be deleted and replaced with the active config. (yes/no) [yes]:
 > yes

 [success] Configuration successfully exported to sites/default/config.
 ```

If you have both exports and imports see the section below.

### Both Exporting and Importing Configuration
You made changes and another developer have made changes, and now the configuration must be merged.

We will use git to combine the two configurations.  
1. Check to make sure everything is committed in git with `git status`.  Commit any untracked changes.
1. Export you local changes on top of the existing git changes.
   ```
   lando drush config:export
   ```
1. Double check you do not want keep any of the changes
   ```
   git status
   git diff <modified file>
   ```
   for any file you want to keep the changes in
   ```
   git add <modified file>
   ```
   commit those changes so they do not get loast with either a `git commit` or `git commit --amend`
1. restore the lost changes tracked by git
   ```
   git reset HEAD .
   git checkout .
   ```
1. Import the changes both tracked and untracked
   ```
   lando drush config:import
   ```
1. Check your config status
   ```
   lando drush config:status
   ```
1. Commit your changes to git with a branch and a PR.



## Deploying to the server

We utilize capistrano to deploy the code out to the server.  To deploy code to an existing server run
`cap <server set> deploy ` (for example `cap production deploy`).

To import a database run `cap <server set> drupal:database:import_dump SQL_DIR=<path to dump> SQL_FILE=<dump file name>`

To install code on a blank sever you must deploy and upload a database, so you need to pass the database bump variables to the deploy command `cap <server set> deploy SQL_DIR=<path to dump> SQL_FILE=<dump file name>`

To see a list of al available command run `cap -T`
