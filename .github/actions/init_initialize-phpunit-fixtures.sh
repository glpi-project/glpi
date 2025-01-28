#!/bin/bash
set -e -u -x -o pipefail

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

cat $ROOT_DIR/tests/glpi-formcreator-migration-data.sql | docker compose exec -T db mysql --user=root glpi
