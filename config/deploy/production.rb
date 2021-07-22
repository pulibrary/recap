server "recap-www-prod1", user: fetch(:user), roles: %w{app drupal_primary}

set :search_api_solr_host, 'lib-solr.princeton.edu'
set :search_api_solr_path, '/solr/recap-production'
