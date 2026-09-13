#!/bin/bash
set -e -u -x -o pipefail

# Clone the tests database to be able to run the tests in parallel.
# The first paratest worker uses the `glpi` database, the following ones use `glpi_<worker number>`.
# The number of databases to create must match the number of paratest workers (see `test_tests-functional.sh`).

docker compose exec -T db bash -c '
  set -e -u -o pipefail
  if command -v mariadb &> /dev/null; then
    DB_CLIENT="mariadb"
    DB_DUMP="mariadb-dump"
  else
    DB_CLIENT="mysql"
    DB_DUMP="mysqldump --set-gtid-purged=OFF"
  fi
  for i in 2 3 4; do
    $DB_CLIENT -u root -e "DROP DATABASE IF EXISTS glpi_$i;"
    $DB_CLIENT -u root -e "CREATE DATABASE glpi_$i CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    $DB_DUMP -u root --single-transaction glpi | $DB_CLIENT -u root glpi_$i
    echo "Database glpi_$i created and populated"
  done
'
