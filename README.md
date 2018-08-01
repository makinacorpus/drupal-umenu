# Drupal µMenu

Drupal core menu module replacement that does not attach to node types.

It provide its own storage table and API.

You must acknowledge that this module does not bring along any end-user UI.


# Which version to use


## Drupal 8

Use 3.x versions.


## Drupal 7

Use 2.x versions for a stable experience.


# Run tests

```sh
cd /path/to/web
../vendor/bin/drush en simpletest
../vendor/bin/drush en umenu
php ./core/scripts/run-tests.sh --verbose umenu
```
