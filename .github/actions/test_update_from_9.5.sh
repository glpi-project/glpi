#!/bin/bash

bin/console glpi:database:configure --config-dir=./tests --no-interaction --ansi --reconfigure --db-name=glpitest-9.5.3 --db-host=db --db-user=root --use-utf8mb4 --log-deprecation-warnings

# Execute update
## First run should do the migration (with no warnings).
## TODO Remove the --force option when schema version will be updated in define.php
bin/console glpi:database:update --config-dir=./tests --ansi --no-interaction --allow-unstable --force | tee ~/migration.log
if [[ -n $(grep "Warning\|No migration needed." ~/migration.log) ]]; then echo "bin/console glpi:database:update command FAILED" && exit 1; fi
## Second run should do nothing.
bin/console glpi:database:update --config-dir=./tests --ansi --no-interaction --allow-unstable | tee ~/migration.log
if [[ -z $(grep "No migration needed." ~/migration.log) ]]; then echo "bin/console glpi:database:update command FAILED" && exit 1; fi

# Execute myisam_to_innodb migration
## First run should do nothing.
bin/console glpi:migration:myisam_to_innodb --config-dir=./tests --ansi --no-interaction | tee ~/migration.log
if [[ -z $(grep "No migration needed." ~/migration.log) ]]; then echo "bin/console glpi:migration:myisam_to_innodb command FAILED" && exit 1; fi

# Execute timestamps migration
## First run should do nothing.
bin/console glpi:migration:timestamps --config-dir=./tests --ansi --no-interaction | tee ~/migration.log
if [[ -z $(grep "No migration needed." ~/migration.log) ]]; then echo "bin/console glpi:migration:timestamps command FAILED" && exit 1; fi

# Execute utf8mb4 migration
## First run should do the migration (with no warnings).
bin/console glpi:migration:utf8mb4 --config-dir=./tests --ansi --no-interaction | tee ~/migration.log
if [[ -n $(grep "Warning\|No migration needed." ~/migration.log) ]]; then echo "bin/console glpi:migration:utf8mb4 command FAILED" && exit 1; fi
## Second run should do nothing.
bin/console glpi:migration:utf8mb4 --config-dir=./tests --ansi --no-interaction | tee ~/migration.log
if [[ -z $(grep "No migration needed." ~/migration.log) ]]; then echo "bin/console glpi:migration:utf8mb4 command FAILED" && exit 1; fi
