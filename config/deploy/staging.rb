server "recap-www-staging1", user: fetch(:user), roles: %w{app drupal_primary}
server "recap-www-staging2", user: fetch(:user), roles: %w{app drupal_secondary}

set :search_api_solr_host, 'lib-solr-staging.princeton.edu'
set :search_api_solr_path, '/solr/recap-staging'

set :db_name, "recap_staging"
