#!/bin/bash
set -e -u -x -o pipefail

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

docker compose exec -T db mysql --user=root --execute="DROP DATABASE IF EXISTS \`glpitest085\`;"
docker compose exec -T db mysql --user=root --execute="CREATE DATABASE \`glpitest085\`;"
cat $ROOT_DIR/tests/glpi-0.85.5-empty.sql | docker compose exec -T db mysql --user=root glpitest085
