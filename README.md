# Global Judicial Portal

## Installation
* Clone the repository
* Copy `web/sites/example.settings.local.php` to `web/sites/default/settings.local.php` and customize database credentials.
* Run `./install.sh`

## Updating Drupal Core
`composer update drupal/core webflo/drupal-core-require-dev symfony/* --with-dependencies`
