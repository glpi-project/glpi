#!/bin/bash
set -e -u -x -o pipefail

LOG_FILE="./tests/files/_log/migration.log"
mkdir -p $(dirname "$LOG_FILE")

bin/console database:configure \
  --config-dir=./tests/config --no-interaction --ansi \
  --reconfigure --db-name=glpitest-9.5 --db-host=db --db-user=root \
  --strict-configuration

# Execute update
## First run should do the migration (with no warnings/errors).
bin/console database:update --config-dir=./tests/config --skip-db-checks --ansi --no-interaction --allow-unstable | tee $LOG_FILE
if [[ -n $(grep "Error\|Warning\|No migration needed." $LOG_FILE) ]];
  then echo "bin/console database:update command FAILED" && exit 1;
fi
## Second run should do nothing.
bin/console database:update --config-dir=./tests/config --skip-db-checks --ansi --no-interaction --allow-unstable | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console database:update command FAILED" && exit 1;
fi
## Check DB schema integrity (do not check additionnal migrations)
bin/console database:check_schema_integrity \
  --config-dir=./tests/config --ansi --no-interaction

# Execute myisam_to_innodb migration
## First run should do nothing.
bin/console migration:myisam_to_innodb --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console migration:myisam_to_innodb command FAILED" && exit 1;
fi
## Check DB schema integrity (including myisam_to_innodb migration)
bin/console database:check_schema_integrity \
  --config-dir=./tests/config --ansi --no-interaction \
  --check-innodb-migration

# Execute timestamps migration
## First run should do nothing.
bin/console migration:timestamps --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console migration:timestamps command FAILED" && exit 1;
fi
## Check DB schema integrity (including timestamps migration)
bin/console database:check_schema_integrity \
  --config-dir=./tests/config --ansi --no-interaction \
  --check-timestamps-migration

# Execute dynamic_row_format migration
## Result will depend on DB server/version, we just expect that command will not fail.
bin/console migration:dynamic_row_format --config-dir=./tests/config --ansi --no-interaction
## Check DB schema integrity (including dynamic_row_format migration)
bin/console database:check_schema_integrity \
  --config-dir=./tests/config --ansi --no-interaction \
  --check-dynamic-row-format-migration

# Execute utf8mb4 migration
## First run should do the migration (with no warnings).
bin/console migration:utf8mb4 --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -n $(grep "Warning\|No migration needed." $LOG_FILE) ]];
  then echo "bin/console migration:utf8mb4 command FAILED" && exit 1;
fi
## Second run should do nothing.
bin/console migration:utf8mb4 --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console migration:utf8mb4 command FAILED" && exit 1;
fi
## Check DB schema integrity (including utf8mb4 migration)
bin/console database:check_schema_integrity \
  --config-dir=./tests/config --ansi --no-interaction \
  --check-utf8mb4-migration

# Execute unsigned keys migration
## First run should do the migration (with no warnings).
bin/console migration:unsigned_keys --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -n $(grep "Warning\|No migration needed." $LOG_FILE) ]];
  then echo "bin/console migration:unsigned_keys command FAILED" && exit 1;
fi
## Second run should do nothing.
bin/console migration:unsigned_keys --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console migration:unsigned_keys command FAILED" && exit 1;
fi
## Check DB schema integrity (including unsigned keys migration)
bin/console database:check_schema_integrity \
  --config-dir=./tests/config --ansi --no-interaction \
  --check-unsigned-keys-migration

# Complete DB check
bin/console database:check_schema_integrity \
  --config-dir=./tests/config --ansi --no-interaction \
  --check-all-migrations
tests/bin/test-data-sanitization --ansi --no-interaction

# Check updated data
bin/console database:configure \
  --config-dir=./tests/config --no-interaction --ansi \
  --reconfigure --db-name=glpi --db-host=db --db-user=root \
  --strict-configuration
mkdir -p ./tests/files/_cache
tests/bin/test-updated-data --host=db --user=root --fresh-db=glpi --updated-db=glpitest-9.5 --ansi --no-interaction
