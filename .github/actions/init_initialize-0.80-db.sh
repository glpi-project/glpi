#!/bin/bash
set -e -u -x -o pipefail

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

docker compose exec -T db mysql --user=root --execute="DROP DATABASE IF EXISTS \`glpitest080\`;"
docker compose exec -T db mysql --user=root --execute="CREATE DATABASE \`glpitest080\`;"
cat $ROOT_DIR/tests/glpi-0.80-empty.sql | docker compose exec -T db mysql --user=root glpitest080
