## GLPI test suite

To run the GLPI test suite you need

* [atoum](http://atoum.org/)

Installing composer development dependencies
----------------------

Run the **composer install** command without --no-dev option in the top of GLPI tree:

```bash
$ composer install -o

Loading composer repositories with package information
Installing dependencies (including require-dev) from lock file
[...]
Generating optimized autoload files
```

Creating a dedicated database
-----------------------------

Use the **database:install** CLI command to create a new database,
only used for the test suite, using the `--config-dir=./tests/config` option:

```bash
$ bin/console database:install --config-dir=./tests/config --db-name=glpitests --db-user=root --db-password=xxxx
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

Running the test suite on developpement env
-------------------------------------------

There are multiple directories for tests:
- `tests/functional` and `phpunit/functional` for unit and functional tests;
- `phpunit/imap` for Mail collector tests;
- `tests/LDAP` for LDAP connection tests;
- `tests/web` for API tests.

You can choose to run tests on a whole directory, on any file, or on any \<class::method>. You have to specify a bootstrap file each time:

```bash
$ atoum -bf tests/bootstrap.php -mcn 1 -d tests/functional/
[...]
$ atoum -bf tests/bootstrap.php -f tests/functional/Html.php
[...]
$ atoum -bf tests/bootstrap.php -f tests/functional/Html.php -m tests\units\Html::testConvDateTime
```
In `tests\units\Html::testConvDateTime`, you may need to double the backslashes (depending on the shell you use);

If you want to run the API tests suite, you need to run a development server:

```bash
php -S localhost:8088 tests/router.php &>/dev/null &
```

Running `atoum` without any arguments will show you the possible options. Most important are:
- `-bf` to set bootstrap file,
- `-d` to run tests located in a whole directory,
- `-f` to run tests on a standalone file,
- `-m` to run tests on all \<class::method>, * may be used as wildcard for class name or method name,
- `--debug` to get extra information when something goes wrong,
- `-mcn` limit number of concurrent runs. This is unfortunately mandatory running the whole test suite right now :/,
- `-ncc` do not generate code coverage,
- `--php` to change PHP executable to use,
- `-l` loop mode.

Note that if you do not use the `-ncc` switch; coverage will be generated in the `tests/code-coverage/` directory.

On first run, additional data are loaded into the test database. On following run, this step is skipped. Note that if the test dataset version changes; you'll have to reset your database using the **CliInstall** script again.

Note: you may see a skipped tests regarding missing extension `event`; this is expected ;)

Running the test suite on containerized env
-------------------------------------------

If you want to execute tests in an environment similar to what is done by CI, you can use the `tests/run_tests.sh`.
This scripts requires the "docker" utility to be installed.
Run `tests/run_tests.sh --help` for more information about its usage.
