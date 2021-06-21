#!/bin/bash -e

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

echo "Initialize GLPI 9.5.3 database"
docker-compose exec -T db mysql --user=root --execute="DROP DATABASE IF EXISTS \`glpitest-9.5.3\`;"
docker-compose exec -T db mysql --user=root --execute="CREATE DATABASE \`glpitest-9.5.3\`;"
cat $ROOT_DIR/tests/glpi-9.5.3-empty.sql | docker-compose exec -T db mysql --user=root glpitest-9.5.3
