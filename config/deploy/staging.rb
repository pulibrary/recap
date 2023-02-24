server "recap-www-staging1", user: fetch(:user), roles: %w{app drupal_primary}

set :search_api_solr_host, 'lib-solr-staging.princeton.edu'
set :search_api_solr_path, '/solr/recap-staging'

server "mysql-db-staging1", user: 'pulsys', roles: %w{db}
set :db_name, "recap-staging"
