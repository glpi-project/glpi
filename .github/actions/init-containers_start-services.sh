#!/bin/bash -e

echo "Pull and start services containers"
docker-compose --file .github/actions/docker-compose-services.yml pull --quiet
docker-compose --file .github/actions/docker-compose-services.yml up --no-start
docker-compose --file .github/actions/docker-compose-services.yml start
