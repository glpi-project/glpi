#!/bin/bash
set -e -u -x -o pipefail

echo "Cleanup containers and volumes"
docker-compose down --volumes
