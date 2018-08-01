# Drupal µMenu

Drupal core menu module replacement that does not attach to node types.

It provide its own storage table and API, based upon the
[Symfony - Dependency injection](https://github.com/makinacorpus/drupal-sf-dic)
module.

You must acknowledge that:

 *  this module does not bring along any end-user UI;

 *  it must be used with Symfony like services;

 *  it aims to give good scalability (in opposition to the Drupal core menu
    module). Actually core menu module is scalable except for 2 of its
    functions: ```menu_load_all()``` and ```menu_get_menus()```.


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
