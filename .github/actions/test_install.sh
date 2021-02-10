#!/bin/bash -e

# Execute install
bin/console glpi:database:install \
  --config-dir=./tests --ansi --no-interaction \
  --reconfigure --db-name=glpi --db-host=db --db-user=root

# Execute update
## Should do nothing.
bin/console glpi:database:update --config-dir=./tests --ansi --no-interaction | tee migration.log
if [[ -z $(grep "No migration needed." migration.log) ]];
  then echo "glpi:database:update command FAILED" && exit 1;
fi
