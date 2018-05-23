# ReCAP
ReCAP Drupal 8 Site

## Requirements

- Drush 8
- PHP 5.5.9 or higher
- MySQL 5.5.3/MariaDB 5.5.20/Percona Server 5.5.8 or higher with PDO and an InnoDB-compatible primary storage engine, PostgreSQL 8.3 or higher with PDO, SQLite 3.4.2 or higher
- Docker
- [Lando](https://docs.devwithlando.io/)

## Local Development

- Set up an alias in `/drush/sites/` (copy the example and make adjustments)
- Use Lando to start container with `lando start`
- Get the latest dump from the production server with `drush @recap.prod sql-dump > dump.sql`
- Restart Lando `lando restart`
- Import the database `lando db-import`
- `cd themes/custom/recap` and generate assets related to the theme `lando npm install` and `lando gulp deploy`
- `cd ../../..` (path to recap) and rebuild the cache `lando drush cr`

## Syncing with Remote Environments

### Setup an Alias for the Remote Site in your local drush alias file
```
$aliases['recap-dev']  = array(
  'uri' => 'http://host-to-sync.princeton.edu',
  'root' => '/path_to_drupal_root/',
  'remote-host' => 'remote-host.princeton.edu',
  'remote-user' => 'my-remote-user',
  'ssh-options' => '-o PasswordAuthentication=no -i /path_to_my_ssh_alias',
  'path-aliases' => array(
    '%dump-dir' => '/path_to_remote_db_dump_directory/',
    '%dump' => '/path_to_remote_db_dump_directory/recap_sql_sync_dump.sql',
    '%sites' => '/path_to_drupal_root/sites/',
    '%files' => 'sites/default/files',
    ## You must have an executable drush 8 environment on the remote server
    ## available at a path your remote user can executable
    ## See instructions for install
    '%drush-script' => '/path_to_drupal_root/vendor/bin/drush',
  ),
   'databases' =>
      array (
        'default' =>
        array (
          'default' =>
          array (
            'driver' => 'mysql',
            'username' => 'db_user',
            'password' => 'db_passwd,
            'port' => '',
            'host' => 'localhost',
            'database' => 'db_name',
          ),
       ),
     ),
  'command-specific' => array (
    'sql-sync' => array (
      'simulate' => '0',
    ),
    'rsync' => array (
      'simulate' => '0',
    ),
  ),
);
```

### For database content
```
/path_to_local_drush_8_executable/drush @recap-dev sql-sync @recap-dev @recap-local
```

### For Files
```
/path_to_local_drush_8_executable/drush rsync @recap-dev:%files/ @recap-local:%files
```


