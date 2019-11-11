set :branch, ENV["BRANCH"] || "master"

server "recap-www-staging1", user: fetch(:user), roles: %w{app drupal_primary}

set :search_api_solr_host, 'lib-solr-staging.princeton.edu'
set :search_api_solr_path, '/solr/recap-staging'
