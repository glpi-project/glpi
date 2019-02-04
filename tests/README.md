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

Use the **glpi:database:install** CLI command to create a new database,
only used for the test suite, using the `--config-dir=./tests` option:

```bash
$ bin/console glpi:database:install --config-dir=./tests --db-name=glpitests --db-user=root --db-password=xxxx
Creating the database...
Saving configuration file...
Loading default schema...
Installation done.
```

The configuration file is saved as `tests/db.yaml`.

The database is created using the default schema for current version.

If you need to recreate the database (e.g. for a new schema), you need to run
**glpi:database:install** CLI command again with the `--force` option.


Changing database configuration
-------------------------------

Using the same database than the web application is not recommended. Use the `tests/db.yaml` file to adjust connection settings.

Running the test suite
----------------------

There are two directories for tests:
- `tests/units` for main core tests;
- `tests/api` for API tests.

You can choose to run tests on a whole directory, on any file, or on any \<class::method>. You have to specify a bootstrap file each time:

```bash
$ atoum -bf tests/bootstrap.php -mcn 1 -d tests/units/
[...]
$ atoum -bf tests/bootstrap.php -f tests/units/Html.php
[...]
$ atoum -bf tests/bootstrap.php -f tests/units/Html.php -m tests\units\Html::testConvDateTime
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
- `--debug` to get extra informations when something goes wrong,
- `-mcn` limit number of concurrent runs. This is unfortunately mandatory running the whole test suite right now :/,
- `-ncc` do not generate code coverage,
- `--php` to change PHP executable to use,
- `-l` loop mode.

Note that if you do not use the `-ncc` switch; coverage will be generated in the `tests/code-coverage/` directory.

On first run, additional data are loaded into the test database. On following run, this step is skipped. Note that if the test dataset version changes; you'll have to reset your database using the **CliInstall** script again.

Note: you may see a skipped tests regarding missing extension `event`; this is expected ;)
