#!/bin/bash
set -e -u -x -o pipefail

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

composer run lint

vendor/bin/composer-dependency-analyser

PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer check \
  --show-progress=dots \
  --verbose \
  --diff

echo "Run code static analysis"
vendor/bin/phpstan analyze \
  --ansi \
  --memory-limit=1G \
  --no-interaction
