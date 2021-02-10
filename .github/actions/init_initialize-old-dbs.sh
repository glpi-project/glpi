#!/bin/bash -e

echo "Initialize old versions databases"
docker exec db mysql --user=root --execute="CREATE DATABASE \`glpitest080\`;"
cat tests/glpi-0.80-empty.sql | docker exec --interactive db mysql --user=root glpitest080
docker exec db mysql --user=root --execute="CREATE DATABASE \`glpitest-9.5.3\`;"
cat tests/glpi-9.5.3-empty.sql | docker exec --interactive db mysql --user=root glpitest-9.5.3
