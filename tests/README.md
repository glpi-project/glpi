## GLPI test suite

To run the GLPI test suite you need

* [phpunit](https://phpunit.de)

Installing dependencies
-----------------------

Run the following command in the top of GLPI tree:

```bash
$ php bin/console dependencies install
```

Creating a dedicated database
-----------------------------

Use the **database:install** CLI command to create a new database,
only used for the test suite, using the `--env=testing` option:

```bash
$ bin/console database:install --env=testing --db-name=glpitests --db-user=root --db-password=xxxx
Creating the database...
Saving configuration file...
Loading default schema...
Installation done.
```

The configuration file is saved as `tests/config/config_db.php`.

The database is created using the default schema for current version.

If you need to recreate the database (e.g. for a new schema), you need to run
**database:install** CLI command again with the `--force` option.


Changing database configuration
-------------------------------

Using the same database than the web application is not recommended. Use the `tests/config/config_db.php` file to adjust connection settings.

Running the test suite on developpement machine
-----------------------------------------------

There are multiple directories for tests:
- `tests/functional` for unit and functional tests;
- `tests/imap` for Mail collector tests;
- `tests/LDAP` for LDAP connection tests;
- `tests/web` for API tests.

You can choose to run tests on a whole directory, on any file, or on any \<class::method>:

```bash
$ ./vendor/bin/phpunit tests/functional/
[...]
$ ./vendor/bin/phpunit tests/functional/Html.php
[...]
$ ./vendor/bin/phpunit tests/functional/Html.php --filter testConvDateTime
```

If you want to run the API tests suite, you need to run a development instance on testing environment (ie. an Apache virtual host with the `SetEnv GLPI_ENVIRONMENT_TYPE "testing"` directive):

On first run, additional data are loaded into the test database. On following run, this step is skipped. Note that if the test dataset version changes; you'll have to reset your database using the **CliInstall** script again.

Note: you may see a skipped tests regarding missing extension `event`; this is expected ;)

Running the test suite on containerized env
-------------------------------------------

If you want to execute tests in an environment similar to what is done by CI, you can use the `tests/run_tests.sh`.
This scripts requires the "docker" utility to be installed.
Run `tests/run_tests.sh --help` for more information about its usage.
