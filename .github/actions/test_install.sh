#!/bin/bash -e

LOG_FILE="./tests/files/_log/install.log"
mkdir -p $(dirname "$LOG_FILE")

# Execute install
bin/console glpi:database:install \
  --config-dir=./tests/config --ansi --no-interaction \
  --force \
  --reconfigure --db-name=glpi --db-host=db --db-user=root \
  --log-deprecation-warnings \
  | tee $LOG_FILE
if [[ -n $(grep "Warning" $LOG_FILE) ]];
  then echo "glpi:database:install command FAILED" && exit 1;
fi

# Check DB
bin/console glpi:database:check_schema_integrity --config-dir=./tests/config --ansi --no-interaction --strict
bin/console glpi:tools:check_database_keys --config-dir=./tests/config --ansi --no-interaction --detect-useless-keys

# Execute update
## Should do nothing.
bin/console glpi:database:update --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "glpi:database:update command FAILED" && exit 1;
fi
