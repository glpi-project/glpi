#!/bin/bash -e

docker exec app php --version
docker exec app php -r 'echo(sprintf("PHP extensions: %s\n", implode(", ", get_loaded_extensions())));'
docker exec app composer --version
docker exec app sh -c 'echo "node $(node --version)"'
docker exec app sh -c 'echo "npm $(npm --version)"'
[ ! "$(docker ps -q -f name=db)" ] || docker exec db mysql --version
