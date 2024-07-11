#!/bin/bash
set -e -u -o pipefail

docker compose exec -T app php --version
docker compose exec -T app php -r 'echo(sprintf("PHP extensions: %s\n", implode(", ", get_loaded_extensions())));'
docker compose exec -T app composer --version
docker compose exec -T app sh -c 'echo "node $(node --version)"'
docker compose exec -T app sh -c 'echo "npm $(npm --version)"'

if [[ -n $(docker compose ps --all --services | grep "db") ]]; then
  docker compose exec -T db mysql --version;
fi
if [[ -n $(docker compose ps --all --services | grep "redis") ]]; then
  docker compose exec -T redis redis-server --version;
fi
if [[ -n $(docker compose ps --all --services | grep "memcached") ]]; then
  docker compose exec -T memcached memcached --version;
fi
