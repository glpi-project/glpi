#!/bin/bash -e

LOG_FILE="./tests/files/_log/migration.log"
mkdir -p $(dirname "$LOG_FILE")

# Reconfigure DB
bin/console glpi:database:configure \
  --config-dir=./tests/config --ansi --no-interaction \
  --reconfigure --db-name=glpitest080 --db-host=db --db-user=root

# Execute update
## First run should do the migration (with no warnings).
bin/console glpi:database:update --config-dir=./tests/config --ansi --no-interaction --allow-unstable | tee $LOG_FILE
if [[ -n $(grep "Warning\|No migration needed." $LOG_FILE) ]];
  then echo "bin/console glpi:database:update command FAILED" && exit 1;
fi
## Second run should do nothing.
bin/console glpi:database:update --config-dir=./tests/config --ansi --no-interaction --allow-unstable | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console glpi:database:update command FAILED" && exit 1;
fi
## Check DB
bin/console glpi:database:check_schema_integrity \
  --config-dir=./tests/config --ansi --no-interaction \
  --ignore-innodb-migration --ignore-timestamps-migration --ignore-dynamic-row-format-migration --ignore-utf8mb4-migration

# Execute myisam_to_innodb migration
## First run should do the migration (with no warnings).
bin/console glpi:migration:myisam_to_innodb --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -n $(grep "Warning\|No migration needed." $LOG_FILE) ]];
  then echo "bin/console glpi:migration:myisam_to_innodb command FAILED" && exit 1;
fi
## Second run should do nothing.
bin/console glpi:migration:myisam_to_innodb --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console glpi:migration:myisam_to_innodb command FAILED" && exit 1;
fi
## Check DB
bin/console glpi:database:check_schema_integrity \
  --config-dir=./tests/config --ansi --no-interaction \
  --ignore-timestamps-migration --ignore-dynamic-row-format-migration --ignore-utf8mb4-migration

# Execute timestamps migration
## First run should do the migration (with no warnings).
bin/console glpi:migration:timestamps --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -n $(grep "Warning\|No migration needed." $LOG_FILE) ]];
  then echo "bin/console glpi:migration:timestamps command FAILED" && exit 1;
fi
## Second run should do nothing.
bin/console glpi:migration:timestamps --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console glpi:migration:timestamps command FAILED" && exit 1;
fi
## Check DB
bin/console glpi:database:check_schema_integrity \
  --config-dir=./tests/config --ansi --no-interaction \
  --ignore-dynamic-row-format-migration --ignore-utf8mb4-migration

# Execute dynamic_row_format migration
## Result will depend on DB server/version, we just expect that command will not fail.
bin/console glpi:migration:dynamic_row_format --config-dir=./tests/config --ansi --no-interaction
## Check DB
bin/console glpi:database:check_schema_integrity --config-dir=./tests/config --ansi --no-interaction --ignore-utf8mb4-migration

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
## Check DB
bin/console glpi:database:check_schema_integrity --config-dir=./tests/config --ansi --no-interaction

# Check updated data
bin/console glpi:database:configure \
  --config-dir=./tests/config --no-interaction --ansi \
  --reconfigure --db-name=glpi --db-host=db --db-user=root --use-utf8mb4 \
  --log-deprecation-warnings
tests/bin/test-updated-data --host=db --user=root --fresh-db=glpi --updated-db=glpitest080 --ansi --no-interaction
