#!/bin/bash -e

docker-compose exec -T app php --version
docker-compose exec -T app php -r 'echo(sprintf("PHP extensions: %s\n", implode(", ", get_loaded_extensions())));'
docker-compose exec -T app composer --version
docker-compose exec -T app sh -c 'echo "node $(node --version)"'
docker-compose exec -T app sh -c 'echo "npm $(npm --version)"'

if [[ -n $(docker-compose ps --all --services | grep "db") ]]; then
  docker-compose exec -T db mysql --version;
fi
