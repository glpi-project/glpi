#!/bin/bash
set -e -u -x -o pipefail

LOG_FILE="./tests/files/_log/migration.log"
mkdir -p $(dirname "$LOG_FILE")

# Reconfigure DB
bin/console database:configure \
  --ansi --no-interaction \
  --reconfigure --db-name=glpitest085 --db-host=db --db-user=root --db-password=""

# Force delete old oauth keys to test they are regenerated
rm -f ./tests/config/oauth.pem ./tests/config/oauth.pub

# Execute myisam_to_innodb migration
## First run should do the migration (with no warnings).
bin/console migration:myisam_to_innodb --ansi --no-interaction | tee $LOG_FILE
if [[ -n $(grep "Warning\|No migration needed." $LOG_FILE) ]];
  then echo "bin/console migration:myisam_to_innodb command FAILED" && exit 1;
fi
## Second run should do nothing.
bin/console migration:myisam_to_innodb --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console migration:myisam_to_innodb command FAILED" && exit 1;
fi

# Execute update
## First run should do the migration (with no warnings/errors).
bin/console database:update --skip-db-checks --ansi --no-interaction --allow-unstable | tee $LOG_FILE
if [[ -n $(grep "Error\|Warning\|No migration needed." $LOG_FILE) ]];
  then echo "bin/console database:update command FAILED" && exit 1;
fi
## Second run should do nothing.
bin/console database:update --skip-db-checks --ansi --no-interaction --allow-unstable | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console database:update command FAILED" && exit 1;
fi
## Check DB schema integrity (do not check additionnal migrations)
bin/console database:check_schema_integrity \
  --ansi --no-interaction

## Check DB schema integrity (including myisam_to_innodb migration)
bin/console database:check_schema_integrity \
  --ansi --no-interaction \
  --check-innodb-migration

# Execute timestamps migration
## First run should do the migration (with no warnings).
bin/console migration:timestamps --ansi --no-interaction | tee $LOG_FILE
if [[ -n $(grep "Warning\|No migration needed." $LOG_FILE) ]];
  then echo "bin/console migration:timestamps command FAILED" && exit 1;
fi
## Second run should do nothing.
bin/console migration:timestamps --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console migration:timestamps command FAILED" && exit 1;
fi
## Check DB schema integrity (including timestamps migration)
bin/console database:check_schema_integrity \
  --ansi --no-interaction \
  --check-timestamps-migration

# Execute dynamic_row_format migration
## Result will depend on DB server/version, we just expect that command will not fail.
bin/console migration:dynamic_row_format --ansi --no-interaction
## Check DB schema integrity (including dynamic_row_format migration)
bin/console database:check_schema_integrity \
  --ansi --no-interaction \
  --check-dynamic-row-format-migration

# Execute utf8mb4 migration
## First run should do the migration (with no warnings).
bin/console migration:utf8mb4 --ansi --no-interaction | tee $LOG_FILE
if [[ -n $(grep "Warning\|No migration needed." $LOG_FILE) ]];
  then echo "bin/console migration:utf8mb4 command FAILED" && exit 1;
fi
## Second run should do nothing.
bin/console migration:utf8mb4 --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console migration:utf8mb4 command FAILED" && exit 1;
fi
## Check DB schema integrity (including utf8mb4 migration)
bin/console database:check_schema_integrity \
  --ansi --no-interaction \
  --check-utf8mb4-migration

# Execute unsigned keys migration
## First run should do the migration (with no warnings).
bin/console migration:unsigned_keys --ansi --no-interaction | tee $LOG_FILE
if [[ -n $(grep "Warning\|No migration needed." $LOG_FILE) ]];
  then echo "bin/console migration:unsigned_keys command FAILED" && exit 1;
fi
## Second run should do nothing.
bin/console migration:unsigned_keys --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "bin/console migration:unsigned_keys command FAILED" && exit 1;
fi
## Check DB schema integrity (including unsigned keys migration)
bin/console database:check_schema_integrity \
  --ansi --no-interaction \
  --check-unsigned-keys-migration

# Complete DB check
bin/console database:check_schema_integrity \
  --ansi --no-interaction \
  --check-all-migrations

# Check updated data
bin/console database:configure \
  --no-interaction --ansi \
  --reconfigure --db-name=glpi --db-host=db --db-user=root --db-password="" \
  --strict-configuration

# Check the OAuth keys are generated
if [ ! -f ./tests/config/oauth.pem ] || [ ! -f ./tests/config/oauth.pub ]; then
  echo "OAuth keys are missing" && exit 1;
fi

tests/bin/test-updated-data --host=db --user=root --fresh-db=glpi --updated-db=glpitest085 --ansi --no-interaction | tee $LOG_FILE
if [[ -n $(grep "Warning" $LOG_FILE) ]];
  then echo "tests/bin/test-updated-data FAILED" && exit 1;
fi
