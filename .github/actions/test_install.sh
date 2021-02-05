#!/bin/bash -e

# Execute install
bin/console glpi:database:install \
  --config-dir=./tests --ansi --no-interaction \
  --reconfigure --db-name=glpi --db-host=db --db-user=root \
  --log-deprecation-warnings \
  | tee install.log
if [[ -n $(grep "Warning" install.log) ]];
  then echo "glpi:database:install command FAILED" && exit 1;
fi

# Check DB
bin/console glpi:database:check_schema --config-dir=./tests --ansi --no-interaction --strict
bin/console glpi:database:check_keys --config-dir=./tests --ansi --no-interaction --detect-useless-keys

# Execute update
## Should do nothing.
bin/console glpi:database:update --config-dir=./tests --ansi --no-interaction | tee migration.log
if [[ -z $(grep "No migration needed." migration.log) ]];
  then echo "glpi:database:update command FAILED" && exit 1;
fi
