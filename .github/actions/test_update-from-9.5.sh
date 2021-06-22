#!/bin/bash -e

LOG_FILE="./tests/files/_log/migration.log"
mkdir -p $(dirname "$LOG_FILE")

bin/console glpi:database:configure \
  --config-dir=./tests/config --no-interaction --ansi \
  --reconfigure --db-name=glpitest-9.5.3 --db-host=db --db-user=root --use-utf8mb4 \
  --log-deprecation-warnings

# Force ROW_FORMAT=DYNAMIC to prevent tests MySQL 5.6 and MariaDB 10.1 databases
# failure on indexes creation for varchar(255) fields.
## Result will depend on DB server/version, we just expect that command will not fail.
bin/console glpi:migration:dynamic_row_format --config-dir=./tests/config --ansi --no-interaction

# Execute update
## First run should do the migration (with no warnings).
## TODO Remove the --force option when schema version will be updated in define.php
bin/console glpi:database:update --config-dir=./tests/config --ansi --no-interaction --allow-unstable --force | tee $LOG_FILE
if [[ -n $(grep "Warning\|No migration needed." $LOG_FILE) ]];
  then echo "bin/console glpi:database:update command FAILED" && exit 1;
fi
## Second run should do nothing.
bin/console glpi:database:update --config-dir=./tests/config --ansi --no-interaction --allow-unstable | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console glpi:database:update command FAILED" && exit 1;
fi
## Check DB
bin/console glpi:database:check_schema_integrity --config-dir=./tests/config --ansi --no-interaction --ignore-utf8mb4-migration

# Execute myisam_to_innodb migration
## First run should do nothing.
bin/console glpi:migration:myisam_to_innodb --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console glpi:migration:myisam_to_innodb command FAILED" && exit 1;
fi

# Execute timestamps migration
## First run should do nothing.
bin/console glpi:migration:timestamps --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console glpi:migration:timestamps command FAILED" && exit 1;
fi

# Execute utf8mb4 migration
## First run should do the migration (with no warnings).
bin/console glpi:migration:utf8mb4 --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -n $(grep "Warning\|No migration needed." $LOG_FILE) ]];
  then echo "bin/console glpi:migration:utf8mb4 command FAILED" && exit 1;
fi
## Second run should do nothing.
bin/console glpi:migration:utf8mb4 --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console glpi:migration:utf8mb4 command FAILED" && exit 1;
fi
# Check DB
bin/console glpi:database:check_schema_integrity --config-dir=./tests/config --ansi --no-interaction

# Check updated data
bin/console glpi:database:configure \
  --config-dir=./tests/config --no-interaction --ansi \
  --reconfigure --db-name=glpi --db-host=db --db-user=root --use-utf8mb4 \
  --log-deprecation-warnings
mkdir -p ./tests/files/_cache
tests/bin/test-updated-data --host=db --user=root --fresh-db=glpi --updated-db=glpitest-9.5.3 --ansi --no-interaction
