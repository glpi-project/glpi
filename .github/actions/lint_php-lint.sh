#!/bin/bash
set -e -u -x -o pipefail

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

vendor/bin/parallel-lint \
  --show-deprecated \
  --colors \
  --exclude ./files/ \
  --exclude ./marketplace/ \
  --exclude ./plugins/ \
  --exclude ./vendor/ \
  .

vendor/bin/composer-dependency-analyser

vendor/bin/php-cs-fixer check \
  --show-progress=dots \
  --verbose \
  --diff

echo "Run code static analysis"
vendor/bin/phpstan analyze \
  --verbose \
  --ansi \
  --memory-limit=1G \
  --no-interaction

echo "Run rector"
vendor/bin/rector process \
  --dry-run \
  --ansi
