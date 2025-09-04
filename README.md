COCO STOCK OPTIONS
===

This plugin defines Custom Post Types (CPT) for tracking required stocks. It scans for future options values for each stock from the CBOE API (`https://cdn.cboe.com/api/global/delayed_quotes/options/<stock-symbol>.json`) via a cron job. The results are formatted and saved as post meta, then exposed through a custom REST API endpoint.

Example endpoints

/wp-json/coco/v1/puts/LMT?date=250808&strike=00447500&field=bid



TODO
===

Translations

Admin
===
Cron jobs
Hacer el frontend mostrando la informacion tabulada de cada call y put de un ticker en la pagina single. Tambien mostrar botones para los endppints.
Arreglar bug que dice ultima actualizacion Never.
Ver por que no funciona en cobianzo.com
Arreglar todos los phpcs errors
Anadir phpstan
Anadir phpunit tests
Cache all the cacheable things.
Las fechas de los cron jobs no cuadra. El main cron job esta siempre programado para AHORA

React app:
===
Popup funciona pero no tiene utilidad.


HOW IT WORKS
===

El proceso se inicia a través de un cron job que, en lugar de actualizar todos los stocks a la vez, utiliza un sistema de "buffer" para procesarlos por lotes.

  Aquí tienes el flujo detallado:

  Flujo de Actualización de Stock


   1. Inicio del Cron Job (`CronJob::update_buffer_callback`)
       * Clase: \CocoStockOptions\Cron\CronJob
       * Fichero: inc/cron/class-cron-job.php
       * Método: update_buffer_callback()
       * Descripción: Un "cron job" de WordPress (cocostock_update_buffer) ejecuta este método. Su única responsabilidad es llamar al gestor del
         buffer para que procese el siguiente lote de stocks.


   2. Procesamiento del Buffer (`BufferManager::process_buffer`)
       * Clase: \CocoStockOptions\Cron\BufferManager
       * Fichero: inc/cron/class-buffer-manager.php
       * Método: process_buffer()
       * Descripción: Este método coge un lote de stocks del buffer (el número de stocks por lote se define en las opciones del plugin). Por cada
         stock del lote (por ejemplo, "LMT"), llama al sincronizador de datos.


   3. Sincronización de Datos (`SyncCboeData::sync_stock`)
       * Clase: \CocoStockOptions\Cboe\SyncCboeData
       * Fichero: inc/cboe/class-sync-cboe-data.php
       * Método: sync_stock( string $symbol )
       * Descripción: Este es el núcleo del proceso para un stock individual.
           1. Llama a la conexión con CBOE para obtener los datos nuevos para el símbolo del stock ("LMT").
           2. Si la obtención de datos es exitosa, llama al gestor de metadatos para guardar la información.


   4. Conexión con la API de CBOE (`CboeConnection::get_options_data`)
       * Clase: \CocoStockOptions\Cboe\CboeConnection
       * Fichero: inc/cboe/class-cboe-connection.php
       * Método: get_options_data( string $symbol )
       * Descripción: Este método construye la URL de la API de CBOE (ej: https://cdn.cboe.com/api/global/delayed_quotes/options/LMT.json) y realiza la petición HTTP para obtener los datos en formato JSON. Devuelve los datos decodificados.


   5. Guardado de Metadatos (`Stock_Meta::save_options_data`)
       * Clase: \CocoStockOptions\Models\Stock_Meta
       * Fichero: inc/models/class-stock-meta.php
       * Método: save_options_data( int $post_id, array $options_data )
       * Descripción: Una vez que SyncCboeData ha obtenido los datos, llama a este método. Se encarga de guardar los datos de las opciones (puts
         y calls) como metadatos del post asociado al stock "LMT", utilizando la función update_post_meta() de WordPress.


  En resumen, para actualizar el stock "LMT", el Cron Job inicia el Buffer Manager, que a su vez le pide al Sync Cboe Data que sincronice
  "LMT". Este último usa la Cboe Connection para traer los datos y el Stock Meta para guardarlos en la base de datos.


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
Then we can access locally o inside the container to wp-content/plugins/coco-stock-options/vendor/bin/phpunit
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
cd wp-content/plugins/coco-stock-options
wp i18n make-pot . languages/coco-stock-options.pot --domain=coco-stock-options
```
To make the translations you can use Poedit, opening `coco-stock-options-es_ES.po`, and update the catalogue from the .pot ( Translation > Update from POT File ).
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
