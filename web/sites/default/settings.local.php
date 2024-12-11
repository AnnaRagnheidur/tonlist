<?php

// Set error logging to log every error
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
$config['system.logging']['error_level'] = 'verbose';

// Turns off preprocessing of CSS and JS files, leaving them to be loaded
// without minification
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

// Loads misc Drupal services - See services.yml file
// Uncomment if needed
$settings['container_yamls'][] = $app_root . '/sites/default/development.services.yml';

// Disable the render cache and disable dynamic page cache
// This causes the site to be slower locally but helps out in development
$settings['cache']['bins']['render'] = 'cache.backend.memory';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.memory';
$settings['cache']['bins']['page'] = 'cache.backend.memory';

// Drupal >= 8.8
// Changes the default location of the config_sync_directory. Default
// location is sites/default/files/config, but by having the config outside
// of the web root, we can more easily store the config in Git, without
// compromising the config data being exposed.
$settings['config_sync_directory'] = $app_root . '/../config/sync';

// These module configs should never be exported, since they are development modules
$settings['config_exclude_modules'] = ['devel', 'stage_file_proxy'];

// See: https://www.drupal.org/docs/installing-drupal/trusted-host-settings
$settings['trusted_host_patterns'] = [
  '^.+\.ddev\.site$',
];

// Environment indicator
// name   | fg_color | bg_color
// -------|----------|---------
// local  | #ffffff  | #000000
$config['environment_indicator.indicator']['name'] = 'local';
$config['environment_indicator.indicator']['fg_color'] = '#ffffff';
$config['environment_indicator.indicator']['bg_color'] = '#000000';
