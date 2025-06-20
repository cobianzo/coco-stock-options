TODO
===


WHAT IS THIS PROJECT
===

[...]

HOW IT WORKS
===


DEVELOPMENT
===
Dependencies
- v18.20.3
- npm 10.7.0
- composer 2.7.9
- install wp-env globally (when I run it locally it takes too long to load).

`npm run up`
this will also install a folder /wordpress for better development.

or, the first time use the global package of wp-env if it doesnt work
`wp-env start`

WordPress development site started at http://localhost:8666
WordPress test site started at http://localhost:8667
MySQL is listening on port 54868
MySQL for automated testing is listening on port 54878
> Use `docker ps | grep mysql` to know the port at anytime.

## Use CLI

`wp-env run cli`
`wp-env run cli bash`

### Use CLI for DB connection (MySQL/MariaDB)

The raw connection would be (replacing the port with the one created by wp-env):

`mysql -h127.0.0.1 -P54868 -uroot -p`

Using DB CLI
`wp-env run cli wp db cli`

To know more info, which can be used to connect from a DB Client.

```
wp-env run cli wp config get DB_HOST   # Host is 127.0.0.1
wp-env run cli wp config get DB_NAME   # Name is wordpress
wp-env run cli wp config get DB_USER   # User is root
wp-env run cli wp config get DB_PASSWORD   # Password is password
And the port you'll have to find out with
> `docker ps | grep mysql`
```

Simple way to export and import DB into the root of the project
`wordpress.sql`:

```>export db
sh ./bin/export-db.sh
```
```>import db
sh ./bin/import-db.sh
```

### Use WP CLI

`wp-env run cli wp get option siteurl`

# PHPCS

Installed Alleys PHPCS standards, which uses WordPress VIP.
Installed PHPStan too.
Both work ok, check composer.json scripts to run the phpcs, phpcbf and phpstan commands.
Check AI-AGENT.md for more info.

# PHPSTAN

@TODO:
I had problems creating a centralized php with types that I can reuse and import.
I was not able to make it work, so sometimes I need to repeat relative complex types in every file.
Iused scanFiles in phpstan.neon, added the lines in composer.json, cleared the cache...

## commands
```
composer run lint,
composer run format,
composer analyze
npm run cs .
npm run cbf .
```

# PHPUNIT

```
npm run test:php
npm run test:php:watch
```
it uses wp-env run tests-wordpress ...

Important, we need to use php 8.3 in wp-env, so we can run the package
`wp-env run tests-wordpress` which works perfectly out of the box.

The watch version of the phpunit run works like charm!!

If run teh tests outside the container, it's still not tested.

packages:
- `phpunit/phpunit`: ! important, version 9 max, or it will be incompatible with function inside teh tests.
Then we can access locally o inside the container to wp-content/plugins/coco-miplugin/vendor/bin/phpunit
- `yoast/phpunit-polyfills` it must be installed, and `wp-env run tests-wordpress` finds it automatically. When installed, it install phpunit, as it depends on it, but the version 12. We need to install phpunit ourselves, the version 9, so there are no incompatibilites.
- `spatie/phpunit-watcher`: for the phpUnit watcher, ran with `npm run test:php:watch`.
- ~~wp-phpunit/wp-phpunit~~: not needed, all the bootstrap is handled by `wp-env run tests-wordpress`

# TESTS PHP

## Useful WP CLI commands

...

## TRANSLATIONS (localization)

To create the .pot with all tranlatable strings, get into the container with
`npm run cli bash`
and
```
cd wp-content/plugins/coco-miplugin
wp i18n make-pot . languages/coco-miplugin.pot --domain=coco-miplugin
```
To make the translations you can use Poedit, opening `coco-miplugin-es_ES.po`, and update the catalogue from the .pot ( Translation > Update from POT File ).
Then translate what you want. To make the translations you can use Poedit, but I like to open the .po with VSCode, and translate the strings with IA, under the promt 'for every empty msgstr, set the translation from msgid into italian.'.
Then, to create the .mo files, we need to run
```
wp i18n make-mo ./languages/
```
it takes a while to be created.
And for the `.json`, needed for the typescript tranlations:
```
wp i18n make-json ./languages/
```

# Useful tools to develop shapes

Helps to design the path
https://boxy-svg.com/app

Helps to undesrstand every point of the path.
https://svg-path-visualizer.netlify.app/
