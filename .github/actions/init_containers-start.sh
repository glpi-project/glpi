#!/bin/bash
set -e -u -x -o pipefail

mkdir -p $APP_CONTAINER_HOME

docker compose pull --quiet
docker compose up --no-start
docker compose start

if [[ "$UPDATE_FILES_ACL" = true ]]; then
  # Change files rights to give write access to app container user
  command -v setfacl > /dev/null || sudo apt-get install --assume-yes --no-install-recommends --quiet acl
  setfacl --recursive --modify u:1000:rwx $APPLICATION_ROOT
  setfacl --recursive --modify u:1000:rwx $APP_CONTAINER_HOME

  # Prevent git to trigger errors related to unexpected directories ownership
  docker compose exec -T app git config --global --add safe.directory /var/www/glpi
  docker compose exec -T app bash -c "if [[ -d "/home/www-data/.cache/composer/vcs" ]]; then find /home/www-data/.cache/composer/vcs -d -mindepth 1 -maxdepth 1 -exec git config --global --add safe.directory {} \;; fi"
fi
