#!/bin/bash -e

echo "Cleanup containers and volumes"
docker-compose down --volumes
