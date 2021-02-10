#!/bin/bash -e

echo "Cleanup containers"
docker stop $(docker ps -q)
docker container prune --force
docker volume prune --force
docker network prune --force
