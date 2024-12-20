# Tonlist

## Required
Needed for this project:
- PHP
- Composer
- Drush
- A database

### Go to the project directory 

Install dependancies

    composer install

Set permissions for the files directory

    chmod -R 755 sites/default/files

Install Drupal

    drush site:install

Import configuration

    drush config:import

Run the server

    drush runserver

Then visit the site
