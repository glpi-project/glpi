#!/bin/bash
set -e -u -x -o pipefail

LOG_FILE="./tests/files/_log/install.log"
mkdir -p $(dirname "$LOG_FILE")

# Execute install
bin/console database:install \
  --ansi --no-interaction \
  --force \
  --reconfigure --db-name=glpi --db-host=db --db-user=root --db-password=glpi \
  --strict-configuration \
  | tee $LOG_FILE
if [[ -n $(grep "Warning" $LOG_FILE) ]];
  then echo "database:install command FAILED" && exit 1;
fi

# Check DB
bin/console database:check_schema_integrity --ansi --no-interaction --strict
bin/console tools:check_database_keys --ansi --no-interaction --detect-useless-keys
bin/console tools:check_database_schema_consistency --ansi --no-interaction

# Execute update
## Should do nothing.
bin/console database:update --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "database:update command FAILED" && exit 1;
fi
