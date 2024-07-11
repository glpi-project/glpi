#!/bin/bash
set -e -u -x -o pipefail

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

docker compose exec -T db mysql --user=root --execute="DROP DATABASE IF EXISTS \`glpitest-9.5\`;"
docker compose exec -T db mysql --user=root --execute="CREATE DATABASE \`glpitest-9.5\`;"
cat $ROOT_DIR/install/mysql/glpi-9.5.13-empty.sql | docker compose exec -T db mysql --user=root glpitest-9.5
cat $ROOT_DIR/tests/glpi-9.5-data.sql | docker compose exec -T db mysql --user=root glpitest-9.5
