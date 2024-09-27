# Health check

The module provides /health endpoint which is never cached and returns only 
timestamp as a body with HTTP status code 200. If Drupal fails to bootstrap, 
it returns an error code instead and your load balancer knows there is
something wrong with the particular instance and can drop it from load 
balancing.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/health_check).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/health_check).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

The module has no modifiable settings. There is no configuration.


## Maintainers

- Marko Korhonen - [back-2-95](https://www.drupal.org/u/back-2-95)
- Roni Kantis - [bfr](https://www.drupal.org/u/bfr)
