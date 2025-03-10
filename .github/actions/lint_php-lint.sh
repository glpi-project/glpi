#!/bin/bash
set -e -u -x -o pipefail

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

composer run lint

vendor/bin/composer-dependency-analyser

touch ~/phpcs.cache
vendor/bin/phpcs \
  --cache ~/phpcs.cache \
  .

echo "Run code static analysis"
vendor/bin/phpstan analyze \
  --ansi \
  --memory-limit=1G \
  --no-interaction
