##GLPI test suite

To run the GLPI test suite you need

* [PHPunit](https://phpunit.de/) version 4.8 or greater

Installing composer development dependencies
----------------------

Run the **composer install** command without --no-dev option in the top of GLPI tree:

```bash
$ composer install -o

Loading composer repositories with package information
Installing dependencies (including require-dev) from lock file
  - Installing react/promise (v2.4.1)
    Loading from cache

  - Installing guzzlehttp/streams (3.0.0)
    Loading from cache

  - Installing guzzlehttp/ringphp (1.1.0)
    Loading from cache

  - Installing guzzlehttp/guzzle (5.3.1)
    Loading from cache

Generating optimized autoload files
```

Creating a dedicated database
-----------------------------

Use the **CliInstall** script to create a new database,
only used for the test suite, using the `--tests` option:

```bash
$ php tools/cliinstall.php --db=glpitests --user=root --pass=xxxx --lang=en_US --tests
Connect to the DB...
Create the DB...
Save configuration file...
Load default schema...
Done
```

The configuration file is saved as `tests/config_db.php`.

The database is created using the default schema for current version.

If you need to recreate the database (e.g. for a new schema), you need to run
**CliInstall** again with the `--force` option.


Changing database configuration
-------------------------------

If you prefer to use the another test configuration file, 
Copy the `phpunit.xml.dist` file to `phpunit.xml` and change 
the `GLPI_CONFIG_DIR` option.

Using the same database than the web application is not recommended.


Running the test suite
----------------------

Run the **phpunit** command in the top of GLPI tree:

```bash
$ phpunit

Loading GLPI dataset version 2
+++++++++++++++++
Done

PHPUnit 5.3.4 by Sebastian Bergmann and contributors.

Runtime:       PHP 5.6.21
Configuration: /work/GLPI/master/phpunit.xml.dist

.........................................................         57 / 57 (100%)

Time: 419 ms, Memory: 41.00MB

OK (57 tests, 630 assertions)
```

On first run, additional data are loaded into the test database.
On following run, this step is skipped.
