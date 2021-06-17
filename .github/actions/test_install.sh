#!/bin/bash -e

LOG_FILE="./tests/files/_log/install.log"
mkdir -p $(dirname "$LOG_FILE")

# Execute install
bin/console glpi:database:install \
  --config-dir=./tests/config --ansi --no-interaction \
  --reconfigure --db-name=glpi --db-host=db --db-user=root --force

# Execute update
## Should do nothing.
bin/console glpi:database:update --config-dir=./tests/config --ansi --no-interaction | tee $LOG_FILE
if [[ -z $(grep "No migration needed." $LOG_FILE) ]];
  then echo "glpi:database:update command FAILED" && exit 1;
fi
