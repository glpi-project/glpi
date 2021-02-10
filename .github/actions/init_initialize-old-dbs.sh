#!/bin/bash -e

echo "Initialize old versions databases"
docker exec db mysql --user=root --execute="CREATE DATABASE \`glpitest0723\`;"
cat tests/glpi-0.72.3-empty.sql | docker exec --interactive db mysql --user=root glpitest0723
