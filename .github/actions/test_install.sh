#!/bin/bash
set -e -u -x -o pipefail

LOG_FILE="./tests/files/_log/install.log"
mkdir -p $(dirname "$LOG_FILE")

# Execute install
bin/console database:install \
  --config-dir=./tests/config --ansi --no-interaction \
  --force \
  --reconfigure --db-name=glpi --db-host=db --db-user=root \
  --strict-configuration \
  | tee $LOG_FILE
if [[ -n $(grep "Warning" $LOG_FILE) ]];
  then echo "database:install command FAILED" && exit 1;
fi

# Check DB
bin/console database:check_schema_integrity --config-dir=./tests/config --ansi --no-interaction --strict
bin/console tools:check_database_keys --config-dir=./tests/config --ansi --no-interaction --detect-useless-keys
bin/console tools:check_database_schema_consistency --config-dir=./tests/config --ansi --no-interaction
tests/bin/test-data-sanitization --ansi --no-interaction

# Execute update
## Should do nothing.
bin/console database:update --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "database:update command FAILED" && exit 1;
fi
